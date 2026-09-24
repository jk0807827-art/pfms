<?php
$pageTitle = "Transactions - Personal Finance Management System";

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

$transactions = $data->get_transactions($userId);

$allCategories = array_unique(array_filter(array_map(function($t){ return $t['category_name']; }, $transactions)));
sort($allCategories);

$totalIn = 0; $totalOut = 0;
foreach($transactions as $t){
    if($t['txn_type'] === 'income') $totalIn += (float)$t['amount'];
    else $totalOut += (float)$t['amount'];
}

function fmtMoney6($n){ return '$' . number_format((float)$n, 2); }
function fmtDate6($d){ $t = strtotime($d); return $t ? date('d M Y', $t) : $d; }

$currentPage = basename($_SERVER['PHP_SELF']);
include 'header.php';
?>


  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Transactions</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">History</div>
        <h1 class="page-title">Transactions</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <div class="ledger-panel">
        <div class="panel-head"><div><h3>Filter</h3></div></div>
        <form id="filterForm" style="display:grid; grid-template-columns:repeat(5,1fr) auto; gap:.9rem; align-items:end;">
          <div class="field-group" style="margin:0;">
            <label class="field-label" for="fromDate">From date</label>
            <input class="field-control" id="fromDate" type="date">
          </div>
          <div class="field-group" style="margin:0;">
            <label class="field-label" for="toDate">To date</label>
            <input class="field-control" id="toDate" type="date">
          </div>
          <div class="field-group" style="margin:0;">
            <label class="field-label" for="typeFilter">Type</label>
            <select class="field-control" id="typeFilter">
              <option value="all">All</option>
              <option value="income">Income</option>
              <option value="expense">Expenses</option>
            </select>
          </div>
          <div class="field-group" style="margin:0;">
            <label class="field-label" for="catFilter">Category</label>
            <select class="field-control" id="catFilter">
              <option value="all">All categories</option>
              <?php foreach($allCategories as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field-group" style="margin:0;">
            <label class="field-label" for="searchBox">Search</label>
            <input class="field-control" id="searchBox" placeholder="Description">
          </div>
          <div style="display:flex; gap:.5rem;">
            <button type="submit" class="btn-ledger brass sm">Search</button>
            <button type="button" class="btn-ledger ghost sm" id="resetBtn">Reset</button>
          </div>
        </form>
      </div>

      <div class="stat-row" style="grid-template-columns:repeat(3,1fr); margin-top:1.25rem;">
        <div class="stat"><div class="stat-label">Matching income</div><div class="stat-value figure" style="color:var(--gain)" id="sumIncome"><?= fmtMoney6($totalIn) ?></div></div>
        <div class="stat"><div class="stat-label">Matching expenses</div><div class="stat-value figure" style="color:var(--loss)" id="sumExpense"><?= fmtMoney6($totalOut) ?></div></div>
        <div class="stat"><div class="stat-label">Net</div><div class="stat-value figure" id="sumNet"><?= fmtMoney6($totalIn - $totalOut) ?></div></div>
      </div>

      <div class="ledger-panel">
        <div class="panel-head">
          <div><div class="panel-sub" id="countSub"><?= count($transactions) ?> matching transaction<?= count($transactions) === 1 ? '' : 's' ?></div><h3>All transactions</h3></div>
        </div>
        <table class="ledger">
          <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th class="num">Amount</th></tr></thead>
          <tbody id="tblBody">
            <?php foreach($transactions as $t):
                $isIncome = $t['txn_type'] === 'income';
            ?>
            <tr data-date="<?= htmlspecialchars($t['txn_date']) ?>" data-type="<?= $t['txn_type'] ?>" data-category="<?= htmlspecialchars($t['category_name'] ?? '', ENT_QUOTES) ?>">
              <td><?= fmtDate6($t['txn_date']) ?></td>
              <td><?= htmlspecialchars($t['description'] ?: '\u2014') ?></td>
              <td><span class="tag neutral"><?= htmlspecialchars($t['category_name'] ?: 'Uncategorized') ?></span></td>
              <td><span class="tag <?= $isIncome ? 'gain' : 'loss' ?>"><?= $isIncome ? 'Income' : 'Expense' ?></span></td>
              <td class="num" style="color:<?= $isIncome ? 'var(--gain)' : 'var(--loss)' ?>"><?= ($isIncome ? '+' : '-') . fmtMoney6($t['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="table-empty" id="emptyMsg" style="display:none;">No transactions match these filters.</div>
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
const rows = Array.from(tblBody.querySelectorAll('tr'));

function fmt(n){ return '$' + n.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }

function applyFilters(){
  const from = document.getElementById('fromDate').value;
  const to = document.getElementById('toDate').value;
  const type = document.getElementById('typeFilter').value;
  const cat = document.getElementById('catFilter').value;
  const q = document.getElementById('searchBox').value.trim().toLowerCase();

  let visible = 0, totalIn = 0, totalOut = 0;
  rows.forEach(function(row){
    const date = row.dataset.date;
    const rtype = row.dataset.type;
    const rcat = row.dataset.category;
    const desc = row.children[1].textContent.toLowerCase();
    const amount = parseFloat(row.children[4].textContent.replace(/[^0-9.]/g,''));

    let show = true;
    if(from && date < from) show = false;
    if(to && date > to) show = false;
    if(type !== 'all' && rtype !== type) show = false;
    if(cat !== 'all' && rcat !== cat) show = false;
    if(q && !desc.includes(q)) show = false;

    row.style.display = show ? '' : 'none';
    if(show){
      visible++;
      if(rtype === 'income') totalIn += amount; else totalOut += amount;
    }
  });

  document.getElementById('sumIncome').textContent = fmt(totalIn);
  document.getElementById('sumExpense').textContent = fmt(totalOut);
  document.getElementById('sumNet').textContent = fmt(totalIn - totalOut);
  document.getElementById('countSub').textContent = visible + ' matching transaction' + (visible===1?'':'s');
  document.getElementById('emptyMsg').style.display = visible === 0 ? 'block' : 'none';
}

document.getElementById('filterForm').addEventListener('submit', function(e){ e.preventDefault(); applyFilters(); });
document.getElementById('resetBtn').addEventListener('click', function(){
  document.getElementById('filterForm').reset();
  applyFilters();
});
</script>
</body>
</html>
