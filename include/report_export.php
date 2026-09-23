<?php
/**
 * Report exports for PFMS: CSV, Excel (.xlsx) and PDF.
 *
 * pfms_report_data() builds ONE data structure for a month; every export format is
 * generated from it, so the numbers in the CSV, the workbook and the PDF always agree.
 */

require_once __DIR__ . '/xlsx_writer.php';

/* ---------------------------------------------------------------- helpers -- */

function pfms_valid_month($m)
{
    return is_string($m) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m) === 1;
}

/** "$1,234.50", negatives as "-$50.00" (not "$-50.00"). */
function pfms_money($n, $showPlus = false)
{
    $n = round((float)$n, 2);
    $s = '$' . number_format(abs($n), 2);
    if ($n < 0) {
        return '-' . $s;
    }
    return ($showPlus && $n > 0 ? '+' : '') . $s;
}

function pfms_pct($num, $den, $decimals = 1)
{
    return $den > 0 ? number_format($num / $den * 100, $decimals) . '%' : '0.0%';
}

function pfms_send_download($body, $mime, $filename)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($body));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    echo $body;
    exit;
}

/* ------------------------------------------------------------ data builder -- */

/**
 * @param core   $data   the app's core data class
 * @param int    $userId
 * @param string $month  YYYY-MM
 */
function pfms_report_data($data, $userId, $month, $userName = '', $userEmail = '')
{
    $ts = strtotime($month . '-01');

    // --- this month's transactions, oldest first (income before expense on the same day)
    $txns = [];
    foreach ($data->get_transactions($userId) as $t) {
        if (substr((string)$t['txn_date'], 0, 7) !== $month) {
            continue;
        }
        $txns[] = [
            'date'     => substr((string)$t['txn_date'], 0, 10),
            'type'     => $t['txn_type'] === 'income' ? 'income' : 'expense',
            'category' => ($t['category_name'] !== null && $t['category_name'] !== '') ? $t['category_name'] : 'Uncategorized',
            'desc'     => (string)$t['description'],
            'amount'   => round((float)$t['amount'], 2),
            'id'       => (int)$t['txn_id'],
        ];
    }
    usort($txns, function ($a, $b) {
        return [$a['date'], $a['type'] === 'income' ? 0 : 1, $a['id']] <=> [$b['date'], $b['type'] === 'income' ? 0 : 1, $b['id']];
    });

    // --- totals + category breakdowns, derived from the same rows that are listed
    $income = 0.0;
    $expenses = 0.0;
    $incomeByCat = [];
    $expenseByCat = [];
    foreach ($txns as $t) {
        if ($t['type'] === 'income') {
            $income += $t['amount'];
            $incomeByCat[$t['category']] = ($incomeByCat[$t['category']] ?? 0) + $t['amount'];
        } else {
            $expenses += $t['amount'];
            $expenseByCat[$t['category']] = ($expenseByCat[$t['category']] ?? 0) + $t['amount'];
        }
    }
    arsort($incomeByCat);
    arsort($expenseByCat);
    $income = round($income, 2);
    $expenses = round($expenses, 2);

    // --- budgets for the month
    $budgets = [];
    foreach ($data->get_budget_status($userId, $month) as $b) {
        $budget = round((float)$b['budget_amount'], 2);
        $spent = round((float)$b['spent'], 2);
        $budgets[] = [
            'category' => $b['category_name'],
            'budget'   => $budget,
            'actual'   => $spent,
            'variance' => round($budget - $spent, 2),
        ];
    }

    // --- investments (portfolio snapshot, not month-specific)
    $investments = [];
    foreach ($data->get_investments($userId) as $i) {
        $investments[] = [
            'name'     => $i['name'],
            'type'     => $i['category_name'] ?: 'Uncategorized',
            'date'     => $i['purchase_date'] ? substr((string)$i['purchase_date'], 0, 10) : '',
            'invested' => round((float)$i['amount_invested'], 2),
            'current'  => round((float)$i['current_value'], 2),
            'gain'     => round((float)$i['gain_loss'], 2),
        ];
    }

    return [
        'month'        => $month,
        'label'        => date('F Y', $ts),
        'user_name'    => $userName,
        'user_email'   => $userEmail,
        'generated'    => date('d M Y, H:i'),
        'as_of'        => date('d M Y'),
        'income'       => $income,
        'expenses'     => $expenses,
        'savings'      => round($income - $expenses, 2),
        'income_cats'  => $incomeByCat,
        'expense_cats' => $expenseByCat,
        'txns'         => $txns,
        'budgets'      => $budgets,
        'investments'  => $investments,
    ];
}

/* -------------------------------------------------------------------- CSV -- */

/** Stop spreadsheet apps from executing text that starts with = + - @ as a formula. */
function pfms_csv_safe($v)
{
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$v);
    if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) {
        return "'" . $v;
    }
    return $v;
}

function pfms_export_csv($r)
{
    $out = fopen('php://temp', 'w+');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents/symbols correctly

    fputcsv($out, ['Date', 'Type', 'Category', 'Description', 'Amount'], ',', '"', '');
    foreach ($r['txns'] as $t) {
        fputcsv($out, [
            $t['date'],
            ucfirst($t['type']),
            pfms_csv_safe($t['category']),
            pfms_csv_safe($t['desc']),
            // Signed: income positive, expenses negative -> the column sums to net savings
            number_format($t['type'] === 'income' ? $t['amount'] : -$t['amount'], 2, '.', ''),
        ], ',', '"', '');
    }

    rewind($out);
    $body = stream_get_contents($out);
    fclose($out);

    pfms_send_download($body, 'text/csv; charset=utf-8', 'pfms-report-' . $r['month'] . '.csv');
}

/* ------------------------------------------------------------------ Excel -- */

function pfms_export_xlsx($r)
{
    $NAVY = '16233A'; $INK2 = '3B4A63'; $PAPER = 'EEEAE0'; $FAINT = '7C879B';
    $GAIN = '3F6D52'; $LOSS = 'A63B2E'; $LINE = 'D8D1BF';

    $MONEY = '"$"#,##0.00;-"$"#,##0.00';
    $DATE  = 'dd mmm yyyy';
    $PCT   = '0.0%';

    $x = new PfmsXlsx();
    $x->setProperties('PFMS report - ' . $r['label'], 'PFMS');

    $st = [
        'title'   => $x->style(['b' => true, 'sz' => 18]),
        'sub'     => $x->style(['b' => true, 'sz' => 13, 'color' => $INK2]),
        'meta'    => $x->style(['i' => true, 'sz' => 9, 'color' => $FAINT]),
        'note'    => $x->style(['i' => true, 'sz' => 9, 'color' => $FAINT, 'wrap' => true, 'v' => 'top']),
        'section' => $x->style(['b' => true, 'sz' => 12, 'border' => 'bottom', 'bcolor' => $NAVY]),
        'th'      => $x->style(['b' => true, 'color' => 'FFFFFF', 'bg' => $NAVY, 'h' => 'left',  'wrap' => true]),
        'thr'     => $x->style(['b' => true, 'color' => 'FFFFFF', 'bg' => $NAVY, 'h' => 'right', 'wrap' => true]),
        'thc'     => $x->style(['b' => true, 'color' => 'FFFFFF', 'bg' => $NAVY, 'h' => 'center', 'wrap' => true]),
        'txt'     => $x->style(['border' => 'bottom']),
        'txtw'    => $x->style(['border' => 'bottom', 'wrap' => true]),
        'date'    => $x->style(['border' => 'bottom', 'fmt' => $DATE, 'h' => 'left']),
        'money'   => $x->style(['border' => 'bottom', 'fmt' => $MONEY]),
        'money_g' => $x->style(['border' => 'bottom', 'fmt' => $MONEY, 'color' => $GAIN]),
        'money_l' => $x->style(['border' => 'bottom', 'fmt' => $MONEY, 'color' => $LOSS]),
        'pct'     => $x->style(['border' => 'bottom', 'fmt' => $PCT]),
        'pct_g'   => $x->style(['border' => 'bottom', 'fmt' => $PCT, 'color' => $GAIN]),
        'pct_l'   => $x->style(['border' => 'bottom', 'fmt' => $PCT, 'color' => $LOSS]),
        'ok'      => $x->style(['border' => 'bottom', 'b' => true, 'color' => $GAIN, 'h' => 'center']),
        'over'    => $x->style(['border' => 'bottom', 'b' => true, 'color' => $LOSS, 'h' => 'center']),
        't_txt'   => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom']),
        't_money' => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'fmt' => $MONEY]),
        't_money_g' => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'fmt' => $MONEY, 'color' => $GAIN]),
        't_money_l' => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'fmt' => $MONEY, 'color' => $LOSS]),
        't_pct'   => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'fmt' => $PCT]),
        't_pct_g' => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'fmt' => $PCT, 'color' => $GAIN]),
        't_pct_l' => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'fmt' => $PCT, 'color' => $LOSS]),
        't_ok'    => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'color' => $GAIN, 'h' => 'center']),
        't_over'  => $x->style(['b' => true, 'bg' => $PAPER, 'border' => 'topbottom', 'color' => $LOSS, 'h' => 'center']),
    ];

    $prepared = 'Prepared for ' . ($r['user_name'] !== '' ? $r['user_name'] : 'PFMS user')
              . ($r['user_email'] !== '' ? ' (' . $r['user_email'] . ')' : '')
              . '   |   Generated ' . $r['generated'];

    /* ============================ Sheet 1: Summary ============================ */
    $s = $x->addSheet('Summary');
    $s->hideGridlines();
    $s->tabColor($NAVY);
    $s->colWidths([1 => 38, 2 => 18, 3 => 16]);

    $s->text(1, 1, 'Personal Finance Report', $st['title']);
    $s->rowHeight(1, 28);
    $s->text(2, 1, $r['label'], $st['sub']);
    $s->text(3, 1, $prepared, $st['meta']);

    $section = function ($sheet, $row, $text) use ($st) {
        $sheet->text($row, 1, $text, $st['section']);
        $sheet->blank($row, 2, $st['section']);
        $sheet->blank($row, 3, $st['section']);
        $sheet->rowHeight($row, 22);
    };

    // Monthly overview
    $section($s, 5, 'Monthly overview');
    $s->text(6, 1, 'Metric', $st['th']);
    $s->text(6, 2, 'Amount', $st['thr']);
    $s->text(6, 3, '% of income', $st['thr']);

    $inc = $r['income'];
    $exp = $r['expenses'];
    $sav = $r['savings'];
    $s->text(7, 1, 'Total income', $st['txt']);
    $s->num(7, 2, $inc, $st['money_g']);
    $s->blank(7, 3, $st['pct']);
    $s->text(8, 1, 'Total expenses', $st['txt']);
    $s->num(8, 2, $exp, $st['money_l']);
    $s->formula(8, 3, 'IF(B7=0,0,B8/B7)', $inc > 0 ? $exp / $inc : 0, $st['pct']);
    $s->text(9, 1, 'Net savings (income - expenses)', $st['t_txt']);
    $s->formula(9, 2, 'B7-B8', $sav, $sav >= 0 ? $st['t_money_g'] : $st['t_money_l']);
    $s->formula(9, 3, 'IF(B7=0,0,B9/B7)', $inc > 0 ? $sav / $inc : 0, $sav >= 0 ? $st['t_pct_g'] : $st['t_pct_l']);

    // Category tables (same layout for income and expenses)
    $catTable = function ($heading, $items, $total, $pctHeader, $emptyMsg, $moneyStyle, $totalStyle, $startRow) use ($s, $st, $section) {
        $section($s, $startRow, $heading);
        $s->text($startRow + 1, 1, 'Category', $st['th']);
        $s->text($startRow + 1, 2, 'Amount', $st['thr']);
        $s->text($startRow + 1, 3, $pctHeader, $st['thr']);

        if (!$items) {
            $s->text($startRow + 2, 1, $emptyMsg, $st['note']);
            return $startRow + 4;
        }

        $first = $startRow + 2;
        $last = $first + count($items) - 1;
        $tot = $last + 1;
        $row = $first;
        foreach ($items as $name => $amt) {
            $s->text($row, 1, $name, $st['txt']);
            $s->num($row, 2, $amt, $moneyStyle);
            $s->formula($row, 3, 'IF($B$' . $tot . '=0,0,B' . $row . '/$B$' . $tot . ')', $total > 0 ? $amt / $total : 0, $st['pct']);
            $row++;
        }
        $s->text($tot, 1, 'Total', $st['t_txt']);
        $s->formula($tot, 2, 'SUM(B' . $first . ':B' . $last . ')', $total, $totalStyle);
        $s->formula($tot, 3, 'SUM(C' . $first . ':C' . $last . ')', $total > 0 ? 1 : 0, $st['t_pct']);
        return $tot + 2;
    };

    $next = $catTable('Income by category', $r['income_cats'], $inc, '% of income',
        'No income recorded this month.', $st['money_g'], $st['t_money_g'], 11);
    $next = $catTable('Expenses by category', $r['expense_cats'], $exp, '% of expenses',
        'No expenses recorded this month.', $st['money_l'], $st['t_money_l'], $next);

    /* ========================== Sheet 2: Transactions ========================= */
    $t = $x->addSheet('Transactions');
    $t->tabColor('3F6D52');
    $t->colWidths([1 => 14, 2 => 11, 3 => 22, 4 => 46, 5 => 16, 6 => 16]);
    $t->landscape();
    $t->text(1, 1, 'Transactions - ' . $r['label'], $st['title']);
    $t->rowHeight(1, 28);
    $n = count($r['txns']);
    $t->text(2, 1, $n . ' transaction' . ($n === 1 ? '' : 's') . '   |   oldest first   |   income and expenses shown in separate columns', $st['meta']);

    $hdr = 4;
    $t->text($hdr, 1, 'Date', $st['th']);
    $t->text($hdr, 2, 'Type', $st['th']);
    $t->text($hdr, 3, 'Category', $st['th']);
    $t->text($hdr, 4, 'Description', $st['th']);
    $t->text($hdr, 5, 'Income', $st['thr']);
    $t->text($hdr, 6, 'Expense', $st['thr']);
    $t->rowHeight($hdr, 20);
    $t->freezeRows($hdr);
    $t->repeatRow($hdr);

    if ($n === 0) {
        $t->text($hdr + 1, 1, 'No transactions recorded for this month.', $st['note']);
    } else {
        $row = $hdr + 1;
        foreach ($r['txns'] as $tx) {
            $isInc = $tx['type'] === 'income';
            $t->date($row, 1, $tx['date'], $st['date']);
            $t->text($row, 2, $isInc ? 'Income' : 'Expense', $st['txt']);
            $t->text($row, 3, $tx['category'], $st['txt']);
            $t->text($row, 4, $tx['desc'], $st['txtw']);
            if ($isInc) {
                $t->num($row, 5, $tx['amount'], $st['money_g']);
                $t->blank($row, 6, $st['money']);
            } else {
                $t->blank($row, 5, $st['money']);
                $t->num($row, 6, $tx['amount'], $st['money_l']);
            }
            $row++;
        }
        $first = $hdr + 1;
        $last = $row - 1;
        $t->autoFilter('A' . $hdr . ':F' . $last);

        $t->text($row, 1, 'Total', $st['t_txt']);
        $t->blank($row, 2, $st['t_txt']);
        $t->blank($row, 3, $st['t_txt']);
        $t->blank($row, 4, $st['t_txt']);
        $t->formula($row, 5, 'SUM(E' . $first . ':E' . $last . ')', $inc, $st['t_money_g']);
        $t->formula($row, 6, 'SUM(F' . $first . ':F' . $last . ')', $exp, $st['t_money_l']);
        $row++;
        $t->text($row, 1, 'Net savings (income - expenses)', $st['t_txt']);
        $t->blank($row, 2, $st['t_txt']);
        $t->blank($row, 3, $st['t_txt']);
        $t->blank($row, 4, $st['t_txt']);
        $t->formula($row, 5, 'E' . ($row - 1) . '-F' . ($row - 1), $sav, $sav >= 0 ? $st['t_money_g'] : $st['t_money_l']);
        $t->blank($row, 6, $st['t_txt']);
    }

    /* ======================== Sheet 3: Budget vs Actual ======================= */
    $b = $x->addSheet('Budget vs Actual');
    $b->tabColor('E0565B');
    $b->colWidths([1 => 26, 2 => 16, 3 => 16, 4 => 16, 5 => 12, 6 => 16]);
    $b->text(1, 1, 'Budget vs actual - ' . $r['label'], $st['title']);
    $b->rowHeight(1, 28);
    $b->text(2, 1, 'Variance = Budget - Actual.  A negative variance means the category is over budget.', $st['meta']);
    $hdr = 4;
    $b->text($hdr, 1, 'Category', $st['th']);
    $b->text($hdr, 2, 'Budget', $st['thr']);
    $b->text($hdr, 3, 'Actual', $st['thr']);
    $b->text($hdr, 4, 'Variance', $st['thr']);
    $b->text($hdr, 5, '% used', $st['thr']);
    $b->text($hdr, 6, 'Status', $st['thc']);
    $b->rowHeight($hdr, 20);
    $b->freezeRows($hdr);

    if (!$r['budgets']) {
        $b->text($hdr + 1, 1, 'No budgets set for this month.', $st['note']);
    } else {
        $row = $hdr + 1;
        $first = $row;
        $sumB = 0; $sumA = 0;
        foreach ($r['budgets'] as $bd) {
            $over = $bd['variance'] < 0;
            $b->text($row, 1, $bd['category'], $st['txt']);
            $b->num($row, 2, $bd['budget'], $st['money']);
            $b->num($row, 3, $bd['actual'], $st['money']);
            $b->formula($row, 4, 'B' . $row . '-C' . $row, $bd['variance'], $over ? $st['money_l'] : $st['money_g']);
            $b->formula($row, 5, 'IF(B' . $row . '=0,0,C' . $row . '/B' . $row . ')', $bd['budget'] > 0 ? $bd['actual'] / $bd['budget'] : 0, $st['pct']);
            $b->formula($row, 6, 'IF(D' . $row . '<0,"Over budget","Within budget")', $over ? 'Over budget' : 'Within budget', $over ? $st['over'] : $st['ok']);
            $sumB += $bd['budget'];
            $sumA += $bd['actual'];
            $row++;
        }
        $last = $row - 1;
        $sumB = round($sumB, 2);
        $sumA = round($sumA, 2);
        $sumV = round($sumB - $sumA, 2);
        $over = $sumV < 0;
        $b->text($row, 1, 'Total', $st['t_txt']);
        $b->formula($row, 2, 'SUM(B' . $first . ':B' . $last . ')', $sumB, $st['t_money']);
        $b->formula($row, 3, 'SUM(C' . $first . ':C' . $last . ')', $sumA, $st['t_money']);
        $b->formula($row, 4, 'B' . $row . '-C' . $row, $sumV, $over ? $st['t_money_l'] : $st['t_money_g']);
        $b->formula($row, 5, 'IF(B' . $row . '=0,0,C' . $row . '/B' . $row . ')', $sumB > 0 ? $sumA / $sumB : 0, $st['t_pct']);
        $b->formula($row, 6, 'IF(D' . $row . '<0,"Over budget","Within budget")', $over ? 'Over budget' : 'Within budget', $over ? $st['t_over'] : $st['t_ok']);
    }

    /* ========================== Sheet 4: Investments ========================== */
    $v = $x->addSheet('Investments');
    $v->tabColor('A5732E');
    $v->colWidths([1 => 28, 2 => 16, 3 => 15, 4 => 16, 5 => 16, 6 => 16, 7 => 12]);
    $v->landscape();
    $v->text(1, 1, 'Investment portfolio', $st['title']);
    $v->rowHeight(1, 28);
    $v->text(2, 1, 'Snapshot as of ' . $r['as_of'] . ' - cumulative holdings, not limited to ' . $r['label'] . '.', $st['meta']);
    $hdr = 4;
    $v->text($hdr, 1, 'Investment', $st['th']);
    $v->text($hdr, 2, 'Type', $st['th']);
    $v->text($hdr, 3, 'Purchase date', $st['th']);
    $v->text($hdr, 4, 'Amount invested', $st['thr']);
    $v->text($hdr, 5, 'Current value', $st['thr']);
    $v->text($hdr, 6, 'Gain / loss', $st['thr']);
    $v->text($hdr, 7, 'Return', $st['thr']);
    $v->rowHeight($hdr, 20);
    $v->freezeRows($hdr);

    if (!$r['investments']) {
        $v->text($hdr + 1, 1, 'No investments recorded.', $st['note']);
    } else {
        $row = $hdr + 1;
        $first = $row;
        $sumI = 0; $sumC = 0;
        foreach ($r['investments'] as $iv) {
            $gain = round($iv['current'] - $iv['invested'], 2);
            $v->text($row, 1, $iv['name'], $st['txt']);
            $v->text($row, 2, $iv['type'], $st['txt']);
            if ($iv['date'] !== '') {
                $v->date($row, 3, $iv['date'], $st['date']);
            } else {
                $v->blank($row, 3, $st['txt']);
            }
            $v->num($row, 4, $iv['invested'], $st['money']);
            $v->num($row, 5, $iv['current'], $st['money']);
            $v->formula($row, 6, 'E' . $row . '-D' . $row, $gain, $gain >= 0 ? $st['money_g'] : $st['money_l']);
            $ret = $iv['invested'] > 0 ? $gain / $iv['invested'] : 0;
            $v->formula($row, 7, 'IF(D' . $row . '=0,0,F' . $row . '/D' . $row . ')', $ret, $ret >= 0 ? $st['pct_g'] : $st['pct_l']);
            $sumI += $iv['invested'];
            $sumC += $iv['current'];
            $row++;
        }
        $last = $row - 1;
        $sumI = round($sumI, 2);
        $sumC = round($sumC, 2);
        $sumG = round($sumC - $sumI, 2);
        $ret = $sumI > 0 ? $sumG / $sumI : 0;
        $v->text($row, 1, 'Total', $st['t_txt']);
        $v->blank($row, 2, $st['t_txt']);
        $v->blank($row, 3, $st['t_txt']);
        $v->formula($row, 4, 'SUM(D' . $first . ':D' . $last . ')', $sumI, $st['t_money']);
        $v->formula($row, 5, 'SUM(E' . $first . ':E' . $last . ')', $sumC, $st['t_money']);
        $v->formula($row, 6, 'E' . $row . '-D' . $row, $sumG, $sumG >= 0 ? $st['t_money_g'] : $st['t_money_l']);
        $v->formula($row, 7, 'IF(D' . $row . '=0,0,F' . $row . '/D' . $row . ')', $ret, $ret >= 0 ? $st['t_pct_g'] : $st['t_pct_l']);
    }

    pfms_send_download(
        $x->build(),
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pfms-report-' . $r['month'] . '.xlsx'
    );
}

/* -------------------------------------------------------------------- PDF -- */

function pfms_export_pdf($r)
{
    require_once __DIR__ . '/report_pdf.php';
    $pdfBytes = pfms_build_pdf($r);
    pfms_send_download($pdfBytes, 'application/pdf', 'pfms-report-' . $r['month'] . '.pdf');
}
