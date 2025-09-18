<?php
function current_user_id(): ?int {
  return $_SESSION['user_id'] ?? null;
}
function require_login(): void {
  if (!current_user_id()) {
    header('Location: /login.php'); exit;
  }
}
