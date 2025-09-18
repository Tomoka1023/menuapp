<?php require_once __DIR__ . '/../app/bootstrap.php'; ?>
<?php $title = $title ?? '献立アプリ'; ?>
<!doctype html>
<html lang="ja">
<head>
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/icons/icon-192.png">
  <meta name="theme-color" content="#ff7eb9">
  <link rel="icon" href="<?= BASE_URL ?>/icons/favicon.png" type="image/png">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="manifest" href="<?= BASE_URL ?>/manifest.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=DotGothic16&family=Hachi+Maru+Pop&family=Hina+Mincho&family=Kaisei+Decol&family=Kiwi+Maru&family=M+PLUS+Rounded+1c&family=Yomogi&display=swap" rel="stylesheet">
  <script>window.BASE_URL = "<?= BASE_URL ?>";</script>
</head>
<body>

<!-- ▼ 共通ヘッダー（CSSの .header / .header__inner に合わせる） -->
<header class="site-header">
  <div class="header">
    <div class="header__inner">
      <a class="brand" href="<?= current_user_id() ? BASE_URL.'/week.php' : BASE_URL.'/index.php' ?>">Menu App</a>

      <button class="nav-toggle" aria-label="メニューを開閉"
              aria-controls="site-nav" aria-expanded="false">
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
      </button>

      <nav id="site-nav" class="nav">
        <a href="<?= BASE_URL ?>/week.php">週の献立</a>
        <a href="<?= BASE_URL ?>/recipes/">レシピ</a>
        <a href="<?= BASE_URL ?>/shopping_list.php">買い物リスト</a>
        <a href="<?= BASE_URL ?>/pantry/">パントリー</a>
        <?php if (current_user_id()): ?>
          <a href="<?= BASE_URL ?>/logout.php">ログアウト</a>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</header>
<!-- ▲ 共通ヘッダー -->

<script>
(function(){
  const header = document.querySelector('.header');
  const btn = header?.querySelector('.nav-toggle');
  const nav = header?.querySelector('#site-nav');
  if(!btn || !nav) return;
  btn.addEventListener('click', () => {
    const opened = header.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', opened ? 'true' : 'false');
  });
  // ナビリンク押下で閉じる
  nav.addEventListener('click', (e)=>{
    if(e.target.tagName === 'A'){ header.classList.remove('is-open'); btn.setAttribute('aria-expanded','false'); }
  });
})();
</script>

<main class="container">
  <?php include __DIR__ . '/_flash.php'; ?>
