// api/shopping_extra_delete.php
<?php
require __DIR__.'/../app/bootstrap.php'; require_login(); require __DIR__.'/../app/db.php';
header('Content-Type: application/json');
$uid=current_user_id();
$in=json_decode(file_get_contents('php://input'),true);
$id=(int)($in['id']??0);
$st=$pdo->prepare("DELETE FROM shopping_extras WHERE id=? AND user_id=?");
$st->execute([$id,$uid]);
echo json_encode(['ok'=>true]);
