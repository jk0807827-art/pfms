

document.addEventListener('DOMContentLoaded', function(){

  // Mobile sidebar open/close
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if(menuToggle && sidebar && backdrop){
    menuToggle.addEventListener('click', function(){
      sidebar.classList.add('open');
      backdrop.classList.add('open');
    });
    backdrop.addEventListener('click', function(){
      sidebar.classList.remove('open');
      backdrop.classList.remove('open');
    });
  }

  // Show/hide password checkboxes: any <input type="checkbox" data-show-password="fieldId">
  document.querySelectorAll('[data-show-password]').forEach(function(cb){
    const target = document.getElementById(cb.getAttribute('data-show-password'));
    if(!target) return;
    cb.addEventListener('change', function(){ target.type = cb.checked ? 'text' : 'password'; });
  });
});

/* ---------- Toast ---------- */
function toast(msg, type){
  const box = document.getElementById('toast');
  if(!box) return;
  const el = document.createElement('div');
  el.className = 'toast-msg' + (type==='error' ? ' error' : '');
  el.textContent = msg;
  box.appendChild(el);
  setTimeout(function(){
    el.style.opacity = '0';
    el.style.transition = 'opacity .2s';
    setTimeout(function(){ el.remove(); }, 200);
  }, 2600);
}

/* ---------- Confirm modal ----------
   Usage: confirmModal('Delete this record?', 'This cannot be undone.').then(ok => { if(ok) ... })
*/
function confirmModal(title, body){
  return new Promise(function(resolve){
    const veil = document.getElementById('confirmVeil');
    if(!veil){ resolve(window.confirm(title)); return; }
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmBody').textContent = body || '';
    veil.classList.add('open');
    const ok = document.getElementById('confirmOk');
    const cancel = document.getElementById('confirmCancel');
    function cleanup(result){
      veil.classList.remove('open');
      ok.removeEventListener('click', onOk);
      cancel.removeEventListener('click', onCancel);
      resolve(result);
    }
    function onOk(){ cleanup(true); }
    function onCancel(){ cleanup(false); }
    ok.addEventListener('click', onOk);
    cancel.addEventListener('click', onCancel);
  });
}

/* ---------- Row removal helper used by list pages ----------
   Wraps confirmModal + removing a <tr> from the DOM so every
   page's delete button is a one-liner.
*/
function confirmDeleteRow(button, label){
  const row = button.closest('tr');
  confirmModal('Delete this ' + (label||'record') + '?', 'This action cannot be undone.').then(function(ok){
    if(!ok || !row) return;
    row.remove();
    toast((label ? label.charAt(0).toUpperCase()+label.slice(1) : 'Record') + ' deleted.');
  });
}
