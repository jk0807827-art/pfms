<?php
$pageTitle = "Expenses - Personal Finance Management System";

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

$categories = $data->get_categories($userId, 'expense');
$expenses = $data->get_expenses($userId);
$months = $data->get_available_months($userId);

$defaultMonth = date('Y-m');
$total = 0;
foreach($expenses as $e){ $total += (float)$e['amount']; }

function fmtMoney2($n){ return '$' . number_format((float)$n, 2); }
function fmtDate2($d){ $t = strtotime($d); return $t ? date('d M Y', $t) : $d; }

$currentPage = basename($_SERVER['PHP_SELF']);
include 'header.php';
?>


  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Expenses</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Financial records</div>
        <h1 class="page-title">Expenses</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <div class="grid-2">
        <div class="ledger-panel">
          <div class="panel-head"><div><h3 id="formTitle">Add expense</h3></div></div>

          <?php if(count($categories) === 0): ?>
          <div class="field-hint" style="margin-bottom:1rem;">You don't have any expense categories yet. <a href="categories.php">Add one first &rarr;</a></div>
          <?php endif; ?>

          <form id="expenseForm">
            <input type="hidden" id="editId">
            <div class="field-group" id="g_category">
              <label class="field-label" for="category">Category</label>
              <select class="field-control" id="category" <?= count($categories) === 0 ? 'disabled' : '' ?>>
                <?php if(count($categories) === 0): ?>
                <option value="">No categories yet</option>
                <?php else: foreach($categories as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="field-group" id="g_amount">
              <label class="field-label" for="amount">Amount</label>
              <input class="field-control" id="amount" type="number" step="0.01" min="0" placeholder="0.00">
              <div class="field-error">Enter a valid amount greater than 0.</div>
            </div>
            <div class="field-group" id="g_date">
              <label class="field-label" for="date">Date</label>
              <input class="field-control" id="date" type="date" value="<?= date('Y-m-d') ?>">
              <div class="field-error">Select a date.</div>
            </div>
            <div class="field-group">
              <label class="field-label" for="description">Description</label>
              <input class="field-control" id="description" placeholder="Optional note">
            </div>
            <div style="display:flex; gap:.6rem;">
              <button type="submit" class="btn-ledger brass" style="flex:1;" id="submitBtn" <?= count($categories) === 0 ? 'disabled' : '' ?>>Add expense</button>
              <button type="button" class="btn-ledger ghost" id="cancelEdit" style="display:none;">Cancel</button>
            </div>
          </form>
        </div>

        <div class="ledger-panel">
          <div class="panel-head" style="align-items:center;">
            <div><div class="panel-sub" id="totalSub"><?= count($expenses) ?> records &middot; total <?= fmtMoney2($total) ?></div><h3>Expense records</h3></div>
            <div class="field-group" style="margin:0;">
              <label class="field-label" for="monthFilter">Filter by month</label>
              <select class="field-control" id="monthFilter" style="width:auto;">
                <option value="all">All months</option>
                <?php foreach($months as $m):
                    $label = date('F Y', strtotime($m.'-01'));
                ?>
                <option value="<?= $m ?>" <?= $m === $defaultMonth ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
                <?php if(!in_array($defaultMonth, $months)): ?>
                <option value="<?= $defaultMonth ?>" selected><?= date('F Y', strtotime($defaultMonth.'-01')) ?></option>
                <?php endif; ?>
              </select>
            </div>
          </div>
          <table class="ledger">
            <thead><tr><th>Date</th><th>Description</th><th>Category</th><th class="num">Amount</th><th></th></tr></thead>
            <tbody id="tblBody">
              <?php foreach($expenses as $e): ?>
              <tr data-month="<?= substr($e['expense_date'],0,7) ?>"
                  data-id="<?= (int)$e['expense_id'] ?>"
                  data-category-id="<?= (int)($e['category_id'] ?? 0) ?>"
                  data-amount="<?= htmlspecialchars($e['amount']) ?>"
                  data-date="<?= htmlspecialchars($e['expense_date']) ?>"
                  data-description="<?= htmlspecialchars($e['description'] ?? '', ENT_QUOTES) ?>">
                <td><?= fmtDate2($e['expense_date']) ?></td>
                <td><?= htmlspecialchars($e['description'] ?: '\u2014') ?></td>
                <td><span class="tag neutral"><?= htmlspecialchars($e['category_name'] ?: 'Uncategorized') ?></span></td>
                <td class="num" style="color:var(--loss)">-<?= fmtMoney2($e['amount']) ?></td>
                <td><div class="row-actions"><button type="button" class="edit-btn">Edit</button><button type="button" class="del">Delete</button></div></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="table-empty" id="emptyMsg" style="display:none;">No expenses match this month.</div>
        </div>
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

<script src="assets/js/ui.js"></script>
<script>
const tblBody = document.getElementById('tblBody');
const form = document.getElementById('expenseForm');
const submitBtn = document.getElementById('submitBtn');
const cancelEdit = document.getElementById('cancelEdit');
const editId = document.getElementById('editId');
let editingRow = null;

form.addEventListener('submit', function(e){
  e.preventDefault();
  let valid = true;
  const categorySelect = document.getElementById('category');
  const categoryId = categorySelect.value;
  const amount = parseFloat(document.getElementById('amount').value);
  const date = document.getElementById('date').value;
  const description = document.getElementById('description').value.trim();

  function setError(id, hasError){ document.getElementById(id).classList.toggle('has-error', hasError); if(hasError) valid = false; }
  setError('g_amount', !(amount > 0));
  setError('g_date', date.length === 0);
  if(!valid) return;

  submitBtn.disabled = true;

  $.ajax({
    url: 'include/routes.php',
    type: 'POST',
    data: {
      type: editingRow ? 8 : 7,
      expense_id: editId.value || undefined,
      category_id: categoryId,
      amount: amount,
      date: date,
      description: description
    },
    dataType: 'json',
    success: function(response){
      if(response.statusCode == 200){
        toast(editingRow ? 'Expense record updated.' : 'Expense added.');
        window.location.reload();
      } else {
        toast(response.message || 'Unable to save expense.', 'error');
        submitBtn.disabled = false;
      }
    },
    error: function(){
      toast('Something went wrong. Please try again.', 'error');
      submitBtn.disabled = false;
    }
  });
});

function bindEditButton(btn){
  btn.addEventListener('click', function(){
    const row = btn.closest('tr');
    editingRow = row;
    editId.value = row.dataset.id;
    document.getElementById('category').value = row.dataset.categoryId;
    document.getElementById('amount').value = row.dataset.amount;
    document.getElementById('date').value = row.dataset.date;
    document.getElementById('description').value = row.dataset.description;
    document.getElementById('formTitle').textContent = 'Edit expense';
    submitBtn.textContent = 'Save changes';
    cancelEdit.style.display = 'inline-flex';
    window.scrollTo({top:0, behavior:'smooth'});
  });
}
function bindDeleteButton(btn){
  btn.addEventListener('click', function(){
    const row = btn.closest('tr');
    confirmModal('Delete this expense record?', 'This action cannot be undone.').then(function(ok){
      if(!ok) return;
      $.ajax({
        url: 'include/routes.php',
        type: 'POST',
        data: { type: 9, expense_id: row.dataset.id },
        dataType: 'json',
        success: function(response){
          if(response.statusCode == 200){
            row.remove();
            toast('Expense record deleted.');
            applyFilter();
          } else {
            toast(response.message || 'Unable to delete expense.', 'error');
          }
        },
        error: function(){
          toast('Something went wrong. Please try again.', 'error');
        }
      });
    });
  });
}
document.querySelectorAll('#tblBody .edit-btn').forEach(bindEditButton);
document.querySelectorAll('#tblBody .del').forEach(bindDeleteButton);

cancelEdit.addEventListener('click', resetForm);

function resetForm(){
  form.reset();
  document.getElementById('date').value = '<?= date('Y-m-d') ?>';
  editId.value = '';
  editingRow = null;
  document.getElementById('formTitle').textContent = 'Add expense';
  submitBtn.textContent = 'Add expense';
  cancelEdit.style.display = 'none';
  ['g_amount','g_date'].forEach(id=>document.getElementById(id).classList.remove('has-error'));
}

const monthFilter = document.getElementById('monthFilter');
function applyFilter(){
  const val = monthFilter.value;
  let visible = 0;
  tblBody.querySelectorAll('tr').forEach(function(row){
    const show = (val === 'all' || row.dataset.month === val);
    row.style.display = show ? '' : 'none';
    if(show) visible++;
  });
  document.getElementById('emptyMsg').style.display = visible === 0 ? 'block' : 'none';
}
monthFilter.addEventListener('change', applyFilter);
applyFilter();
</script>
</body>
</html>
