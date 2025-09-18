<?php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '8889';           // ← ここ重要（MAMPは8889）
$name = $_ENV['DB_NAME'] ?? 'xs279861_menuapp';
$user = $_ENV['DB_USER'] ?? 'xs279861_masabou';
$pass = $_ENV['DB_PASS'] ?? 'masabouadmin';           // ← デフォルトは root にしておく

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
$pdo = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
