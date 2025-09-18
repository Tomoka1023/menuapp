<?php
require __DIR__.'/../../app/bootstrap.php';
require_login();
require __DIR__.'/../../app/db.php';

$title = '在庫を追加';
$uid = current_user_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $name = trim($_POST['name'] ?? '');
  $unit = trim($_POST['unit'] ?? '');
  $qty  = (float)($_POST['quantity'] ?? 0);

  if ($name === '') $errors[] = '食材名を入力してください。';
  if ($unit === '') $unit = '個';

  if (!$errors) {
    try {
      // uq_user_name_unit(user_id,name,unit) を使ってUPSERT（同じものは加算）
      $sql = "INSERT INTO pantry_items (user_id,name,unit,quantity)
              VALUES (?,?,?,?)
              ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
      $st = $pdo->prepare($sql);
      $st->execute([$uid, $name, $unit, $qty]);
      flash('在庫を追加しました');
      redirect(BASE_URL.'/pantry/');
    } catch (Throwable $e) {
      $errors[] = '保存に失敗: '.$e->getMessage();
    }
  }
}

require __DIR__.'/../../templates/_header.php';
?>
<h1>在庫を追加</h1>

<?php if ($errors): ?>
  <div class="alert error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="auth-form">
  <?= csrf_field() ?>
  <label>食材名
    <input type="text" name="name" required placeholder="玉ねぎ" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
  </label>
  <label>単位
    <input type="text" name="unit" placeholder="g, ml, 個 など" value="<?= htmlspecialchars($_POST['unit'] ?? '個') ?>">
  </label>
  <label>数量
    <input type="number" step="0.01" name="quantity" min="0" value="<?= htmlspecialchars($_POST['quantity'] ?? '1') ?>">
  </label>
  <button type="submit" class="btn">追加</button>
  <a href="<?= BASE_URL ?>/pantry/">戻る</a>
</form>

<?php require __DIR__.'/../../templates/_footer.php'; ?>
