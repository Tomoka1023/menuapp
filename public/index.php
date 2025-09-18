<?php
require __DIR__.'/../app/bootstrap.php';

if (current_user_id()) {
    // ログイン済みなら週の献立ページへ
    header('Location: ' . BASE_URL . '/week.php');
    exit;
}

$title = '献立アプリ';
require __DIR__.'/../templates/_header.php';
?>
<h1>🍳 献立アプリ</h1>

<p>
  このアプリは、1週間分の献立を簡単に立てて、<br>
  自動で買い物リストを作成できる便利ツールです。
</p>

<section class="features">
  <h2>✨ 主な機能</h2>
  <ul class="features">
    <li>1週間の朝・昼・晩の献立管理</li>
    <li>レシピ登録＆材料リスト</li>
    <li>まとめて買い物リスト生成</li>
    <li>パントリー管理（家にある材料を差し引き）</li>
  </ul>
</section>

<section class="cta">
  <p>さっそく使ってみましょう！</p>
  <a href="register.php" class="btn primary">新規登録</a>
  <a href="login.php" class="btn">ログイン</a>
</section>

<?php require __DIR__.'/../templates/_footer.php'; ?>
