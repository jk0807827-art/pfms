<?php
$pageTitle = "Budgets - Personal Finance Management System";

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

$categories = $data->get_categories($userId, 'budget');
$allBudgets = $data->get_budget_status($userId);
$months = $data->get_available_months($userId);

$defaultMonth = date('Y-m');
$budgetMonths = array_unique(array_map(function($b){ return substr($b['budget_month'],0,7); }, $allBudgets));
$allMonths = array_unique(array_merge($months, $budgetMonths, [$defaultMonth]));
rsort($allMonths);

function fmtMoney4($n){ return '$' . number_format((float)$n, 2); }

$currentPage = basename($_SERVER['PHP_SELF']);
include 'header.php';
?>


  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Budgets</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Planning</div>
        <h1 class="page-title">Budgets</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <div class="grid-2">
        <div class="ledger-panel">
          <div class="panel-head"><div><h3>Create monthly budget</h3></div></div>

          <?php if(count($categories) === 0): ?>
          <div class="field-hint" style="margin-bottom:1rem;">You don't have any budget categories yet. <a href="categories.php">Add one first &rarr;</a></div>
          <?php endif; ?>

          <form id="budgetForm">
            <div class="field-group">
              <label class="field-label" for="month">Month</label>
              <input class="field-control" id="month" type="month" value="<?= $defaultMonth ?>">
            </div>
            <div class="field-group">
              <label class="field-label" for="category">Category</label>
              <select class="field-control" id="category" <?= count($categories) === 0 ? 'disabled' : '' ?>>
                <?php if(count($categories) === 0): ?>
                <option value="">No categories yet</option>
                <?php else: foreach($categories as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; endif; ?>
              </select>
              <div class="field-hint">If a budget already exists for this category and month, it will be updated.</div>
            </div>
            <div class="field-group" id="g_amount">
              <label class="field-label" for="amount">Budget amount</label>
              <input class="field-control" id="amount" type="number" step="0.01" min="0" placeholder="0.00">
              <div class="field-error">Enter a valid amount greater than 0.</div>
            </div>
            <button type="submit" class="btn-ledger brass" style="width:100%;" <?= count($categories) === 0 ? 'disabled' : '' ?>>Save budget</button>
          </form>
        </div>

        <div class="ledger-panel">
          <div class="panel-head">
            <div><div class="panel-sub" id="progressMonthLabel"><?= date('F Y', strtotime($defaultMonth.'-01')) ?></div><h3>Budget vs actual</h3></div>
            <div class="field-group" style="margin:0;">
              <label class="field-label" for="monthFilter">Filter by month</label>
              <select class="field-control" id="monthFilter" style="width:auto;">
                <?php foreach($allMonths as $m): ?>
                <option value="<?= $m ?>" <?= $m === $defaultMonth ? 'selected' : '' ?>><?= date('F Y', strtotime($m.'-01')) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div id="budgetProgress"></div>
          <div class="table-empty" id="progressEmpty" style="display:none;">No budgets set for this month yet.</div>
        </div>
      </div>

      <div class="ledger-panel" style="margin-top:1.25rem;">
        <div class="panel-head"><div><h3 id="tableMonthTitle">All budgets for <?= date('F Y', strtotime($defaultMonth.'-01')) ?></h3></div></div>
        <table class="ledger">
          <thead><tr><th>Category</th><th class="num">Budget</th><th class="num">Spent</th><th class="num">Remaining</th><th></th></tr></thead>
          <tbody id="tblBody">
              <?php foreach($allBudgets as $b):
                  $bMonth = substr($b['budget_month'],0,7);
              ?>
              <tr data-month="<?= $bMonth ?>"
                  data-id="<?= (int)$b['budget_id'] ?>"
                  data-category="<?= htmlspecialchars($b['category_name'], ENT_QUOTES) ?>"
                  data-budget="<?= htmlspecialchars($b['budget_amount']) ?>"
                  data-spent="<?= htmlspecialchars($b['spent']) ?>"
                  data-remaining="<?= htmlspecialchars($b['remaining']) ?>"
                  style="display:none;">
                <td><?= htmlspecialchars($b['category_name']) ?></td>
                <td class="num"><?= fmtMoney4($b['budget_amount']) ?></td>
                <td class="num"><?= fmtMoney4($b['spent']) ?></td>
                <td class="num" style="color:<?= (float)$b['remaining'] >= 0 ? 'var(--gain)' : 'var(--loss)' ?>"><?= ((float)$b['remaining'] < 0 ? '-' : '') . fmtMoney4(abs($b['remaining'])) ?></td>
                <td><div class="row-actions"><button type="button" class="edit-btn">Edit</button><button type="button" class="del">Delete</button></div></td>
              </tr>
              <?php endforeach; ?>
          </tbody>
        </table>
        <div class="table-empty" id="tableEmpty" style="display:none;">No budgets for this month yet.</div>
      </div>

    </main>
  </div>
</div>

<div id="toast"></div>
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

<!-- Edit budget modal -->
<div class="modal-veil" id="editBudgetVeil">
  <div class="modal-box">
    <div class="modal-title">Edit budget</div>
    <form id="editBudgetForm">
      <input type="hidden" id="editBudgetId">
      <p class="field-hint" id="editBudgetLabel" style="margin-top:0;"></p>
      <div class="field-group" id="g_editBudgetAmount">
        <label class="field-label" for="editBudgetAmount">Budget amount</label>
        <input class="field-control" id="editBudgetAmount" type="number" step="0.01" min="0" placeholder="0.00">
        <div class="field-error">Enter a valid amount greater than 0.</div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ledger ghost sm" id="editBudgetCancel">Cancel</button>
        <button type="submit" class="btn-ledger brass sm">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/ui.js"></script>
<script>
/* ---------- Add / upsert budget ---------- */
document.getElementById('budgetForm').addEventListener('submit', function(e){
  e.preventDefault();
  const amount = parseFloat(document.getElementById('amount').value);
  const categoryId = document.getElementById('category').value;
  const month = document.getElementById('month').value;
  document.getElementById('g_amount').classList.toggle('has-error', !(amount > 0));
  if(!(amount > 0) || !categoryId || !month) return;

  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;

  $.ajax({
    url: 'include/routes.php',
    type: 'POST',
    data: { type: 13, category_id: categoryId, month: month, amount: amount },
    dataType: 'json',
    success: function(response){
      if(response.statusCode == 200){
        toast('Budget saved.');
        window.location.reload();
      } else {
        toast(response.message || 'Unable to save budget.', 'error');
        btn.disabled = false;
      }
    },
    error: function(){
      toast('Something went wrong. Please try again.', 'error');
      btn.disabled = false;
    }
  });
});

/* ---------- Edit modal (budget amount) ---------- */
const editBudgetVeil = document.getElementById('editBudgetVeil');
const editBudgetForm = document.getElementById('editBudgetForm');
const editBudgetId = document.getElementById('editBudgetId');
const editBudgetAmount = document.getElementById('editBudgetAmount');

function openEditBudget(row){
  editBudgetId.value = row.dataset.id;
  editBudgetAmount.value = row.dataset.budget;
  document.getElementById('editBudgetLabel').textContent = row.dataset.category + ' \u2014 ' + document.getElementById('monthFilter').selectedOptions[0].textContent;
  document.getElementById('g_editBudgetAmount').classList.remove('has-error');
  editBudgetVeil.classList.add('open');
  setTimeout(function(){ editBudgetAmount.focus(); }, 50);
}
document.getElementById('editBudgetCancel').addEventListener('click', function(){
  editBudgetVeil.classList.remove('open');
});

editBudgetForm.addEventListener('submit', function(e){
  e.preventDefault();
  const amount = parseFloat(editBudgetAmount.value);
  document.getElementById('g_editBudgetAmount').classList.toggle('has-error', !(amount > 0));
  if(!(amount > 0)) return;

  const btn = editBudgetForm.querySelector('button[type="submit"]');
  btn.disabled = true;

  $.ajax({
    url: 'include/routes.php',
    type: 'POST',
    data: { type: 14, budget_id: editBudgetId.value, amount: amount },
    dataType: 'json',
    success: function(response){
      if(response.statusCode == 200){
        toast('Budget updated.');
        window.location.reload();
      } else {
        toast(response.message || 'Unable to update budget.', 'error');
        btn.disabled = false;
      }
    },
    error: function(){
      toast('Something went wrong. Please try again.', 'error');
      btn.disabled = false;
    }
  });
});

/* ---------- Delete ---------- */
function bindDeleteButton(btn){
  btn.addEventListener('click', function(){
    const row = btn.closest('tr');
    confirmModal('Delete this budget?', 'This action cannot be undone.').then(function(ok){
      if(!ok) return;
      $.ajax({
        url: 'include/routes.php',
        type: 'POST',
        data: { type: 15, budget_id: row.dataset.id },
        dataType: 'json',
        success: function(response){
          if(response.statusCode == 200){
            row.remove();
            toast('Budget deleted.');
            renderMonth();
          } else {
            toast(response.message || 'Unable to delete budget.', 'error');
          }
        },
        error: function(){
          toast('Something went wrong. Please try again.', 'error');
        }
      });
    });
  });
}
document.querySelectorAll('#tblBody .del').forEach(bindDeleteButton);
document.querySelectorAll('#tblBody .edit-btn').forEach(function(btn){
  btn.addEventListener('click', function(){ openEditBudget(btn.closest('tr')); });
});

/* ---------- Month filter drives both the progress panel and the table ---------- */
const monthFilter = document.getElementById('monthFilter');
const allRows = Array.from(document.querySelectorAll('#tblBody tr'));
const progressBox = document.getElementById('budgetProgress');

function fmt(n){ return '$' + Number(n).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }

function renderMonth(){
  const month = monthFilter.value;
  document.getElementById('progressMonthLabel').textContent = monthFilter.selectedOptions[0].textContent;
  document.getElementById('tableMonthTitle').textContent = 'All budgets for ' + monthFilter.selectedOptions[0].textContent;

  const rowsForMonth = allRows.filter(r => r.dataset.month === month && r.parentNode);
  progressBox.innerHTML = '';

  rowsForMonth.forEach(function(row){
    const budget = parseFloat(row.dataset.budget);
    const spent = parseFloat(row.dataset.spent);
    const remaining = parseFloat(row.dataset.remaining);
    const pct = budget > 0 ? Math.min(100, Math.round((spent / budget) * 100)) : 0;
    const over = remaining < 0;

    const div = document.createElement('div');
    div.style.marginBottom = '1.1rem';
    div.innerHTML = '<div style="display:flex; justify-content:space-between; font-size:.85rem;">' +
      '<span>' + row.dataset.category + '</span><span class="figure">' + fmt(spent) + ' / ' + fmt(budget) + ' &middot; ' + pct + '%</span></div>' +
      '<div class="progress-track"><div class="progress-fill ' + (over ? 'over' : '') + '" style="width:' + (over ? 100 : pct) + '%"></div></div>' +
      (over
        ? '<div style="font-size:.76rem;color:var(--loss);margin-top:.3em;">&#9888; Budget exceeded by ' + fmt(Math.abs(remaining)) + '</div>'
        : '<div style="font-size:.76rem;color:var(--ink-faint);margin-top:.3em;">Within budget</div>');
    progressBox.appendChild(div);
  });

  document.getElementById('progressEmpty').style.display = rowsForMonth.length === 0 ? 'block' : 'none';

  let visibleRows = 0;
  allRows.forEach(function(row){
    if(!row.parentNode) return;
    const show = row.dataset.month === month;
    row.style.display = show ? '' : 'none';
    if(show) visibleRows++;
  });
  document.getElementById('tableEmpty').style.display = visibleRows === 0 ? 'block' : 'none';
}
monthFilter.addEventListener('change', renderMonth);
renderMonth();
</script>
</body>
</html>
