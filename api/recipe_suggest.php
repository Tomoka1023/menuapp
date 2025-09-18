<?php
// api/recipe_suggest.php
require __DIR__.'/../app/bootstrap.php';
require_login();
require __DIR__.'/../app/db.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']); exit;
}

// JSONから読み込み
$jsonFile = __DIR__ . '/../app/data/recipes_seed.json';
$templates = json_decode(file_get_contents($jsonFile), true);
if (!$templates) {
  echo json_encode(['ok'=>false,'error'=>'レシピデータが読み込めませんでした']); exit;
}

$uid = current_user_id();

// 既存タイトルを取得（このユーザー分）
$stExists = $pdo->prepare("SELECT title FROM recipes WHERE user_id=?");
$stExists->execute([$uid]);
$exists = array_flip(array_column($stExists->fetchAll(PDO::FETCH_ASSOC), 'title'));

// 候補から“未登録”だけを抽出
$candidates = array_values(array_filter($templates, function($t) use ($exists){
  return empty($exists[$t['title']]);   // 同名が無いものだけ
}));

if (!$candidates) {
  echo json_encode(['ok'=>false,'error'=>'すべて追加済みです']); exit;
}

// ランダムに1件だけ追加
$pick = $candidates[array_rand($candidates)];
$title = $pick['title']; // ← 連番付与ロジックは廃止（重複排除するため）

// 追加
$st = $pdo->prepare("INSERT INTO recipes (user_id,title,servings,tags,instructions) VALUES (?,?,?,?,?)");
$st->execute([$uid, $title, $pick['servings'], $pick['tags'] ?? null, $pick['instructions'] ?? null]);
$rid = (int)$pdo->lastInsertId();

$stIng = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id,name,quantity,unit) VALUES (?,?,?,?)");
foreach ($pick['ingredients'] as $i) {
  $stIng->execute([$rid, $i['name'], $i['quantity'], $i['unit']]);
}

echo json_encode(['ok'=>true,'recipe_id'=>$rid,'title'=>$title]);
