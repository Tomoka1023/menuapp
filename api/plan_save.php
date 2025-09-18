<?php
require_once __DIR__.'/../app/bootstrap.php';
require_login();
require __DIR__.'/../app/db.php';
header('Content-Type: application/json');

$uid = current_user_id();
$data = json_decode(file_get_contents('php://input'), true);
$pdo->beginTransaction();
try {
  $up = $pdo->prepare(
    "INSERT INTO meal_plans (user_id,date,meal,recipe_id,servings)
     VALUES (?,?,?,?,?)
     ON DUPLICATE KEY UPDATE recipe_id=VALUES(recipe_id), servings=VALUES(servings)"
  );
  foreach ($data as $p) {
    $up->execute([$uid, $p['date'], $p['meal'], (int)$p['recipe_id'], (int)$p['servings']]);
  }
  $pdo->commit();
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
