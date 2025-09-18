<?php
require __DIR__.'/../../app/bootstrap.php';
require_login();
require __DIR__.'/../../app/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); exit('Method Not Allowed');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$uid = current_user_id();

$st = $pdo->prepare("DELETE FROM pantry_items WHERE id=? AND user_id=?");
$st->execute([$id, $uid]);

flash('削除しました');
redirect(BASE_URL.'/pantry/');
