document.addEventListener('click', async e => {
  if (e.target.id === 'save-plan') {
    console.log("save button clicked!"); // デバッグ
    const payload = [];
    document.querySelectorAll('tr[data-meal]').forEach(tr => {
      tr.querySelectorAll('td[data-date]').forEach(td => {
        const sel = td.querySelector('.recipe-select');
        const sv  = td.querySelector('.servings');
        const rid = Number(sel?.value || 0);
        const servings = Number(sv?.value || 1);
        if (rid > 0) {
          payload.push({
            date: td.dataset.date,
            meal: tr.dataset.meal,
            recipe_id: rid,
            servings
          });
        }
      });
    });

    const res = await fetch(window.BASE_URL.replace(/\/public$/, '') + '/api/plan_save.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    });
    const j = await res.json();
    alert(j.ok ? '保存しました！' : '失敗: ' + j.error);
  }
});

document.getElementById('auto-generate')?.addEventListener('click', async () => {
  const start = new URL(location.href).searchParams.get('start') || 
                new Date(new Date().setDate(new Date().getDate() - (new Date().getDay()+6)%7)).toISOString().slice(0,10);
  const payload = {
    start,
    servings: 2,
    meals: ['breakfast','lunch','dinner'],
    exclude_ingredients: [],        // ここはUIから拾うなら差し替え
    prefer_tags: ['時短','作り置き'],
    no_repeat_days: 7
  };
  const res = await fetch(window.BASE_URL.replace(/\/public$/, '') + '/api/auto_plan.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  });
  const j = await res.json();
  if (j.ok) {
    alert('自動作成しました！ページを再読み込みします。');
    location.reload();
  } else {
    alert('自動作成に失敗: ' + j.error);
  }
});

// ===== 自動献立モーダル =====
(function(){
  const $ = (sel, root=document)=>root.querySelector(sel);
  const $$ = (sel, root=document)=>Array.from(root.querySelectorAll(sel));

  const modal = $('#auto-modal');
  const openBtn = document.getElementById('auto-open') || document.getElementById('auto-generate');
  const form = $('#auto-form');

  function open(){ modal.hidden = false; }
  function close(){ modal.hidden = true; }

  openBtn?.addEventListener('click', open);
  modal?.addEventListener('click', (e)=>{
    if (e.target.matches('[data-close], .modal__backdrop')) close();
  });

  form?.addEventListener('submit', async (e)=>{
    e.preventDefault();

    const start = form.start.value || new Date().toISOString().slice(0,10);
    const servings = Math.max(1, parseInt(form.servings.value || '2', 10));
    const meals = $$('input[name="meals[]"]:checked', form).map(i=>i.value);
    const exclude_ingredients = (form.exclude_ingredients.value || '')
      .split(',').map(s=>s.trim()).filter(Boolean);
    const prefer_tags = (form.prefer_tags.value || '')
      .split(',').map(s=>s.trim()).filter(Boolean);
    const no_repeat_days = Math.max(0, parseInt(form.no_repeat_days.value || '7', 10));

    if (!meals.length){
      alert('少なくとも1つの食事（朝/昼/夜）を選んでください。');
      return;
    }

    const payload = { start, servings, meals, exclude_ingredients, prefer_tags, no_repeat_days };

    try {
      const res = await fetch(window.BASE_URL.replace(/\/public$/, '') + '/api/auto_plan.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      });
      const j = await res.json();
      if (!j.ok) throw new Error(j.error || 'unknown error');

      close();
      alert('自動作成しました！ページを更新します。');
      location.href = new URL(location.href).toString(); // リロード
    } catch (err) {
      console.error(err);
      alert('自動作成に失敗: ' + err.message);
    }
  });
})();

function syncRecipeLabels(root = document) {
  root.querySelectorAll('.week-cell').forEach(cell => {
    const sel = cell.querySelector('.recipe-select');
    const label = cell.querySelector('.recipe-label');
    if (!sel || !label) return;
    const text = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text : '';
    label.textContent = text || '';
    sel.title = text;     // ホバーでフル表示（tooltip）
    label.title = text;   // ラベルにもtooltip
  });
}

// 初期表示
document.addEventListener('DOMContentLoaded', () => {
  syncRecipeLabels();
});

// 変更時に同期
document.addEventListener('change', (e) => {
  if (e.target.classList.contains('recipe-select')) {
    const cell = e.target.closest('.week-cell') || document;
    syncRecipeLabels(cell);
  }
});
