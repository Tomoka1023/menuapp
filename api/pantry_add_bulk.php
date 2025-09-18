<?php
// api/pantry_add_bulk.php
require __DIR__.'/../app/bootstrap.php';
require_login();
require __DIR__.'/../app/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']); exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'invalid payload']); exit;
}
/*
  期待するpayload:
  [
    {"name":"玉ねぎ","unit":"個","quantity":2},
    {"name":"牛乳","unit":"ml","quantity":500}
  ]
*/

$uid = current_user_id();
try {
  $pdo->beginTransaction();
  $sql = "INSERT INTO pantry_items (user_id,name,unit,quantity)
          VALUES (?,?,?,?)
          ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
  $st = $pdo->prepare($sql);
  $affected = 0;
  foreach ($payload as $it) {
    $name = trim($it['name'] ?? '');
    $unit = trim($it['unit'] ?? '');
    $qty  = (float)($it['quantity'] ?? 0);
    if ($name === '' || $unit === '' || $qty <= 0) continue;
    $st->execute([$uid, $name, $unit, $qty]);
    $affected += $st->rowCount(); // 1 か 2（UPSERT）
  }
  $pdo->commit();
  echo json_encode(['ok'=>true,'affected'=>$affected]);
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
