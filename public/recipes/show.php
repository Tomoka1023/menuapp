<?php
$title='レシピ詳細';
require __DIR__.'/../../templates/_header.php';
require_login();
require __DIR__.'/../../app/db.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM recipes WHERE id=? AND user_id=?");
$st->execute([$id, current_user_id()]);
$r = $st->fetch();
if(!$r){ http_response_code(404); exit('Not found'); }

$ings = $pdo->prepare("SELECT name,quantity,unit,note FROM recipe_ingredients WHERE recipe_id=? ORDER BY id");
$ings->execute([$id]);
?>
<h1><?=htmlspecialchars($r['title'])?></h1>
<p>基準人数：<?=htmlspecialchars($r['servings'])?>人分</p>

<h2>材料</h2>
<ul>
  <?php foreach($ings as $i): ?>
    <li>
      <?= h($i['name']) ?> …… <?= h(number_format((float)$i['quantity'], 2)) ?>
      <?= h($i['unit'] ?? '') ?>
      <?= h($i['note'] ?? '') ?>
    </li>
  <?php endforeach; ?>
</ul>

<h2>作り方</h2>
<pre><?= h($r['instructions'] ?? '') ?></pre>

<p>
  <a href="edit.php?id=<?=$id?>">編集</a> /
  <a href="delete.php?id=<?=$id?>" onclick="return confirm('削除しますか？')">削除</a> /
  <a href="index.php">一覧へ</a>
</p>
<?php require __DIR__.'/../../templates/_footer.php'; ?>
