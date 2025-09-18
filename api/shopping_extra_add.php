<?php
require __DIR__.'/../app/bootstrap.php'; require_login(); require __DIR__.'/../app/db.php';
header('Content-Type: application/json');

$uid = current_user_id();
$in = json_decode(file_get_contents('php://input'), true);
$name = trim($in['name'] ?? '');
$unit = trim($in['unit'] ?? '個');
$qty  = (float)($in['quantity'] ?? 0);
$week = $in['week_start'] ?? date('Y-m-d', strtotime('monday this week'));

if ($name==='' || $qty<=0) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'invalid']); exit; }

$st = $pdo->prepare("INSERT INTO shopping_extras (user_id, week_start, name, unit, quantity) VALUES (?,?,?,?,?)");
$st->execute([$uid, $week, $name, $unit, $qty]);
echo json_encode(['ok'=>true, 'id'=>(int)$pdo->lastInsertId()]);
