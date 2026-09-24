<?php
$pageTitle = "Investments - Personal Finance Management System";

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

$categories = $data->get_categories($userId, 'investment');
$investments = $data->get_investments($userId);
$totals = $data->get_investment_totals($userId);

function fmtMoney5($n){ return '$' . number_format((float)$n, 2); }

$currentPage = basename($_SERVER['PHP_SELF']);
include 'header.php';
?>


  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Investments</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Portfolio tracking</div>
        <h1 class="page-title">Investments</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <div class="grid-2">
        <div class="ledger-panel">
          <div class="panel-head"><div><h3 id="formTitle">Add investment</h3></div></div>

          <?php if(count($categories) === 0): ?>
          <div class="field-hint" style="margin-bottom:1rem;">You don't have any investment types yet. <a href="categories.php">Add one first &rarr;</a></div>
          <?php endif; ?>

          <form id="invForm">
            <input type="hidden" id="editId">
            <div class="field-group" id="g_name">
              <label class="field-label" for="name">Investment name</label>
              <input class="field-control" id="name" placeholder="e.g. ABC Shares">
              <div class="field-error">Enter a name.</div>
            </div>
            <div class="field-group">
              <label class="field-label" for="type">Investment type</label>
              <select class="field-control" id="type" <?= count($categories) === 0 ? 'disabled' : '' ?>>
                <?php if(count($categories) === 0): ?>
                <option value="">No types yet</option>
                <?php else: foreach($categories as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="field-group" id="g_invested">
              <label class="field-label" for="invested">Amount invested</label>
              <input class="field-control" id="invested" type="number" step="0.01" min="0" placeholder="0.00">
              <div class="field-error">Enter a valid amount greater than 0.</div>
            </div>
            <div class="field-group" id="g_date">
              <label class="field-label" for="purchaseDate">Purchase date</label>
              <input class="field-control" id="purchaseDate" type="date" value="<?= date('Y-m-d') ?>">
              <div class="field-error">Select a date.</div>
            </div>
            <div class="field-group" id="g_current">
              <label class="field-label" for="current">Current value</label>
              <input class="field-control" id="current" type="number" step="0.01" min="0" placeholder="0.00">
              <div class="field-error">Enter a valid current value.</div>
            </div>
            <div class="field-group">
              <label class="field-label" for="notes">Notes</label>
              <input class="field-control" id="notes" placeholder="Optional note">
            </div>
            <div style="display:flex; gap:.6rem;">
              <button type="submit" class="btn-ledger brass" style="flex:1;" id="submitBtn" <?= count($categories) === 0 ? 'disabled' : '' ?>>Add investment</button>
              <button type="button" class="btn-ledger ghost" id="cancelEdit" style="display:none;">Cancel</button>
            </div>
          </form>
          <p class="field-hint" style="margin-top:1rem;">PFMS tracks what you tell it — it doesn't fetch live prices, place trades, or suggest what to buy.</p>
        </div>

        <div>
          <div class="stat-row" style="grid-template-columns:repeat(3,1fr); margin-bottom:1.25rem;">
            <div class="stat"><div class="stat-label">Total invested</div><div class="stat-value figure" id="statInvested"><?= fmtMoney5($totals['total_invested']) ?></div></div>
            <div class="stat"><div class="stat-label">Current value</div><div class="stat-value figure" id="statCurrent"><?= fmtMoney5($totals['total_current_value']) ?></div></div>
            <div class="stat"><div class="stat-label">Gain / loss</div><div class="stat-value figure" id="statGain" style="color:<?= (float)$totals['total_gain_loss'] >= 0 ? 'var(--gain)' : 'var(--loss)' ?>"><?= ((float)$totals['total_gain_loss'] >= 0 ? '+' : '') . fmtMoney5($totals['total_gain_loss']) ?></div></div>
          </div>
          <div class="ledger-panel">
            <div class="panel-head"><div><h3>Your investments</h3></div></div>
            <table class="ledger">
              <thead><tr><th>Name</th><th>Type</th><th class="num">Invested</th><th class="num">Current</th><th class="num">Gain/Loss</th><th></th></tr></thead>
              <tbody id="tblBody">
                <?php foreach($investments as $inv): ?>
                <tr data-id="<?= (int)$inv['investment_id'] ?>"
                    data-category-id="<?= (int)($inv['category_id'] ?? 0) ?>"
                    data-name="<?= htmlspecialchars($inv['name'], ENT_QUOTES) ?>"
                    data-invested="<?= htmlspecialchars($inv['amount_invested']) ?>"
                    data-current="<?= htmlspecialchars($inv['current_value']) ?>"
                    data-date="<?= htmlspecialchars($inv['purchase_date']) ?>"
                    data-notes="<?= htmlspecialchars($inv['notes'] ?? '', ENT_QUOTES) ?>">
                  <td><?= htmlspecialchars($inv['name']) ?><?php if($inv['notes']): ?><div class="field-hint" style="margin:0;"><?= htmlspecialchars($inv['notes']) ?></div><?php endif; ?></td>
                  <td><span class="tag neutral"><?= htmlspecialchars($inv['category_name'] ?: 'Uncategorized') ?></span></td>
                  <td class="num"><?= fmtMoney5($inv['amount_invested']) ?></td>
                  <td class="num"><?= fmtMoney5($inv['current_value']) ?></td>
                  <td class="num"><span class="tag <?= (float)$inv['gain_loss'] >= 0 ? 'gain' : 'loss' ?>"><?= ((float)$inv['gain_loss'] >= 0 ? '+' : '') . fmtMoney5($inv['gain_loss']) ?></span></td>
                  <td><div class="row-actions"><button type="button" class="edit-btn">Edit</button><button type="button" class="del">Delete</button></div></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if(count($investments) === 0): ?>
            <div class="table-empty">No investments recorded yet.</div>
            <?php endif; ?>
          </div>
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
const form = document.getElementById('invForm');
const submitBtn = document.getElementById('submitBtn');
const cancelEdit = document.getElementById('cancelEdit');
const editId = document.getElementById('editId');
let editingRow = null;

form.addEventListener('submit', function(e){
  e.preventDefault();
  let valid = true;
  const name = document.getElementById('name').value.trim();
  const typeId = document.getElementById('type').value;
  const invested = parseFloat(document.getElementById('invested').value);
  const date = document.getElementById('purchaseDate').value;
  const current = parseFloat(document.getElementById('current').value);
  const notes = document.getElementById('notes').value.trim();

  function setError(id, hasError){ document.getElementById(id).classList.toggle('has-error', hasError); if(hasError) valid = false; }
  setError('g_name', name.length === 0);
  setError('g_invested', !(invested > 0));
  setError('g_date', date.length === 0);
  setError('g_current', isNaN(current) || current < 0);
  if(!valid) return;

  submitBtn.disabled = true;

  $.ajax({
    url: 'include/routes.php',
    type: 'POST',
    data: {
      type: editingRow ? 17 : 16,
      investment_id: editId.value || undefined,
      category_id: typeId,
      name: name,
      invested: invested,
      current: current,
      purchase_date: date,
      notes: notes
    },
    dataType: 'json',
    success: function(response){
      if(response.statusCode == 200){
        toast(editingRow ? 'Investment updated.' : 'Investment added.');
        window.location.reload();
      } else {
        toast(response.message || 'Unable to save investment.', 'error');
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
    document.getElementById('name').value = row.dataset.name;
    document.getElementById('type').value = row.dataset.categoryId;
    document.getElementById('invested').value = row.dataset.invested;
    document.getElementById('purchaseDate').value = row.dataset.date;
    document.getElementById('current').value = row.dataset.current;
    document.getElementById('notes').value = row.dataset.notes;
    document.getElementById('formTitle').textContent = 'Edit investment';
    submitBtn.textContent = 'Save changes';
    cancelEdit.style.display = 'inline-flex';
    window.scrollTo({top:0, behavior:'smooth'});
  });
}
function bindDeleteButton(btn){
  btn.addEventListener('click', function(){
    const row = btn.closest('tr');
    confirmModal('Delete this investment?', 'This action cannot be undone.').then(function(ok){
      if(!ok) return;
      $.ajax({
        url: 'include/routes.php',
        type: 'POST',
        data: { type: 18, investment_id: row.dataset.id },
        dataType: 'json',
        success: function(response){
          if(response.statusCode == 200){
            row.remove();
            toast('Investment deleted.');
          } else {
            toast(response.message || 'Unable to delete investment.', 'error');
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

cancelEdit.addEventListener('click', function(){
  form.reset();
  document.getElementById('purchaseDate').value = '<?= date('Y-m-d') ?>';
  editId.value = '';
  editingRow = null;
  document.getElementById('formTitle').textContent = 'Add investment';
  submitBtn.textContent = 'Add investment';
  cancelEdit.style.display = 'none';
  ['g_name','g_invested','g_date','g_current'].forEach(id=>document.getElementById(id).classList.remove('has-error'));
});
</script>
</body>
</html>
