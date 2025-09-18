<?php
require __DIR__.'/../../app/bootstrap.php';
require_login();
require __DIR__.'/../../app/db.php';

$title = 'パントリー';
$uid = current_user_id();

/* 保存処理（数量の一括更新） */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $items = $_POST['items'] ?? [];
  $pdo->beginTransaction();
  try {
    $up = $pdo->prepare("UPDATE pantry_items SET quantity=? WHERE id=? AND user_id=?");
    foreach ($items as $id => $row) {
      $q = (float)($row['quantity'] ?? 0);
      $up->execute([$q, (int)$id, $uid]);
    }
    $pdo->commit();
    flash('在庫を更新しました。');
  } catch (Throwable $e) {
    $pdo->rollBack();
    flash('更新に失敗: '.$e->getMessage(), 'error');
  }
  redirect(BASE_URL.'/pantry/');
}

/* 取得 */
$st = $pdo->prepare("SELECT id,name,unit,quantity FROM pantry_items WHERE user_id=? ORDER BY name,unit");
$st->execute([$uid]);
$rows = $st->fetchAll();

require __DIR__.'/../../templates/_header.php';
?>
<h1>パントリー（在庫）</h1>

<p><a class="btn" href="<?= BASE_URL ?>/pantry/new.php">＋ 在庫を追加</a></p>

<form method="post" class="pantry-form">
  <?= csrf_field() ?>
  <table class="list">
    <thead>
      <tr><th>食材名</th><th>単位</th><th style="width:10em;">在庫量</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['unit']) ?></td>
          <td>
            <input type="number" step="0.01" name="items[<?= $r['id'] ?>][quantity]" value="<?= htmlspecialchars($r['quantity']) ?>" style="width:8em">
          </td>
          <td>
          <button type="submit"
                  class="linklike"
                  form="del-<?= $r['id'] ?>"
                  formaction="<?= BASE_URL ?>/pantry/delete.php"
                  formmethod="post"
                  onclick="return confirm('削除しますか？');">削除</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="4">まだ在庫がありません。「＋ 在庫を追加」から登録してください。</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <div style="margin-top:12px">
    <button type="submit" class="btn">在庫量を保存</button>
  </div>
</form>

<?php foreach ($rows as $r): ?>
  <form id="del-<?= $r['id'] ?>" method="post" action="<?= BASE_URL ?>/pantry/delete.php" style="display:none">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
  </form>
<?php endforeach; ?>


<?php require __DIR__.'/../../templates/_footer.php'; ?>
