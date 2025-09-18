<?php
$title='レシピ編集';
require __DIR__.'/../../templates/_header.php';
require_login();
require __DIR__.'/../../app/db.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM recipes WHERE id=? AND user_id=?");
$st->execute([$id, current_user_id()]);
$recipe = $st->fetch();
if(!$recipe){ http_response_code(404); exit('Not found'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf();
  $titleV = trim($_POST['title']??'');
  $servV  = max(1, (int)($_POST['servings']??2));
  $instV  = trim($_POST['instructions']??'');
  $names = $_POST['ing_name'] ?? [];
  $qtys  = $_POST['ing_qty'] ?? [];
  $units = $_POST['ing_unit'] ?? [];
  $notes = $_POST['ing_note'] ?? [];

  $pdo->beginTransaction();
  try{
    $up = $pdo->prepare("UPDATE recipes SET title=?, servings=?, instructions=? WHERE id=? AND user_id=?");
    $up->execute([$titleV,$servV,$instV,$id,current_user_id()]);
    $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id=?")->execute([$id]);
    $ing = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id,name,quantity,unit,note) VALUES (?,?,?,?,?)");
    for($i=0;$i<count($names);$i++){
      $n = trim($names[$i]); if($n==='') continue;
      $q = (float)$qtys[$i]; $u=trim($units[$i]?:'個'); $no=trim($notes[$i]??'');
      $ing->execute([$id,$n,$q,$u,$no]);
    }
    $pdo->commit();
    flash('更新しました');
    redirect('show.php?id='.$id);
  }catch(Throwable $e){ $pdo->rollBack(); flash('更新失敗: '.$e->getMessage(),'error'); }
}

/* 材料取得 */
$ings = $pdo->prepare("SELECT * FROM recipe_ingredients WHERE recipe_id=? ORDER BY id");
$ings->execute([$id]);
$ingredients = $ings->fetchAll();
?>
<h1>レシピ編集</h1>
<form method="post" class="recipe-form">
  <?=csrf_field()?>
  <label>タイトル<input type="text" name="title" required value="<?=htmlspecialchars($recipe['title'])?>"></label>
  <label>基準人数<input type="number" name="servings" min="1" value="<?=htmlspecialchars($recipe['servings'])?>"> 人分</label>
  <label>作り方<textarea name="instructions" rows="6"><?=htmlspecialchars($recipe['instructions'])?></textarea></label>

  <h2>材料</h2>
<table id="ing-table">
  <thead>
    <tr>
      <th>食材名</th>
      <th>量</th>
      <th>単位</th>
      <th>メモ</th>
      <th></th>
    </tr>
  </thead>

  <tbody id="ing-body">
    <?php if ($ingredients): ?>
      <?php foreach ($ingredients as $row): ?>
        <tr>
          <td data-label="食材名">
            <input type="text" name="ing_name[]" value="<?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES) ?>">
          </td>
          <td data-label="量">
            <input type="number" step="0.01" name="ing_qty[]" value="<?= htmlspecialchars((string)($row['quantity'] ?? ''), ENT_QUOTES) ?>">
          </td>
          <td data-label="単位">
            <input type="text" name="ing_unit[]" value="<?= htmlspecialchars($row['unit'] ?? '', ENT_QUOTES) ?>">
          </td>
          <td data-label="メモ">
            <input type="text" name="ing_note[]" value="<?= htmlspecialchars($row['note'] ?? '', ENT_QUOTES) ?>">
          </td>
          <td class="right" data-label="">
            <button type="button" class="btn small danger" onclick="this.closest('tr').remove()">削除</button>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- 材料が0件のときは空行を1つ表示 -->
      <tr>
        <td data-label="食材名"><input type="text" name="ing_name[]" value=""></td>
        <td data-label="量"><input type="number" step="0.01" name="ing_qty[]" value=""></td>
        <td data-label="単位"><input type="text" name="ing_unit[]" value=""></td>
        <td data-label="メモ"><input type="text" name="ing_note[]" value=""></td>
        <td class="right" data-label="">
          <button type="button" class="btn small danger" onclick="this.closest('tr').remove()">削除</button>
        </td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<button type="button" id="add-ing" class="btn">＋ 行を追加</button>


  <div style="margin-top:16px">
    <button type="submit" class="btn">保存</button>
    <a href="index.php">一覧へ</a>
  </div>
</form>

<script>
  window.__ING_PRELOAD__ = <?=json_encode($ingredients, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
</script>
<script src="../assets/js/recipe_form.js"></script>
<?php require __DIR__.'/../../templates/_footer.php'; ?>
