<?php
require __DIR__.'/../app/bootstrap.php';
require_login();
require __DIR__.'/../app/db.php';
header('Content-Type: application/json');

/* 期待するpayload:
{
  "start": "2025-09-08",
  "servings": 2,
  "meals": ["breakfast","lunch","dinner"],
  "exclude_ingredients": ["卵","牛乳"],
  "prefer_tags": ["時短","作り置き"],
  "no_repeat_days": 7
}
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']); exit;
}
$req = json_decode(file_get_contents('php://input'), true) ?? [];
$uid = current_user_id();

$start = $req['start'] ?? date('Y-m-d', strtotime('monday this week'));
$servings = max(1, (int)($req['servings'] ?? 2));
$meals = $req['meals'] ?? ['breakfast','lunch','dinner'];
$excl = array_map('trim', $req['exclude_ingredients'] ?? []);
$pref = array_map('trim', $req['prefer_tags'] ?? []);
$noRepeatDays = max(0, (int)($req['no_repeat_days'] ?? 7));

$startDt = new DateTime($start);
$days=[]; for($i=0;$i<7;$i++){ $d=clone $startDt; $d->modify("+$i day"); $days[] = $d->format('Y-m-d'); }

/* 1) 直近noRepeatDaysの使用レシピを除外するため取得 */
$since = (clone $startDt)->modify("-{$noRepeatDays} day")->format('Y-m-d');
$st = $pdo->prepare("SELECT DISTINCT recipe_id FROM meal_plans WHERE user_id=? AND date>=? AND date<?");
$st->execute([$uid, $since, $startDt->format('Y-m-d')]);
$recent = array_column($st->fetchAll(), 'recipe_id'); // 配列

/* 2) NG食材を含むレシピIDを特定 */
$ngIds = [];
if ($excl) {
  $in = implode(',', array_fill(0, count($excl), '?'));
  $q = $pdo->prepare("SELECT DISTINCT recipe_id FROM recipe_ingredients WHERE name IN ($in)");
  $q->execute($excl);
  $ngIds = array_column($q->fetchAll(), 'recipe_id');
}
$ngSet = array_flip($ngIds);
$recentSet = array_flip($recent);

/* 3) 候補レシピを取得してスコアリング */
$q = $pdo->prepare("SELECT id,title,tags,servings FROM recipes WHERE user_id=?");
$q->execute([$uid]);
$candidates = [];
foreach ($q as $r) {
  if (isset($ngSet[$r['id']])) continue;
  if (isset($recentSet[$r['id']])) continue;

  $tags = array_filter(array_map('trim', explode(',', (string)$r['tags'])));
  $match = count(array_intersect($pref, $tags));
  $random = mt_rand(0, 100) / 1000; // 0〜0.1のランダムで並び替えのばらつき
  $score = $match + $random;
  $candidates[] = ['id'=>(int)$r['id'], 'title'=>$r['title'], 'score'=>$score];
}
usort($candidates, fn($a,$b)=> $b['score'] <=> $a['score']);

/* 4) 週×食事に割り当て（足りなければ重複許容で2周目） */
$needSlots = [];
foreach ($days as $d) foreach ($meals as $m) $needSlots[] = [$d,$m];
$assign = [];
$used = [];
$idx = 0;
for ($round=0; $round<2 && count($assign) < count($needSlots); $round++) {
  foreach ($needSlots as [$d,$m]) {
    if (isset($assign["$d|$m"])) continue;
    // 1周目は未使用のみ、2周目は重複許容
    while ($idx < count($candidates)) {
      $cand = $candidates[$idx++];
      if ($round===0 && isset($used[$cand['id']])) continue;
      $assign["$d|$m"] = ['date'=>$d,'meal'=>$m,'recipe_id'=>$cand['id'],'servings'=>$servings];
      $used[$cand['id']] = true;
      break;
    }
    // 候補尽きたらリセットして続行
    if (!isset($assign["$d|$m"]) && $idx>=count($candidates)) { $idx = 0; }
  }
}

/* 5) DBに保存（未割当スロットはスキップ） */
$pdo->beginTransaction();
try {
  $up = $pdo->prepare(
    "INSERT INTO meal_plans (user_id,date,meal,recipe_id,servings)
     VALUES (?,?,?,?,?)
     ON DUPLICATE KEY UPDATE recipe_id=VALUES(recipe_id), servings=VALUES(servings)"
  );
  $count = 0;
  foreach ($assign as $slot) {
    if (empty($slot['recipe_id'])) continue;
    $up->execute([$uid, $slot['date'], $slot['meal'], $slot['recipe_id'], $slot['servings']]);
    $count += $up->rowCount();
  }
  $pdo->commit();
  echo json_encode(['ok'=>true,'saved'=>$count,'assigned'=>array_values($assign)]);
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
