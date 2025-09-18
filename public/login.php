<?php
$title = 'ログイン';
require __DIR__.'/../templates/_header.php';
require __DIR__.'/../app/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  if ($email === '' || $pass === '') {
    $errors[] = 'メールアドレスとパスワードを入力してください。';
  } else {
    $st = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();
    if (!$user || !password_verify($pass, $user['password_hash'])) {
      // 攻撃者に情報を与えないようメッセージは共通に
      $errors[] = 'メールアドレスまたはパスワードが違います。';
    } else {
      // セッション固定化対策
      session_regenerate_id(true);
      $_SESSION['user_id'] = (int)$user['id'];
      flash('ログインしました。');
      redirect('week.php');
    }
  }
}
?>
<h1>ログイン</h1>

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
    パスワード
    <input type="password" name="password" required>
  </label>
  <button type="submit" class="btn">ログイン</button>
</form>

<p>はじめての方は <a href="register.php" class="btn">新規登録</a></p>

<?php require __DIR__.'/../templates/_footer.php'; ?>
