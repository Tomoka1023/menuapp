<?php
// public/recipes/new.php
$title = 'レシピ作成';
require __DIR__ . '/../../app/bootstrap.php';
require_login();
require __DIR__ . '/../../app/db.php';

// 画面表示のための初期値（POSTが無ければ空1行）
$ingredients = $_POST['ingredients'] ?? [[]];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $titleV = trim($_POST['title'] ?? '');
  $servV  = max(1, (int)($_POST['servings'] ?? 2));
  $instV  = trim($_POST['instructions'] ?? '');

  // 互換: 旧フォーマット(ing_name等)で来た場合はingredients配列に変換
  if (empty($_POST['ingredients'])) {
    $names = $_POST['ing_name'] ?? [];
    $qtys  = $_POST['ing_qty']  ?? [];
    $units = $_POST['ing_unit'] ?? [];
    $notes = $_POST['ing_note'] ?? [];
    $ingredients = [];
    $n = max(count($names), count($qtys), count($units), count($notes));
    for ($i = 0; $i < $n; $i++) {
      $ingredients[] = [
        'name'     => $names[$i] ?? '',
        'quantity' => $qtys[$i] ?? '',
        'unit'     => $units[$i] ?? '',
        'note'     => $notes[$i] ?? '',
      ];
    }
  } else {
    $ingredients = $_POST['ingredients'];
  }

  if ($titleV === '') $errors[] = 'タイトルを入力してください。';

  if (!$errors) {
    $pdo->beginTransaction();
    try {
      // レシピ本体
      $ins = $pdo->prepare("INSERT INTO recipes (user_id, title, servings, instructions) VALUES (?, ?, ?, ?)");
      $ins->execute([current_user_id(), $titleV, $servV, $instV]);
      $rid = (int)$pdo->lastInsertId();

      // 材料
      $ing = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, name, quantity, unit, note) VALUES (?, ?, ?, ?, ?)");
      foreach ($ingredients as $row) {
        $name = trim($row['name'] ?? '');
        if ($name === '') continue;
        $qty  = (float)($row['quantity'] ?? 0);
        $unit = trim($row['unit'] ?? '');
        if ($unit === '') $unit = '個';
        $note = trim($row['note'] ?? '');
        $ing->execute([$rid, $name, $qty, $unit, $note]);
      }

      $pdo->commit();
      flash('レシピを作成しました');
      redirect('show.php?id=' . $rid);
    } catch (Throwable $e) {
      $pdo->rollBack();
      $errors[] = '保存に失敗: ' . $e->getMessage();
    }
  }
}

// ここからHTML
require __DIR__ . '/../../templates/_header.php';
?>
<h1>レシピ作成</h1>

<?php if ($errors): ?>
  <div class="alert error">
    <ul class="m-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="recipe-form">
  <?= csrf_field() ?>

  <div class="form-row">
    <label for="title">タイトル</label>
    <input id="title" type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
  </div>

  <div class="form-row">
    <label for="serv">基準人数</label>
    <input id="serv" type="number" name="servings" min="1" value="<?= htmlspecialchars($_POST['servings'] ?? 2) ?>">
  </div>

  <div class="form-row">
    <label for="inst">作り方</label>
    <textarea id="inst" name="instructions" rows="6"><?= htmlspecialchars($_POST['instructions'] ?? '') ?></textarea>
  </div>

  <h2 class="mt-2">材料</h2>

  <div class="ing-wrap">
    <!-- ヘッダ -->
    <div class="ing-grid ing-head">
      <div>食材名</div><div>量</div><div>単位</div><div>メモ</div><div></div>
    </div>

    <!-- 行リスト -->
    <div id="ing-rows">
      <?php foreach ($ingredients as $i => $row): ?>
        <div class="ing-grid ing-row">
          <input type="text"   name="ingredients[<?= $i ?>][name]"     value="<?= htmlspecialchars($row['name'] ?? '') ?>">
          <input type="number" step="0.01" name="ingredients[<?= $i ?>][quantity]" value="<?= htmlspecialchars($row['quantity'] ?? '') ?>">
          <input type="text"   name="ingredients[<?= $i ?>][unit]"     value="<?= htmlspecialchars($row['unit'] ?? '') ?>">
          <input type="text"   name="ingredients[<?= $i ?>][note]"     value="<?= htmlspecialchars($row['note'] ?? '') ?>">
          <button type="button" class="btn small danger" onclick="removeRow(this)">削除</button>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="ing-actions">
      <button type="button" id="add-row" class="btn small">＋ 行を追加</button>
    </div>
  </div>

  <div style="margin-top:16px">
    <button type="submit" class="btn">保存</button>
    <a href="<?= BASE_URL ?>/recipes/">一覧へ</a>
  </div>
</form>

<script>
// 行追加・削除
(function(){
  const rows = document.getElementById('ing-rows');
  document.getElementById('add-row')?.addEventListener('click', () => {
    const idx = rows.querySelectorAll('.ing-row').length;
    const div = document.createElement('div');
    div.className = 'ing-grid ing-row';
    div.innerHTML = `
      <input type="text"   name="ingredients[${idx}][name]"     value="">
      <input type="number" step="0.01" name="ingredients[${idx}][quantity]" value="">
      <input type="text"   name="ingredients[${idx}][unit]"     value="">
      <input type="text"   name="ingredients[${idx}][note]"     value="">
      <button type="button" class="btn small danger" onclick="removeRow(this)">削除</button>
    `;
    rows.appendChild(div);
  });
})();
function removeRow(btn){
  const row = btn.closest('.ing-row');
  if(row) row.remove();
}
</script>

<?php require __DIR__ . '/../../templates/_footer.php'; ?>
