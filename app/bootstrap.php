<?php
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Tokyo');

/* .env 読み込み（超シンプル）*/
$env = __DIR__ . '/../.env';
if (is_readable($env)) {
  foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '=') !== false && $line[0] !== '#') {
      [$k,$v] = array_map('trim', explode('=', $line, 2));
      $_ENV[$k] = $v;
    }
  }
}
require __DIR__.'/config.php';
require __DIR__.'/helpers.php';
require __DIR__.'/csrf.php';
require __DIR__.'/auth.php';
