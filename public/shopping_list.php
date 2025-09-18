<?php
require __DIR__.'/../app/bootstrap.php';
require_login();
require __DIR__.'/../app/db.php';

$title = '買い物リスト';
$uid = current_user_id();

// 週開始日（GETパラメータ）
$start = $_GET['start'] ?? date('Y-m-d', strtotime('monday this week'));
$startDt = new DateTime($start);
$endDt   = (clone $startDt)->modify('+6 day');

// この週の献立を取得
$sql = "SELECT mp.date, mp.meal, mp.servings, r.id rid, r.servings base_servings
        FROM meal_plans mp
        JOIN recipes r ON r.id=mp.recipe_id
        WHERE mp.user_id=? AND mp.date BETWEEN ? AND ?";
$st = $pdo->prepare($sql);
$st->execute([$uid, $startDt->format('Y-m-d'), $endDt->format('Y-m-d')]);
$plans = $st->fetchAll();

// 週の自分アイテムを取得して、表に混ぜる用の配列へ
$extras = $pdo->prepare("SELECT id, name, unit, quantity FROM shopping_extras WHERE user_id=? AND week_start=? ORDER BY id");
$extras->execute([$uid, $startDt->format('Y-m-d')]);
$extraRows = $extras->fetchAll();

// レシピIDごとに人数比率を計算
$ingredients = [];
$ingSt = $pdo->prepare("SELECT name, quantity, unit FROM recipe_ingredients WHERE recipe_id=?");
foreach ($plans as $p) {
    $scale = $p['servings'] / $p['base_servings'];
    $ingSt->execute([$p['rid']]);
    foreach ($ingSt as $i) {
        $key = $i['name'].'__'.$i['unit']; // 合算キー
        if (!isset($ingredients[$key])) {
            $ingredients[$key] = ['name'=>$i['name'], 'unit'=>$i['unit'], 'need'=>0];
        }
        $ingredients[$key]['need'] += $i['quantity'] * $scale;
    }
}

// パントリー在庫を差し引く
$st = $pdo->prepare("SELECT name, unit, quantity FROM pantry_items WHERE user_id=?");
$st->execute([$uid]);
foreach ($st as $row) {
    $key = $row['name'].'__'.$row['unit'];
    if (isset($ingredients[$key])) {
        $ingredients[$key]['need'] -= $row['quantity'];
        if ($ingredients[$key]['need'] < 0) {
            $ingredients[$key]['need'] = 0;
        }
    }
}

require __DIR__.'/../templates/_header.php';
?>
<h1>買い物リスト</h1>
<p><?= htmlspecialchars($start) ?> 週 (<?= $startDt->format('Y-m-d') ?>〜<?= $endDt->format('Y-m-d') ?>)</p>

<div class="add-extra box" style="margin:14px 0; display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content: center;">
  <strong>自分の買い物を追加：</strong>
  <input type="text" id="extra-name" placeholder="例: 牛乳" style="min-width:140px">
  <input type="number" id="extra-qty" step="0.01" min="0" placeholder="数量" style="width:8em">
  <input type="text" id="extra-unit" placeholder="単位（本・個・g など）" style="min-width:120px">
  <button type="button" id="add-extra" class="btn">＋ リストに追加</button>
</div>

<?php if ($ingredients): ?>
  <div style="margin:10px 0;">
    <button id="add-to-pantry" class="btn">✔ 選択を購入済みに追加</button>
  </div>
<?php endif; ?>

<table class="list" id="shop-list">
  <colgroup>
    <col style="width:2.2em">   <!-- チェック -->
    <col>                       <!-- 食材名（可変） -->
    <col style="width:4.8em">   <!-- 必要量 -->
    <col style="width:3.6em">   <!-- 単位 -->
    <col style="width:5.2em">   <!-- 購入量（縮小） -->
    <col style="width:4.2em">   <!-- 操作 -->
  </colgroup>
  <thead>
    <tr>
			<th><input type="checkbox" id="check-all"></th>
			<th>食材名</th>
      <th>必要量</th>
      <th>単位</th>
      <th>購入量</th>
      <th>操作</th>
		</tr>
	</thead>
  <tbody>
		<?php foreach ($ingredients as $i): ?>
      <?php if ($i['need'] <= 0) continue; ?>
      <tr data-name="<?= htmlspecialchars($i['name']) ?>"
          data-unit="<?= htmlspecialchars($i['unit']) ?>">
        <td><input type="checkbox" class="row-check"></td>
        <td><?= htmlspecialchars($i['name']) ?></td>
        <td><?= number_format($i['need'], 2) ?></td>
        <td><?= htmlspecialchars($i['unit']) ?></td>
        <td>
          <input type="number" step="0.01" min="0"
                 class="buy-qty" value="<?= htmlspecialchars(number_format($i['need'], 2, '.', '')) ?>"
                 style="width:8em">
        </td>
        <td class="right">
          <button type="button" class="btn small danger row-del">削除</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$ingredients): ?>
      <tr><td colspan="5">この週の献立が未設定です。</td></tr>
    <?php endif; ?>
    <?php foreach ($extraRows as $x): ?>
    <tr data-name="<?=h($x['name'])?>" data-unit="<?=h($x['unit'])?>" data-custom="1" data-id="<?=$x['id']?>">
      <td><input type="checkbox" class="row-check" checked></td>
      <td><?=h($x['name'])?></td>
      <td><?=number_format($x['quantity'],2)?></td>
      <td><?=h($x['unit'])?></td>
      <td>
        <input type="number" step="0.01" min="0" class="buy-qty"
              value="<?=h(number_format($x['quantity'],2,'.',''))?>" style="width:8em">
      </td>
      <td class="right"><button type="button" class="btn small danger row-del">削除</button></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
  // BASE_URL は _footer.php で window.BASE_URL に埋め込まれている前提
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  // 全選択
  const all = document.getElementById('check-all');
  all?.addEventListener('change', () => {
    $$('.row-check').forEach(ch => ch.checked = all.checked);
  });

  // 追加ボタン
  document.getElementById('add-to-pantry')?.addEventListener('click', async () => {
    const rows = $$('#shop-list tbody tr').filter(tr => tr.querySelector('.row-check')?.checked);
    if (!rows.length) { alert('追加する食材を選択してください。'); return; }

    const payload = rows.map(tr => {
      const name = tr.dataset.name;
      const unit = tr.dataset.unit;
      const qty  = parseFloat(tr.querySelector('.buy-qty')?.value || '0');
      return { name, unit, quantity: isNaN(qty) ? 0 : qty };
    }).filter(x => x.quantity > 0);

    if (!payload.length) { alert('購入量が0の行は送れません。'); return; }

    try {
      const res = await fetch(window.BASE_URL.replace(/\/public$/, '') + '/api/pantry_add_bulk.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      });
      const j = await res.json();
      if (!j.ok) throw new Error(j.error || 'unknown error');

      alert('パントリーに追加しました！');
      // 反映: 今の画面上では必要量=必要量-購入量 として更新し、0になった行は消す
      rows.forEach(tr => {
        const needCell = tr.children[2];
        const buyInput = tr.querySelector('.buy-qty');
        const need = parseFloat((needCell.textContent || '0').replace(/,/g,''));
        const buy  = parseFloat(buyInput.value || '0');
        const next = Math.max(0, (need - buy));
        if (next <= 0.0001) {
          tr.remove();
        } else {
          needCell.textContent = next.toFixed(2);
          buyInput.value = next.toFixed(2);
          tr.querySelector('.row-check').checked = false;
        }
      });
    } catch (e) {
      console.error(e);
      alert('追加に失敗しました: ' + e.message);
    }
  });

  // 行削除（カスタム＝自分追加のものはDBも削除）
  document.getElementById('shop-list')?.addEventListener('click', async (e) => {
  if (!e.target.classList.contains('row-del')) return;
  const tr = e.target.closest('tr');
  if (!tr) return;

  const isCustom = tr.dataset.custom === '1';
  if (isCustom) {
    const id = Number(tr.dataset.id || 0);
    if (id > 0) {
      try {
        const res = await fetch(
          window.BASE_URL.replace(/\/public$/, '') + '/api/shopping_extra_delete.php',
          {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({ id })
          }
        );
        // 成否は画面では特に出さず、失敗しても行は消す（好みで調整OK）
        await res.json().catch(()=>({ok:true}));
      } catch (e) {
        console.warn('delete API failed:', e);
      }
    }
  }

  tr.remove();
  const rows = document.querySelectorAll('#shop-list tbody tr');
  if (!rows.length) {
    const all = document.getElementById('check-all');
    if (all) all.checked = false;
  }
});


  // 自分用アイテムを行として追加（id付き）
function addCustomRow({id, name, quantity, unit}) {
  const tbody = document.querySelector('#shop-list tbody');
  const tr = document.createElement('tr');
  tr.dataset.name = name;
  tr.dataset.unit = unit;
  tr.dataset.custom = '1';
  if (id) tr.dataset.id = id;     // ← DBのidを保持

  tr.innerHTML = `
    <td><input type="checkbox" class="row-check" checked></td>
    <td>${escapeHtml(name)}</td>
    <td class="need-cell">${Number(quantity).toFixed(2)}</td>
    <td>${escapeHtml(unit)}</td>
    <td><input type="number" step="0.01" min="0" class="buy-qty" value="${Number(quantity).toFixed(2)}" style="width:8em"></td>
    <td class="right"><button type="button" class="btn small danger row-del">削除</button></td>
  `;
  tbody.appendChild(tr);
}


  // HTMLエスケープ（最低限）
  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

// 追加ボタン：DBに保存してから行を追加
document.getElementById('add-extra')?.addEventListener('click', async () => {
  const name = (document.getElementById('extra-name')?.value || '').trim();
  const qty  = parseFloat(document.getElementById('extra-qty')?.value || '0');
  const unit = (document.getElementById('extra-unit')?.value || '').trim() || '個';
  if (!name) { alert('品名を入力してください'); return; }
  if (!(qty > 0)) { alert('数量は0より大きい値を入力してください'); return; }

  try {
    const payload = {
      week_start: '<?= $startDt->format('Y-m-d') ?>',
      name, unit, quantity: qty
    };
    const res = await fetch(
      window.BASE_URL.replace(/\/public$/, '') + '/api/shopping_extra_add.php',
      {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      }
    );
    const j = await res.json();
    if (!j.ok) throw new Error(j.error || '保存に失敗しました');

    // 画面にも反映（DBから返った id をセット）
    addCustomRow({id: j.id, name, quantity: qty, unit});

    // 入力リセット＆全選択の状態を更新
    document.getElementById('extra-name').value = '';
    document.getElementById('extra-qty').value  = '';
    const all = document.getElementById('check-all');
    if (all) {
      const rows = $$('.row-check');
      all.checked = rows.length && rows.every(ch => ch.checked);
    }
  } catch (e) {
    console.error(e);
    alert(e.message || '保存に失敗しました');
  }
});


  // 行の個別チェックで全選択を更新
  document.getElementById('shop-list')?.addEventListener('change', (e)=>{
    if (e.target.classList.contains('row-check')) {
      const all = document.getElementById('check-all');
      const rows = $$('.row-check');
      all.checked = rows.length && rows.every(ch => ch.checked);
    }
  });
</script>

<?php require __DIR__.'/../templates/_footer.php'; ?>
