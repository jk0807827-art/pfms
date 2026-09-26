<?php
$pageTitle = "Categories - Personal Finance Management System";

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

$typesMap = [
    'income'     => ['label' => 'Income',     'panel' => 'panel-income'],
    'expense'    => ['label' => 'Expense',    'panel' => 'panel-expense'],
    'budget'     => ['label' => 'Budget',     'panel' => 'panel-budget'],
    'investment' => ['label' => 'Investment', 'panel' => 'panel-investment'],
];

$catsByType = [];
foreach($typesMap as $key => $meta){
    $catsByType[$key] = $data->get_categories($userId, $key);
}

$currentPage = basename($_SERVER['PHP_SELF']);
include 'header.php';
?>


  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Categories</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Account setup</div>
        <h1 class="page-title">Categories</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <div class="ledger-panel" style="margin-bottom:1.25rem;">
        <p style="margin:0;">Categories keep your records consistent across the app. Add, rename or retire a category here and it becomes available on the Income, Expenses, Budgets and Investments pages.</p>
      </div>

      <div class="cat-tabs">
        <button type="button" class="cat-tab-btn active" data-target="panel-income">Income</button>
        <button type="button" class="cat-tab-btn" data-target="panel-expense">Expenses</button>
        <button type="button" class="cat-tab-btn" data-target="panel-budget">Budgets</button>
        <button type="button" class="cat-tab-btn" data-target="panel-investment">Investments</button>
      </div>

      <?php foreach($typesMap as $typeKey => $meta): $rows = $catsByType[$typeKey]; ?>
      <section class="cat-panel <?= $typeKey === 'income' ? 'active' : '' ?>" id="<?= $meta['panel'] ?>">
        <div class="grid-2">
          <div class="ledger-panel">
            <div class="panel-head"><div><h3>Add <?= strtolower($meta['label']) ?> categor<?= $typeKey === 'expense' ? 'ies' : 'y' ?></h3></div></div>
            <form class="cat-form" data-usage="<?= $meta['label'] ?>" data-type="<?= $typeKey ?>">
              <div class="field-group cat-name-group">
                <label class="field-label">Category name</label>
                <input class="field-control cat-name-input" placeholder="e.g. <?= $typeKey === 'budget' ? 'Food, Transport' : 'Other' ?>">
                <div class="field-error">Enter a category name.</div>
              </div>
              <button type="submit" class="btn-ledger brass" style="width:100%;">Add category</button>
            </form>
            <p class="field-hint" style="margin-top:1rem;">
              <?php if($typeKey === 'income'): ?>Categories shown on the Income page when logging a new record.
              <?php elseif($typeKey === 'expense'): ?>Categories shown on the Expenses page, and reused as budget categories.
              <?php elseif($typeKey === 'budget'): ?>The categories you can set a monthly spending limit against on the Budgets page. Usually mirrors your expense categories.
              <?php else: ?>Types shown on the Investments page when logging a new holding.<?php endif; ?>
            </p>
          </div>
          <div class="ledger-panel">
            <div class="panel-head"><div><div class="panel-sub cat-count"><?= count($rows) ?> categor<?= count($rows) === 1 ? 'y' : 'ies' ?></div><h3><?= $meta['label'] ?> categories</h3></div></div>
            <table class="ledger">
              <thead><tr><th>Name</th><th>Used in</th><th></th></tr></thead>
              <tbody class="cat-tbody" data-type="<?= $typeKey ?>">
              <?php foreach($rows as $c): ?>
              <tr data-id="<?= (int)$c['category_id'] ?>" data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>">
                <td class="cat-name-cell"><?= htmlspecialchars($c['name']) ?></td>
                <td><span class="tag neutral"><?= $meta['label'] ?></span></td>
                <td><div class="row-actions"><button type="button" class="edit-cat-btn">Edit</button><button type="button" class="del">Delete</button></div></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php if(count($rows) === 0): ?>
            <div class="table-empty cat-empty">No <?= strtolower($meta['label']) ?> categories yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </section>
      <?php endforeach; ?>

    </main>
  </div>
</div>

<div id="toast"></div>

<!-- Delete confirm modal -->
<div class="modal-veil" id="confirmVeil">
  <div class="modal-box">
    <div class="modal-title" id="confirmTitle">Are you sure?</div>
    <div class="modal-body" id="confirmBody"></div>
    <div class="modal-actions">
      <button class="btn-ledger ghost sm" id="confirmCancel">Cancel</button>
      <button class="btn-ledger danger sm" id="confirmOk">Delete</button>
    </div>
  </div>
</div>

<!-- Edit category modal -->
<div class="modal-veil" id="editCatVeil">
  <div class="modal-box">
    <div class="modal-title">Edit category</div>
    <form id="editCatForm">
      <input type="hidden" id="editCatId">
      <div class="field-group" id="g_editCatName">
        <label class="field-label" for="editCatName">Category name</label>
        <input class="field-control" id="editCatName" autocomplete="off">
        <div class="field-error">Enter a category name.</div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ledger ghost sm" id="editCatCancel">Cancel</button>
        <button type="submit" class="btn-ledger brass sm">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/ui.js"></script>
<script>
document.querySelectorAll('.cat-tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.cat-tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.cat-panel').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.target).classList.add('active');
  });
});

/* ---------- Add category ---------- */
document.querySelectorAll('.cat-form').forEach(function(form){
  form.addEventListener('submit', function(e){
    e.preventDefault();
    const input = form.querySelector('.cat-name-input');
    const group = form.querySelector('.cat-name-group');
    const name = input.value.trim();
    group.classList.toggle('has-error', name.length === 0);
    if(name.length === 0) return;

    const catType = form.dataset.type;
    const usage = form.dataset.usage;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;

    $.ajax({
      url: 'include/routes.php',
      type: 'POST',
      data: { type: 4, name: name, category_type: catType },
      dataType: 'json',
      success: function(response){
        if(response.statusCode == 200){
          const tbody = document.querySelector('.cat-tbody[data-type="'+catType+'"]');
          const emptyMsg = tbody.closest('.ledger-panel').querySelector('.cat-empty');
          if(emptyMsg) emptyMsg.remove();

          const tr = document.createElement('tr');
          tr.dataset.id = response.category_id;
          tr.dataset.name = name;
          tr.innerHTML = '<td class="cat-name-cell"></td>' +
            '<td><span class="tag neutral">'+usage+'</span></td>' +
            '<td><div class="row-actions"><button type="button" class="edit-cat-btn">Edit</button><button type="button" class="del">Delete</button></div></td>';
          tr.querySelector('.cat-name-cell').textContent = name;
          tbody.appendChild(tr);
          bindRow(tr);

          const countEl = tbody.closest('.ledger-panel').querySelector('.cat-count');
          const n = tbody.querySelectorAll('tr').length;
          countEl.textContent = n + ' categor' + (n === 1 ? 'y' : 'ies');

          input.value = '';
          toast(usage + ' category added.');
        } else {
          group.classList.add('has-error');
          toast(response.message || 'Unable to add category.', 'error');
        }
      },
      error: function(){
        toast('Something went wrong. Please try again.', 'error');
      },
      complete: function(){ btn.disabled = false; }
    });
  });
});

/* ---------- Edit / Delete ---------- */
const editVeil = document.getElementById('editCatVeil');
const editForm = document.getElementById('editCatForm');
const editIdField = document.getElementById('editCatId');
const editNameField = document.getElementById('editCatName');
let editingRow = null;

function openEditModal(row){
  editingRow = row;
  editIdField.value = row.dataset.id;
  editNameField.value = row.dataset.name;
  document.getElementById('g_editCatName').classList.remove('has-error');
  editVeil.classList.add('open');
  setTimeout(function(){ editNameField.focus(); }, 50);
}
document.getElementById('editCatCancel').addEventListener('click', function(){
  editVeil.classList.remove('open');
  editingRow = null;
});

editForm.addEventListener('submit', function(e){
  e.preventDefault();
  const name = editNameField.value.trim();
  document.getElementById('g_editCatName').classList.toggle('has-error', name.length === 0);
  if(name.length === 0 || !editingRow) return;

  const btn = editForm.querySelector('button[type="submit"]');
  btn.disabled = true;

  $.ajax({
    url: 'include/routes.php',
    type: 'POST',
    data: { type: 5, category_id: editIdField.value, name: name },
    dataType: 'json',
    success: function(response){
      if(response.statusCode == 200){
        editingRow.dataset.name = name;
        editingRow.querySelector('.cat-name-cell').textContent = name;
        editVeil.classList.remove('open');
        toast('Category updated.');
      } else {
        document.getElementById('g_editCatName').classList.add('has-error');
        toast(response.message || 'Unable to update category.', 'error');
      }
    },
    error: function(){
      toast('Something went wrong. Please try again.', 'error');
    },
    complete: function(){ btn.disabled = false; }
  });
});

function bindRow(row){
  row.querySelector('.edit-cat-btn').addEventListener('click', function(){
    openEditModal(row);
  });
  row.querySelector('.del').addEventListener('click', function(){
    confirmModal('Delete this category?', 'Records already using it will keep their history, but budgets tied to it will also be removed.').then(function(ok){
      if(!ok) return;
      $.ajax({
        url: 'include/routes.php',
        type: 'POST',
        data: { type: 6, category_id: row.dataset.id },
        dataType: 'json',
        success: function(response){
          if(response.statusCode == 200){
            const tbody = row.closest('.cat-tbody');
            const panel = row.closest('.ledger-panel');
            row.remove();
            const countEl = panel.querySelector('.cat-count');
            const n = tbody.querySelectorAll('tr').length;
            countEl.textContent = n + ' categor' + (n === 1 ? 'y' : 'ies');
            if(n === 0 && !panel.querySelector('.cat-empty')){
              const div = document.createElement('div');
              div.className = 'table-empty cat-empty';
              div.textContent = 'No categories yet.';
              panel.appendChild(div);
            }
            toast('Category deleted.');
          } else {
            toast(response.message || 'Unable to delete category.', 'error');
          }
        },
        error: function(){
          toast('Something went wrong. Please try again.', 'error');
        }
      });
    });
  });
}
document.querySelectorAll('.cat-tbody tr').forEach(bindRow);
</script>
</body>
</html>
