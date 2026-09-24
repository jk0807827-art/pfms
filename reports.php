<?php
$pageTitle = "Reports - Personal Finance Management System";

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';
require_once 'include/report_export.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

// -------------------------------------------------
// EXPORTS  (?export=xlsx|pdf|csv&month=YYYY-MM)
// -------------------------------------------------
$exportFormat = $_GET['export'] ?? '';
if(in_array($exportFormat, ['csv', 'xlsx', 'pdf'], true)){

    $month = pfms_valid_month($_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');

    try{
        $report = pfms_report_data(
            $data,
            $userId,
            $month,
            $_SESSION['user_name']  ?? '',
            $_SESSION['user_email'] ?? ''
        );

        if($exportFormat === 'xlsx')      pfms_export_xlsx($report);
        elseif($exportFormat === 'pdf')   pfms_export_pdf($report);
        else                              pfms_export_csv($report);

    }catch(\Throwable $e){
        error_log('PFMS export failed: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Sorry, the export could not be generated. Please try again.";
        exit;
    }
}

// -------------------------------------------------
// PAGE DATA
// -------------------------------------------------
$defaultMonth = date('Y-m');
$selectedMonth = pfms_valid_month($_GET['month'] ?? '') ? $_GET['month'] : $defaultMonth;

$txnMonths = $data->get_available_months($userId);
$budgetMonths = array_unique(array_map(function($b){ return substr($b['budget_month'],0,7); }, $data->get_budget_status($userId)));
$months = array_unique(array_merge($txnMonths, $budgetMonths, [$defaultMonth, $selectedMonth]));
rsort($months);

$reportData = [];
foreach($months as $m){
    $summary = $data->get_monthly_summary($userId, $m);
    $budgetStatus = $data->get_budget_status($userId, $m);
    $catBreakdown = $data->get_category_breakdown($userId, $m);

    $budgetRows = [];
    foreach($budgetStatus as $b){
        $budgetRows[] = [
            'category_name' => $b['category_name'],
            'budget_amount' => (float)$b['budget_amount'],
            'spent'         => (float)$b['spent'],
        ];
    }

    $reportData[$m] = [
        'income'     => (float)$summary['total_income'],
        'expenses'   => (float)$summary['total_expenses'],
        'savings'    => (float)$summary['savings'],
        'budgets'    => $budgetRows,
        'cat_labels' => array_column($catBreakdown, 'name'),
        'cat_data'   => array_map('floatval', array_column($catBreakdown, 'total')),
    ];
}

// Safe for embedding inside an inline <script> (category names are user-entered).
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

$investmentTotals = $data->get_investment_totals($userId);
$investSummary = $conn->prepare("SELECT investment_type, holdings, total_invested, total_current_value, total_gain_loss FROM v_investment_summary WHERE user_id = :uid ORDER BY investment_type");
$investSummary->execute([':uid' => $userId]);
$investmentBreakdown = $investSummary->fetchAll(PDO::FETCH_ASSOC);

$gainTotal = (float)$investmentTotals['total_gain_loss'];

$currentPage = basename($_SERVER['PHP_SELF']);
include 'header.php';
?>

<style>
  /* ---------- Reports: responsive layout & charts ---------- */
  .main, .content{ min-width:0; }
  .ledger-panel{ min-width:0; }

  .report-head{ flex-wrap:wrap; align-items:center; }
  .report-actions{ display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; }
  .report-actions .field-control{ width:auto; max-width:100%; }

  .report-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:1.25rem;
    align-items:start;
  }
  /* style.css puts a top margin between adjacent .ledger-panel siblings, which would
     push the second panel of a side-by-side pair down. */
  .report-grid > .ledger-panel{ margin-top:0; }
  .report-grid > div{ min-width:0; }

  .report-chart-title{ font-size:1rem; margin:0 0 .6rem; }

  .chart-container{
    position:relative;
    width:100%;
    min-width:0;
    height:300px;
  }
  .chart-container canvas{ display:block; max-width:100%; }
  .chart-empty{ display:flex; align-items:center; justify-content:center; min-height:200px; text-align:center; }

  .table-scroll{ width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
  .table-scroll table.ledger{ min-width:0; }

  @media (max-width:900px){
    .report-grid{ grid-template-columns:minmax(0, 1fr); }
    .chart-container{ height:280px; }
  }
  @media (max-width:600px){
    .chart-container{ height:260px; }
    .ledger-panel{ padding:1.1rem; }

    /* Compact tables + stat cards so every column and full amounts fit on a phone. */
    .table-scroll .ledger tbody td{ padding:.6rem .3rem; font-size:.8rem; }
    .table-scroll .ledger thead th{ padding:.5rem .3rem; font-size:.62rem; letter-spacing:.02em; }
    .table-scroll .tag{ padding:.2em .5em; font-size:.68rem; }
    #reportStats .stat{ padding:.9rem .8rem; }
    #reportStats .stat-value{ font-size:1.3rem; overflow-wrap:anywhere; }
    .report-actions{ width:100%; }
    .report-actions .field-control{ flex:1 1 100%; }
    .report-actions .btn-ledger{ flex:1 1 auto; text-align:center; }
  }
</style>

  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Reports</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Monthly summary</div>
        <h1 class="page-title">Reports</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <div class="ledger-panel">
        <div class="panel-head report-head">
          <div>
            <div class="panel-sub">Choose a month to review</div>
            <h3>Monthly financial report</h3>
          </div>
          <div class="report-actions">
            <select class="field-control" id="monthSelect" aria-label="Report month">
              <?php foreach($months as $m): ?>
              <option value="<?= htmlspecialchars($m) ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= date('F Y', strtotime($m.'-01')) ?></option>
              <?php endforeach; ?>
            </select>
            <a class="btn-ledger sm" data-export="xlsx" href="reports.php?export=xlsx&month=<?= urlencode($selectedMonth) ?>" title="Formatted workbook: summary, transactions, budgets, investments">Export Excel</a>
            <a class="btn-ledger sm" data-export="pdf" href="reports.php?export=pdf&month=<?= urlencode($selectedMonth) ?>" title="Printable report">Export PDF</a>
            <a class="btn-ledger ghost sm" data-export="csv" href="reports.php?export=csv&month=<?= urlencode($selectedMonth) ?>" title="Plain transaction list">Export CSV</a>
          </div>
        </div>

        <div class="stat-row" id="reportStats">
          <div class="stat"><div class="stat-label">Total income</div><div class="stat-value figure" id="statIncome">$0.00</div><div class="stat-sub" id="statMonth"></div></div>
          <div class="stat"><div class="stat-label">Total expenses</div><div class="stat-value figure" id="statExpenses">$0.00</div><div class="stat-sub" id="statMonth2"></div></div>
          <div class="stat"><div class="stat-label">Total savings</div><div class="stat-value figure" id="statSavings">$0.00</div><div class="stat-sub" id="statSavingsSub"></div></div>
          <div class="stat"><div class="stat-label">Total invested</div><div class="stat-value figure"><?= pfms_money($investmentTotals['total_invested']) ?></div><div class="stat-sub">All time (portfolio)</div></div>
        </div>

        <div class="report-grid">
          <div>
            <h3 class="report-chart-title">Income vs expenses</h3>
            <div class="chart-container" id="ieBox"><canvas id="ieChart"></canvas></div>
            <div class="table-empty chart-empty" id="ieEmpty" style="display:none;">No income or expenses recorded this month.</div>
          </div>
          <div>
            <h3 class="report-chart-title">Expense by category</h3>
            <div class="chart-container" id="catBox"><canvas id="catChart"></canvas></div>
            <div class="table-empty chart-empty" id="catEmpty" style="display:none;">No expenses recorded this month.</div>
          </div>
        </div>
      </div>

      <div class="report-grid" style="margin-top:1.25rem;">
        <div class="ledger-panel">
          <div class="panel-head"><div><h3>Budget vs actual</h3></div></div>
          <div class="table-scroll">
            <table class="ledger">
              <thead><tr><th>Category</th><th class="num">Budget</th><th class="num">Actual</th><th class="num">Variance</th></tr></thead>
              <tbody id="budgetTbody"></tbody>
            </table>
          </div>
          <div class="table-empty" id="budgetEmpty" style="display:none;">No budgets set for this month.</div>
        </div>
        <div class="ledger-panel">
          <div class="panel-head"><div><h3>Investment summary</h3><div class="panel-sub">All investments to date</div></div></div>
          <div class="table-scroll">
            <table class="ledger">
              <tbody>
                <tr><td>Total invested</td><td class="num"><?= pfms_money($investmentTotals['total_invested']) ?></td></tr>
                <tr><td>Current value</td><td class="num"><?= pfms_money($investmentTotals['total_current_value']) ?></td></tr>
                <tr><td>Gain / loss</td><td class="num" style="color:<?= $gainTotal >= 0 ? 'var(--gain)' : 'var(--loss)' ?>"><?= pfms_money($gainTotal, true) ?></td></tr>
              </tbody>
            </table>
          </div>
          <?php if(count($investmentBreakdown) > 0): ?>
          <div class="table-scroll" style="margin-top:1rem;">
            <table class="ledger">
              <thead><tr><th>Type</th><th class="num">Holdings</th><th class="num">Invested</th><th class="num">Current</th></tr></thead>
              <tbody>
                <?php foreach($investmentBreakdown as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['investment_type'] ?: 'Uncategorized') ?></td>
                  <td class="num"><?= (int)$row['holdings'] ?></td>
                  <td class="num"><?= pfms_money($row['total_invested']) ?></td>
                  <td class="num"><?= pfms_money($row['total_current_value']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {

  const REPORTS = <?= json_encode($reportData, $jsonFlags) ?>;
  const chartsOk = (typeof Chart !== 'undefined');

  if (chartsOk) {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#3B4A63';
  }

  let ieChart, catChart;
  const palette = ['#A5732E','#3F6D52','#16233A','#A63B2E','#7C879B','#C9A15A','#5C7A6B','#8A5D22','#4F6D9A','#B85C7A','#6B8E8E','#9A8F5C'];
  const compact = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', notation: 'compact', maximumFractionDigits: 1 });

  // "$1,234.50" and "-$50.00" (never "$-50.00")
  function fmt(n) {
    n = Number(n) || 0;
    const s = '$' + Math.abs(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return (n < 0 && Math.round(n * 100) !== 0 ? '-' : '') + s;
  }

  // Category names are user-entered: never put them into innerHTML unescaped.
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function monthLabel(ym) {
    const p = ym.split('-');
    return new Date(Number(p[0]), Number(p[1]) - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  }

  const barValueLabels = {
    id: 'barValueLabels',
    afterDatasetsDraw: function (chart) {
      const ctx = chart.ctx;
      ctx.save();
      ctx.font = '600 ' + (chart.width < 380 ? 11 : 13) + 'px Inter, sans-serif';
      ctx.fillStyle = '#16233A';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'bottom';
      chart.getDatasetMeta(0).data.forEach(function (bar, i) {
        ctx.fillText(fmt(chart.data.datasets[0].data[i]), bar.x, bar.y - 6);
      });
      ctx.restore();
    }
  };

  function show(id, on) { document.getElementById(id).style.display = on ? '' : 'none'; }

  function fitCatBox(count, box) {
    if (count <= 6) { box.style.height = ''; return; }
    const perRow = Math.max(1, Math.floor(box.clientWidth / 125));
    box.style.height = Math.min(200 + Math.ceil(count / perRow) * 22, 560) + 'px';
  }

  function draw(ym) {
    const r = REPORTS[ym];
    if (!r) return;
    const label = monthLabel(ym);

    document.getElementById('statIncome').textContent = fmt(r.income);
    document.getElementById('statExpenses').textContent = fmt(r.expenses);
    document.getElementById('statMonth').textContent = label;
    document.getElementById('statMonth2').textContent = label;

    const sav = document.getElementById('statSavings');
    sav.textContent = fmt(r.savings);
    sav.style.color = r.savings >= 0 ? 'var(--gain)' : 'var(--loss)';
    const savSub = document.getElementById('statSavingsSub');
    savSub.textContent = r.income > 0 ? Math.round(r.savings / r.income * 100) + '% of income' : 'No income recorded';
    savSub.className = 'stat-sub ' + (r.savings >= 0 ? 'gain' : 'loss');

    document.querySelectorAll('[data-export]').forEach(function (a) {
      a.href = 'reports.php?export=' + a.getAttribute('data-export') + '&month=' + encodeURIComponent(ym);
    });

    // ---- charts
    if (ieChart) { ieChart.destroy(); ieChart = null; }
    if (catChart) { catChart.destroy(); catChart = null; }

    const hasActivity = r.income > 0 || r.expenses > 0;
    const hasCats = r.cat_labels.length > 0;
    show('ieBox', hasActivity && chartsOk);   show('ieEmpty', !hasActivity || !chartsOk);
    show('catBox', hasCats && chartsOk);      show('catEmpty', !hasCats || !chartsOk);
    if (!chartsOk) {
      document.getElementById('ieEmpty').textContent = document.getElementById('catEmpty').textContent = 'Charts could not be loaded. Check your internet connection and refresh.';
      return;
    }
    document.getElementById('ieEmpty').textContent = 'No income or expenses recorded this month.';
    document.getElementById('catEmpty').textContent = 'No expenses recorded this month.';

    if (hasActivity) {
      ieChart = new Chart(document.getElementById('ieChart'), {
        type: 'bar',
        data: {
          labels: ['Income', 'Expenses'],
          datasets: [{ label: label, data: [r.income, r.expenses], backgroundColor: ['#3F6D52', '#A63B2E'], borderRadius: 6, maxBarThickness: 90 }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          layout: { padding: { top: 8 } },
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { title: function () { return label; }, label: function (c) { return ' ' + c.label + ': ' + fmt(c.raw); } } }
          },
          scales: {
            y: { beginAtZero: true, grace: '15%', grid: { color: '#E6E0D2' }, ticks: { maxTicksLimit: 6, callback: function (v) { return compact.format(v); } } },
            x: { grid: { display: false } }
          }
        },
        plugins: [barValueLabels]
      });
    }

    if (hasCats) {
      const total = r.cat_data.reduce(function (a, b) { return a + b; }, 0);
      fitCatBox(r.cat_labels.length, document.getElementById('catBox'));
      catChart = new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: { labels: r.cat_labels, datasets: [{ data: r.cat_data, backgroundColor: palette, borderColor: '#FBFAF6', borderWidth: 2 }] },
        options: {
          responsive: true, maintainAspectRatio: false, cutout: '58%',
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } },
            tooltip: { callbacks: { label: function (c) {
              return ' ' + c.label + ': ' + fmt(c.raw) + ' (' + (total > 0 ? (c.raw / total * 100).toFixed(1) : '0.0') + '%)';
            } } }
          }
        }
      });
    }

    // ---- budget vs actual
    const tbody = document.getElementById('budgetTbody');
    tbody.innerHTML = '';
    r.budgets.forEach(function (b) {
      const variance = b.budget_amount - b.spent;
      const tr = document.createElement('tr');
      tr.innerHTML = '<td>' + esc(b.category_name) + '</td>' +
        '<td class="num">' + fmt(b.budget_amount) + '</td>' +
        '<td class="num">' + fmt(b.spent) + '</td>' +
        '<td class="num" style="color:' + (variance >= 0 ? 'var(--gain)' : 'var(--loss)') + '">' + (variance > 0 ? '+' : '') + fmt(variance) + '</td>';
      tbody.appendChild(tr);
    });
    show('budgetEmpty', r.budgets.length === 0);
  }

  let resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      const r = REPORTS[document.getElementById('monthSelect').value];
      if (r && chartsOk) fitCatBox(r.cat_labels.length, document.getElementById('catBox'));
    }, 120);
  });

  const select = document.getElementById('monthSelect');
  select.addEventListener('change', function () { draw(this.value); });
  draw(select.value);

})();
</script>
</body>
</html>
