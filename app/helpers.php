<?php
function redirect(string $path): void {
  header('Location: '.$path); exit;
}
function flash(string $msg, string $type='info'): void {
  $_SESSION['flash'][] = ['m'=>$msg,'t'=>$type];
}
function flashes(): array {
  $f = $_SESSION['flash'] ?? [];
  unset($_SESSION['flash']);
  return $f;
}

function h($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
