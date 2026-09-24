<?php
$pageTitle = "Home - Personal Finance Management System";
$currentPage = basename($_SERVER['PHP_SELF']);

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

$thisMonth = date('Y-m');
$thisMonthLabel = date('F Y');

$monthSummary = $data->get_monthly_summary($userId, $thisMonth);
$grand = $data->get_grand_totals($userId);
$budgetStatus = $data->get_budget_status($userId, $thisMonth);
$investmentTotals = $data->get_investment_totals($userId);
$recentTxns = $data->get_recent_transactions($userId, 6);
$catBreakdown = $data->get_category_breakdown($userId, $thisMonth);



$totalIncome = (float)$monthSummary['total_income'];
$totalExpenses = (float)$monthSummary['total_expenses'];
$savings = (float)$monthSummary['savings'];
$savingsPct = $totalIncome > 0 ? round(($savings / $totalIncome) * 100) : 0;

// Charts show the CURRENT month only (not every month on record).
$hasMonthActivity = ($totalIncome > 0 || $totalExpenses > 0);

// Safe for embedding inside an inline <script> (category names are user-entered).
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$catLabels = array_column($catBreakdown, 'name');
$catTotals = array_map('floatval', array_column($catBreakdown, 'total'));

$overBudgetCount = 0;
foreach($budgetStatus as $b){
    if((float)$b['remaining'] < 0) $overBudgetCount++;
}

function fmtMoney($n){
    return '$' . number_format((float)$n, 2);
}

function fmtDate($d){
    $t = strtotime($d);
    return $t ? date('d M Y', $t) : $d;
}

include 'header.php';
?>

<style>
  /* ---------- Dashboard: responsive layout & charts ---------- */

  /* Let flex/grid children shrink below their content width. Without this a wide
     child (table, canvas) stretches the whole page past the screen edge on phones. */
  .main, .content{ min-width:0; }
  .ledger-panel{ min-width:0; }

  .dashboard-charts{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:1.25rem;
    align-items:stretch;
    margin-top:1.25rem;
  }

  .chart-panel{
    min-width:0;
    overflow:hidden;
    display:flex;
    flex-direction:column;
  }

  /* style.css adds a top margin between adjacent .ledger-panel siblings; inside this
     grid that would push the second panel down and misalign the pair. */
  .dashboard-charts > .ledger-panel{ margin-top:0; }

  /* Chart.js needs a wrapper with an explicit height + position:relative.
     The canvas then fills it and redraws whenever the wrapper is resized. */
  .chart-container{
    position:relative;
    flex:1 1 auto;
    width:100%;
    min-width:0;
    height:300px;
  }

  .chart-container canvas{
    display:block;
    max-width:100%;
  }

  .chart-empty{
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:200px;
    text-align:center;
  }

  /* Wide tables scroll inside their own panel instead of widening the page. */
  .table-scroll{
    width:100%;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }
  .table-scroll table.ledger{ min-width:0; }

  @media (max-width:900px){
    .dashboard-charts{ grid-template-columns:minmax(0, 1fr); }
    .chart-container{ height:280px; }
  }

  @media (max-width:600px){
    .chart-container{ height:260px; }
    .dashboard-charts{ gap:1rem; }
    .ledger-panel{ padding:1.1rem; }

    /* Compact tables so every column (especially Amount) fits without sideways scrolling. */
    .table-scroll .ledger tbody td{ padding:.6rem .3rem; font-size:.8rem; }
    .table-scroll .ledger thead th{ padding:.5rem .3rem; font-size:.62rem; letter-spacing:.02em; }
    .table-scroll .tag{ padding:.2em .5em; font-size:.68rem; }
    .stat-value{ overflow-wrap:anywhere; }
  }
</style>
  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Dashboard</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Overview</div>
        <h1 class="page-title">Dashboard</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <!-- ===== This month ===== -->
      <div class="panel-sub" style="margin-bottom:.4rem;"><?= htmlspecialchars($thisMonthLabel) ?> — this month</div>
      <div class="stat-row">
        <div class="stat">
          <div class="stat-label">Total income</div>
          <div class="stat-value figure"><?= fmtMoney($totalIncome) ?></div>
          <div class="stat-sub"><?= htmlspecialchars($thisMonthLabel) ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Total expenses</div>
          <div class="stat-value figure"><?= fmtMoney($totalExpenses) ?></div>
          <div class="stat-sub"><?= htmlspecialchars($thisMonthLabel) ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Savings</div>
          <div class="stat-value figure" style="color:<?= $savings >= 0 ? 'var(--gain)' : 'var(--loss)' ?>"><?= fmtMoney($savings) ?></div>
          <div class="stat-sub <?= $savings >= 0 ? 'gain' : 'loss' ?>"><?= $totalIncome > 0 ? $savingsPct.'% of income' : 'No income recorded yet' ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Budget status</div>
          <div class="stat-value figure" style="color:<?= $overBudgetCount > 0 ? 'var(--loss)' : 'var(--gain)' ?>"><?= $overBudgetCount > 0 ? $overBudgetCount.' over' : 'On track' ?></div>
          <div class="stat-sub"><?= count($budgetStatus) ?> categories budgeted</div>
        </div>
      </div>

      <!-- ===== All-time grand totals ===== -->
      <div class="panel-sub" style="margin:1.5rem 0 .4rem;">All months on record — grand totals</div>
      <div class="stat-row">
        <div class="stat">
          <div class="stat-label">Grand income</div>
          <div class="stat-value figure"><?= fmtMoney($grand['grand_income']) ?></div>
          <div class="stat-sub"><?= $grand['first_month'] ? date('M Y', strtotime($grand['first_month'])).' – '.date('M Y', strtotime($grand['last_month'])) : 'No records yet' ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Grand expenses</div>
          <div class="stat-value figure"><?= fmtMoney($grand['grand_expenses']) ?></div>
          <div class="stat-sub"><?= $grand['first_month'] ? date('M Y', strtotime($grand['first_month'])).' – '.date('M Y', strtotime($grand['last_month'])) : 'No records yet' ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Grand savings</div>
          <div class="stat-value figure" style="color:<?= (float)$grand['grand_savings'] >= 0 ? 'var(--gain)' : 'var(--loss)' ?>"><?= fmtMoney($grand['grand_savings']) ?></div>
          <div class="stat-sub gain"><?= (float)$grand['grand_income'] > 0 ? round(((float)$grand['grand_savings'] / (float)$grand['grand_income']) * 100).'% of income kept' : '\u2014' ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Net worth trend</div>
          <div class="stat-value figure" style="color:<?= (float)$grand['grand_savings'] >= 0 ? 'var(--gain)' : 'var(--loss)' ?>"><?= (float)$grand['grand_savings'] >= 0 ? '&#8599; Growing' : '&#8600; Declining' ?></div>
          <div class="stat-sub"><?= (int)$grand['months_tracked'] ?> month<?= (int)$grand['months_tracked'] == 1 ? '' : 's' ?> tracked</div>
        </div>
      </div>

      <!-- ===== Charts: current month only ===== -->
      <div class="dashboard-charts">

        <!-- Income vs expenses (current month) -->
        <div class="ledger-panel chart-panel">
          <div class="panel-head">
            <div>
              <div class="panel-sub"><?= htmlspecialchars($thisMonthLabel) ?></div>
              <h3>Income vs expenses</h3>
            </div>
          </div>

          <?php if(!$hasMonthActivity): ?>
            <div class="table-empty chart-empty">No income or expenses recorded this month.</div>
          <?php else: ?>
            <div class="chart-container">
              <canvas id="ieChart" role="img" aria-label="Income versus expenses for <?= htmlspecialchars($thisMonthLabel) ?>"></canvas>
            </div>
          <?php endif; ?>
        </div>

        <!-- Spending by category (current month) -->
        <div class="ledger-panel chart-panel">
          <div class="panel-head">
            <div>
              <div class="panel-sub"><?= htmlspecialchars($thisMonthLabel) ?></div>
              <h3>Spending by category</h3>
            </div>
          </div>

          <?php if(count($catBreakdown) === 0): ?>
            <div class="table-empty chart-empty">No expenses recorded this month.</div>
          <?php else: ?>
            <div class="chart-container">
              <canvas id="catChart" role="img" aria-label="Spending by category for <?= htmlspecialchars($thisMonthLabel) ?>"></canvas>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <div class="ledger-panel" style="margin-top:1.25rem;">
        <div class="panel-head">
          <div><h3>Recent transactions</h3></div>
          <a href="transactions.php" style="font-size:.82rem;">View all transactions &rarr;</a>
        </div>
        <?php if(count($recentTxns) === 0): ?>
        <div class="table-empty">No transactions yet. Add your first income or expense to see it here.</div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="ledger">
          <thead><tr><th>Date</th><th>Description</th><th>Category</th><th class="num">Amount</th></tr></thead>
          <tbody>
            <?php foreach($recentTxns as $t):
                $isIncome = $t['txn_type'] === 'income';
            ?>
            <tr>
              <td><?= fmtDate($t['txn_date']) ?></td>
              <td><?= htmlspecialchars($t['description'] ?: '\u2014') ?></td>
              <td><span class="tag neutral"><?= htmlspecialchars($t['category_name'] ?: 'Uncategorized') ?></span></td>
              <td class="num" style="color:<?= $isIncome ? 'var(--gain)' : 'var(--loss)' ?>"><?= ($isIncome ? '+' : '-') . fmtMoney($t['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
(function () {

  // If the Chart.js CDN is blocked/offline, say so instead of leaving blank boxes.
  if (typeof Chart === 'undefined') {
    document.querySelectorAll('.chart-container').forEach(function (box) {
      box.innerHTML = '<div class="table-empty chart-empty">Charts could not be loaded. Check your internet connection and refresh.</div>';
      box.style.height = 'auto';
    });
    return;
  }

  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.color = '#3B4A63';

  const money = function (n) {
    return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };
  const compact = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', notation: 'compact', maximumFractionDigits: 1 });

  const thisMonthLabel = <?= json_encode($thisMonthLabel, $jsonFlags) ?>;

  // Draws the amount above each bar so values are readable without hovering (works on touch screens).
  const barValueLabels = {
    id: 'barValueLabels',
    afterDatasetsDraw: function (chart) {
      const ctx = chart.ctx;
      const size = chart.width < 380 ? 11 : 13;
      ctx.save();
      ctx.font = '600 ' + size + 'px Inter, sans-serif';
      ctx.fillStyle = '#16233A';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'bottom';
      chart.getDatasetMeta(0).data.forEach(function (bar, i) {
        ctx.fillText(money(chart.data.datasets[0].data[i]), bar.x, bar.y - 6);
      });
      ctx.restore();
    }
  };

  /* ---------- Income vs expenses — current month ---------- */
  const ieCanvas = document.getElementById('ieChart');
  if (ieCanvas) {
    new Chart(ieCanvas, {
      type: 'bar',
      data: {
        labels: ['Income', 'Expenses'],
        datasets: [{
          label: thisMonthLabel,
          data: [<?= json_encode($totalIncome) ?>, <?= json_encode($totalExpenses) ?>],
          backgroundColor: ['#3F6D52', '#A63B2E'],
          borderRadius: 6,
          maxBarThickness: 90
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 8 } },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: function () { return thisMonthLabel; },
              label: function (c) { return ' ' + c.label + ': ' + money(c.raw); }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grace: '15%',
            grid: { color: '#E6E0D2' },
            ticks: { maxTicksLimit: 6, callback: function (v) { return compact.format(v); } }
          },
          x: { grid: { display: false } }
        }
      },
      plugins: [barValueLabels]
    });
  }

  /* ---------- Spending by category — current month ---------- */
  const catCanvas = document.getElementById('catChart');
  if (catCanvas) {
    const catLabels = <?= json_encode($catLabels, $jsonFlags) ?>;
    const catTotals = <?= json_encode($catTotals, $jsonFlags) ?>;
    const catSum = catTotals.reduce(function (a, b) { return a + b; }, 0);

    // With many categories the legend needs more room; grow the box so the donut stays a
    // usable size. With 6 or fewer categories the default CSS heights are used untouched.
    const catBox = catCanvas.parentElement;
    function fitCatBox() {
      if (catLabels.length <= 6) { catBox.style.height = ''; return; }
      const perRow = Math.max(1, Math.floor(catBox.clientWidth / 125));
      let items = 0;
      catLabels.forEach(function (l) { items += l.length > 22 ? 2 : 1; });   // long names take more room
      const legendRows = Math.ceil(items / perRow);
      catBox.style.height = Math.min(200 + legendRows * 22, 560) + 'px';
    }
    fitCatBox();
    let fitTimer;
    window.addEventListener('resize', function () { clearTimeout(fitTimer); fitTimer = setTimeout(fitCatBox, 120); });

    new Chart(catCanvas, {
      type: 'doughnut',
      data: {
        labels: catLabels,
        datasets: [{
          data: catTotals,
          backgroundColor: ['#A5732E', '#3F6D52', '#16233A', '#A63B2E', '#7C879B', '#C9A15A',
                            '#5C7A6B', '#8A5D22', '#4F6D9A', '#B85C7A', '#6B8E8E', '#9A8F5C'],
          borderColor: '#FBFAF6',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, padding: 10, font: { size: 11 } }
          },
          tooltip: {
            callbacks: {
              label: function (c) {
                const pct = catSum > 0 ? (c.raw / catSum * 100).toFixed(1) : '0.0';
                return ' ' + c.label + ': ' + money(c.raw) + ' (' + pct + '%)';
              }
            }
          }
        }
      }
    });
  }

})();
</script>
</body>
</html>
