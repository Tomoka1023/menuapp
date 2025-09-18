(function(){
    const body = document.getElementById('ing-body');
    const add = document.getElementById('add-ing');
  
    function row(data={name:'',quantity:'',unit:'',note:''}){
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input name="ing_name[]" value="${data.name ?? ''}" required placeholder="玉ねぎ"></td>
        <td><input type="number" step="0.01" name="ing_qty[]" value="${data.quantity ?? ''}" required style="width:7em"></td>
        <td><input name="ing_unit[]" value="${data.unit ?? '個'}" style="width:6em"></td>
        <td><input name="ing_note[]" value="${data.note ?? ''}" placeholder="みじん切りなど"></td>
        <td><button type="button" class="del">削除</button></td>
      `;
      tr.querySelector('.del').addEventListener('click', ()=> tr.remove());
      return tr;
    }
  
    add?.addEventListener('click', ()=> body.appendChild(row()));
  
    // // preload（edit画面）
    // const preload = window.__ING_PRELOAD__ || [];
    // if(preload.length){
    //   preload.forEach(i=> body.appendChild(row(i)));
    // }else{
    //   body.appendChild(row()); // newは1行だけ出しておく
    // }
  })();
  