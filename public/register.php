<?php
$title = '新規登録';
require __DIR__.'/../templates/_header.php';
require __DIR__.'/../app/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $pass2 = $_POST['password_confirm'] ?? '';

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '正しいメールアドレスを入力してください。';
  }
  if (strlen($pass) < 8) {
    $errors[] = 'パスワードは8文字以上にしてください。';
  }
  if ($pass !== $pass2) {
    $errors[] = '確認用パスワードが一致しません。';
  }

  if (!$errors) {
    // 既存チェック
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) {
      $errors[] = 'このメールアドレスは既に登録されています。';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $ins = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
      $ins->execute([$email, $hash]);
      $_SESSION['user_id'] = (int)$pdo->lastInsertId();
      flash('登録しました。ようこそ！');
      redirect('week.php');
    }
  }
}
?>
<h1>新規登録</h1>

<?php if ($errors): ?>
  <div class="alert error">
    <ul>
        <?php foreach ($errors as $e): ?>
            <li><?=htmlspecialchars($e)?>
            </li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" autocomplete="off" class="auth-form">
  <?= csrf_field() ?>
  <label>
    メールアドレス
    <input type="email" name="email" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
  </label>
  <label>
    パスワード（8文字以上）
    <input type="password" name="password" required minlength="8">
  </label>
  <label>
    パスワード（確認）
    <input type="password" name="password_confirm" required minlength="8">
  </label>
  <button type="submit" class="btn">登録する</button>
</form>

<p>アカウントをお持ちの方は <a href="login.php" class="btn">ログイン</a></p>

<?php require __DIR__.'/../templates/_footer.php'; ?>
