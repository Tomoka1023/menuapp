<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../app/bootstrap.php';  // ← まずブート
require_login();                          // ← ログインチェックは最初に
require __DIR__.'/../app/db.php';

$title = '週の献立';
require __DIR__.'/../templates/_header.php'; // ← ここからHTML開始

$start = $_GET['start'] ?? date('Y-m-d', strtotime('monday this week'));
$startDt = new DateTime($start);
$days = [];
for ($i=0; $i<7; $i++) { $d = clone $startDt; $d->modify("+$i day"); $days[] = $d->format('Y-m-d'); }

$in = implode(',', array_fill(0, count($days), '?'));
$sql = "SELECT mp.date, mp.meal, mp.servings, r.id rid, r.title
        FROM meal_plans mp
        JOIN recipes r ON r.id = mp.recipe_id
        WHERE mp.user_id=? AND mp.date IN ($in)";
$st = $pdo->prepare($sql);
$st->execute(array_merge([current_user_id()], $days));
$plans = [];
foreach ($st as $row) { $plans[$row['date']][$row['meal']] = $row; }

$meals = ['breakfast'=>'朝','lunch'=>'昼','dinner'=>'夜'];

$all = $pdo->prepare("SELECT id,title FROM recipes WHERE user_id=? ORDER BY id DESC");
$all->execute([current_user_id()]);
$allRecipes = $all->fetchAll();
?>

<h1>週の献立</h1>
<div class="week-nav">
  <a href="?start=<?=(clone $startDt)->modify('-7 day')->format('Y-m-d')?>">←前週</a>
  <strong><?=htmlspecialchars($start)?> 週</strong>
  <a href="?start=<?=(clone $startDt)->modify('+7 day')->format('Y-m-d')?>">次週→</a>
</div>

<div class="auto-box" style="margin:12px 0;">
  <button id="auto-open" class="btn">✨ 自動で一週間の献立を作る</button>
</div>

<!-- 自動献立モーダル -->
<div id="auto-modal" class="modal" hidden>
  <div class="modal__backdrop" data-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="auto-title">
    <h2 id="auto-title">自動献立の条件</h2>

    <form id="auto-form">
      <?= csrf_field() ?>
      <div class="grid">
        <label>週の開始日
          <input type="date" name="start" value="<?= htmlspecialchars($start) ?>">
        </label>

        <label>1食あたりの人数
          <input type="number" name="servings" min="1" value="2" />
        </label>
      </div>

      <fieldset>
        <legend>対象の食事</legend>
        <label><input type="checkbox" name="meals[]" value="breakfast" checked> 朝</label>
        <label><input type="checkbox" name="meals[]" value="lunch" checked> 昼</label>
        <label><input type="checkbox" name="meals[]" value="dinner" checked> 夜</label>
      </fieldset>

      <label>NG食材（カンマ区切り）
        <input type="text" name="exclude_ingredients" placeholder="卵, 牛乳">
      </label>

      <label>優先タグ（カンマ区切り）
        <input type="text" name="prefer_tags" placeholder="時短, 作り置き">
      </label>

      <label>同じレシピを再使用しない日数
        <input type="number" name="no_repeat_days" min="0" value="7">
      </label>

      <div class="modal__actions">
        <button type="button" class="btn" data-close>キャンセル</button>
        <button type="submit" class="btn primary">この条件で自動作成</button>
      </div>
    </form>
  </div>
</div>

<table class="week-table">
  <thead>
    <tr>
      <th>日付</th>
      <?php foreach ($meals as $key => $label): ?>
        <th><?= $label ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($days as $d): ?>
      <tr data-date="<?= $d ?>" class="<?= $isToday ? 'is-today' : '' ?>">
        <th><?= htmlspecialchars($d) ?></th>
        <?php foreach ($meals as $key => $label):
          $cell = $plans[$d][$key] ?? null;
          $rid  = $cell['rid'] ?? 0;
          $serv = $cell['servings'] ?? 2; ?>

          <td data-meal="<?= $key ?>">
            <div class="week-cell">
              <select class="recipe-select" name="recipes[<?=$d?>][<?=$key?>]" 
              title="<?= htmlspecialchars($cell['title'] ?? '') ?>">
                <option value="0">未設定</option>
                <?php foreach ($allRecipes as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= ($rid==$r['id'] ? 'selected' : '') ?>>
                    <?= htmlspecialchars($r['title']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="servings-wrap">
                <input type="number" class="servings" value="<?= $serv ?>" min="1" style="width:4em">
                <span>人分</span>
              </div>
              <div class="recipe-label"></div>
            </div>
          </td>

        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>


<button id="save-plan" class="btn">この週を保存</button>

<?php require __DIR__.'/../templates/_footer.php'; ?>
