<?php
// 1) まずブートして関数群を読み込む
require __DIR__ . '/../../app/bootstrap.php';
// 2) ログインチェック（HTMLを出す前）
require_login();
// 3) DB接続
require __DIR__ . '/../../app/db.php';

$title = 'レシピ一覧';

// 検索 & 取得
$q = trim($_GET['q'] ?? '');
$params = [current_user_id()];
$sql = "SELECT id, title, servings, created_at FROM recipes WHERE user_id=? ";
if ($q !== '') { $sql .= "AND title LIKE ? "; $params[] = '%'.$q.'%'; }
$sql .= "ORDER BY id DESC";
$st = $pdo->prepare($sql);
$st->execute($params);

// 4) ここからHTML開始（_header.php が <html>〜<body> を出す）
require __DIR__ . '/../../templates/_header.php';
?>

<h1>レシピ</h1>

<form method="get" class="search" style="margin:10px 0;">
  <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="レシピ名で検索">
  <button class="btn">検索</button>
  <a href="<?= BASE_URL ?>/recipes/new.php" class="btn" style="font-size:13px;">新規作成</a>
  <button id="suggest-recipe" type="button" class="btn">🍳 おすすめレシピを追加</button>
</form>

<table class="list">
  <thead><tr><th>ID</th><th>タイトル</th><th>基準人数</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($st as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><a href="<?= BASE_URL ?>/recipes/show.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
      <td><?= htmlspecialchars($r['servings']) ?>人分</td>
      <td>
        <a href="<?= BASE_URL ?>/recipes/edit.php?id=<?= $r['id'] ?>">編集</a> /
        <a href="<?= BASE_URL ?>/recipes/delete.php?id=<?= $r['id'] ?>" onclick="return confirm('削除しますか？')">削除</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<script>
document.getElementById('suggest-recipe')?.addEventListener('click', async ()=>{
  if (!confirm('サンプルレシピを1件追加します。よろしいですか？')) return;
  const url = window.BASE_URL.replace(/\/public$/, '') + '/api/recipe_suggest.php';
  try {
    const res = await fetch(url, { method: 'POST', credentials: 'same-origin' });
    const j = await res.json();
    if (!j.ok) throw new Error(j.error || 'unknown');
    alert(`「${j.title}」を追加しました！`);
    location.reload();
  } catch (e) {
    console.error(e);
    alert('追加に失敗しました: ' + e.message);
  }
});
</script>


<?php require __DIR__ . '/../../templates/_footer.php'; ?>
