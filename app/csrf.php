<?php
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}
function csrf_field(): string {
  return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(),ENT_QUOTES).'">';
}
function verify_csrf(): void {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(400); exit('Bad CSRF');
  }
}
