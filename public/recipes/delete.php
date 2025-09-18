<?php
require __DIR__.'/../../app/bootstrap.php';
require_login();
require __DIR__.'/../../app/db.php';

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$uid = current_user_id();

$pdo->beginTransaction();
try {
  // 週の献立から、このレシピの割当を先に削除
  $pdo->prepare("DELETE FROM meal_plans WHERE user_id=? AND recipe_id=?")
      ->execute([$uid, $id]);

  // レシピの材料を削除
  $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id=?")
      ->execute([$id]);

  // レシピ本体を削除（他人のを消せないよう user_id を条件に）
  $st = $pdo->prepare("DELETE FROM recipes WHERE id=? AND user_id=?");
  $st->execute([$id, $uid]);

  $pdo->commit();
  flash('レシピを削除しました');
  redirect(BASE_URL.'/recipes/');
} catch (Throwable $e) {
  $pdo->rollBack();
  flash('削除に失敗: '.$e->getMessage(), 'error');
  redirect(BASE_URL.'/recipes/');
}
