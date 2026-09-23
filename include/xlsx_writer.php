<?php
/**
 * Minimal, dependency-free .xlsx (Office Open XML) writer for PFMS.
 *
 * Needs nothing beyond stock PHP (no Composer, no ext-zip): the ZIP container is
 * written by hand. Supports what the reports need: several sheets, text / number /
 * date / formula cells, cell styles, column widths, merged cells, frozen header rows,
 * auto-filter and print setup.
 *
 * Usage:
 *   $x  = new PfmsXlsx();
 *   $s  = $x->addSheet('Summary');
 *   $st = $x->style(['b' => true, 'bg' => 'EEEAE0', 'fmt' => '"$"#,##0.00']);
 *   $s->text(1, 1, 'Hello', $st);      // row, column (both 1-based)
 *   $s->num(1, 2, 12.5, $st);
 *   $bytes = $x->build();
 */

/* ------------------------------------------------------------------ ZIP ---- */
class PfmsZip
{
    private $files = [];

    public function add($name, $data)
    {
        $this->files[] = [$name, $data];
    }

    public function build()
    {
        $now = getdate();
        $dosTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | (int)($now['seconds'] / 2);
        $dosDate = (max($now['year'], 1980) - 1980) << 9 | ($now['mon'] << 5) | $now['mday'];

        $body = '';
        $central = '';
        $offset = 0;

        foreach ($this->files as $f) {
            list($name, $data) = $f;
            $crc  = crc32($data);
            $size = strlen($data);

            $method = 0;
            $comp = $data;
            if (function_exists('gzdeflate')) {
                $try = gzdeflate($data, 6);
                if ($try !== false && strlen($try) < $size) {
                    $comp = $try;
                    $method = 8;
                }
            }
            $csize = strlen($comp);

            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $dosTime, $dosDate, $crc, $csize, $size, strlen($name), 0) . $name;
            $body .= $local . $comp;

            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, $method, $dosTime, $dosDate, $crc, $csize, $size, strlen($name), 0, 0, 0, 0, 0, $offset) . $name;

            $offset += strlen($local) + $csize;
        }

        $count = count($this->files);
        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), $offset, 0);

        return $body . $central . $end;
    }
}

/* ------------------------------------------------------------- WORKBOOK ---- */
class PfmsXlsx
{
    /** @var PfmsXlsxSheet[] */
    private $sheets = [];

    private $fonts   = [];
    private $fills   = [];
    private $borders = [];
    private $numFmts = [];   // format code => id
    private $xfs     = [];
    private $xfIndex = [];   // dedupe key => xf id

    private $title   = 'Report';
    private $creator = 'PFMS';

    public function __construct()
    {
        // Mandatory defaults: font 0, fills 0/1 (none + gray125), border 0, xf 0.
        $this->fonts[]   = '<font><sz val="10"/><color rgb="FF16233A"/><name val="Calibri"/><family val="2"/></font>';
        $this->fills[]   = '<fill><patternFill patternType="none"/></fill>';
        $this->fills[]   = '<fill><patternFill patternType="gray125"/></fill>';
        $this->borders[] = '<border><left/><right/><top/><bottom/><diagonal/></border>';
        $this->xfs[]     = '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';
        $this->xfIndex['default'] = 0;
    }

    public function setProperties($title, $creator = 'PFMS')
    {
        $this->title = $title;
        $this->creator = $creator;
    }

    public function addSheet($name)
    {
        $name = preg_replace('/[\[\]\:\*\?\/\\\\]/', '', $name);
        $name = mb_substr($name, 0, 31);
        $sheet = new PfmsXlsxSheet($name);
        $this->sheets[] = $sheet;
        return $sheet;
    }

    /**
     * Register (or reuse) a cell style and return its id.
     * Keys: b, i (bool) | sz (pt) | color, bg (RRGGBB) | fmt (number format code)
     *       h (left|center|right) | v (top|center|bottom) | wrap (bool) | indent (int)
     *       border (null|bottom|top|topbottom|box) | bcolor (RRGGBB)
     */
    public function style(array $s)
    {
        $s += [
            'b' => false, 'i' => false, 'sz' => 10, 'color' => '16233A', 'bg' => null,
            'fmt' => null, 'h' => null, 'v' => 'center', 'wrap' => false, 'indent' => 0,
            'border' => null, 'bcolor' => 'D8D1BF',
        ];
        ksort($s);
        $key = json_encode($s);
        if (isset($this->xfIndex[$key])) {
            return $this->xfIndex[$key];
        }

        // font
        $font = '<font>' . ($s['b'] ? '<b/>' : '') . ($s['i'] ? '<i/>' : '')
              . '<sz val="' . (float)$s['sz'] . '"/><color rgb="FF' . $s['color'] . '"/><name val="Calibri"/><family val="2"/></font>';
        $fontId = $this->register($this->fonts, $font);

        // fill
        $fillId = 0;
        if ($s['bg']) {
            $fillId = $this->register($this->fills,
                '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $s['bg'] . '"/><bgColor indexed="64"/></patternFill></fill>');
        }

        // border
        $borderId = 0;
        if ($s['border']) {
            $line = function ($tag, $on, $style, $color) {
                return $on ? '<' . $tag . ' style="' . $style . '"><color rgb="FF' . $color . '"/></' . $tag . '>' : '<' . $tag . '/>';
            };
            $b = $s['border'];
            $c = $s['bcolor'];
            $xml = '<border>'
                 . $line('left',   $b === 'box', 'thin', $c)
                 . $line('right',  $b === 'box', 'thin', $c)
                 . $line('top',    in_array($b, ['top', 'topbottom', 'box'], true), $b === 'top' || $b === 'topbottom' ? 'thin' : 'thin', $b === 'top' || $b === 'topbottom' ? '16233A' : $c)
                 . $line('bottom', in_array($b, ['bottom', 'topbottom', 'box'], true), 'thin', $b === 'topbottom' ? '16233A' : $c)
                 . '<diagonal/></border>';
            $borderId = $this->register($this->borders, $xml);
        }

        // number format
        $numFmtId = 0;
        if ($s['fmt'] !== null) {
            $builtin = ['0' => 1, '0.00' => 2, '#,##0' => 3, '#,##0.00' => 4, '0%' => 9, '0.00%' => 10];
            if (isset($builtin[$s['fmt']])) {
                $numFmtId = $builtin[$s['fmt']];
            } else {
                if (!isset($this->numFmts[$s['fmt']])) {
                    $this->numFmts[$s['fmt']] = 164 + count($this->numFmts);
                }
                $numFmtId = $this->numFmts[$s['fmt']];
            }
        }

        // alignment
        $align = '<alignment vertical="' . $s['v'] . '"'
               . ($s['h'] ? ' horizontal="' . $s['h'] . '"' : '')
               . ($s['wrap'] ? ' wrapText="1"' : '')
               . ($s['indent'] ? ' indent="' . (int)$s['indent'] . '"' : '')
               . '/>';

        $xf = '<xf numFmtId="' . $numFmtId . '" fontId="' . $fontId . '" fillId="' . $fillId . '" borderId="' . $borderId . '" xfId="0"'
            . ' applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $align . '</xf>';

        $this->xfs[] = $xf;
        $id = count($this->xfs) - 1;
        $this->xfIndex[$key] = $id;
        return $id;
    }

    private function register(array &$list, $xml)
    {
        $i = array_search($xml, $list, true);
        if ($i !== false) {
            return $i;
        }
        $list[] = $xml;
        return count($list) - 1;
    }

    /* ------------------------------------------------------------ output -- */

    public function build()
    {
        $n = count($this->sheets);
        $zip = new PfmsZip();

        // [Content_Types].xml
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
             . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
             . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
             . '</Types>';
        $zip->add('[Content_Types].xml', $ct);

        // _rels/.rels
        $zip->add('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>');

        // docProps
        $zip->add('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . self::esc($this->title) . '</dc:title>'
            . '<dc:creator>' . self::esc($this->creator) . '</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
            . '</cp:coreProperties>');
        $zip->add('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>PFMS</Application></Properties>');

        // workbook.xml.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
              . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $n; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
               . '</Relationships>';
        $zip->add('xl/_rels/workbook.xml.rels', $rels);

        // workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000" activeTab="0"/></bookViews><sheets>';
        $names = '';
        foreach ($this->sheets as $i => $sh) {
            $wb .= '<sheet name="' . self::esc($sh->name) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
            $q = "'" . str_replace("'", "''", $sh->name) . "'";
            if ($sh->filterRange) {
                $names .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">' . self::esc($q . '!' . self::absRange($sh->filterRange)) . '</definedName>';
            }
            if ($sh->printTitleRow) {
                $names .= '<definedName name="_xlnm.Print_Titles" localSheetId="' . $i . '">' . self::esc($q . '!$' . $sh->printTitleRow . ':$' . $sh->printTitleRow) . '</definedName>';
            }
        }
        $wb .= '</sheets>' . ($names ? '<definedNames>' . $names . '</definedNames>' : '')
             . '<calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>';
        $zip->add('xl/workbook.xml', $wb);

        // worksheets
        foreach ($this->sheets as $i => $sh) {
            $zip->add('xl/worksheets/sheet' . ($i + 1) . '.xml', $sh->toXml($i === 0));
        }

        // styles.xml
        $zip->add('xl/styles.xml', $this->stylesXml());

        return $zip->build();
    }

    private function stylesXml()
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if ($this->numFmts) {
            $x .= '<numFmts count="' . count($this->numFmts) . '">';
            foreach ($this->numFmts as $code => $id) {
                $x .= '<numFmt numFmtId="' . $id . '" formatCode="' . self::esc($code) . '"/>';
            }
            $x .= '</numFmts>';
        }

        $x .= '<fonts count="' . count($this->fonts) . '">' . implode('', $this->fonts) . '</fonts>'
            . '<fills count="' . count($this->fills) . '">' . implode('', $this->fills) . '</fills>'
            . '<borders count="' . count($this->borders) . '">' . implode('', $this->borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($this->xfs) . '">' . implode('', $this->xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
        return $x;
    }

    /** XML-escape + strip characters that are illegal in XML 1.0. */
    public static function esc($s)
    {
        $s = (string)$s;
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function col($c)
    {
        $s = '';
        while ($c > 0) {
            $m = ($c - 1) % 26;
            $s = chr(65 + $m) . $s;
            $c = intdiv($c - 1, 26);
        }
        return $s;
    }

    private static function absRange($r)
    {
        return preg_replace('/([A-Z]+)(\d+)/', '\$$1\$$2', $r);
    }
}

/* ---------------------------------------------------------------- SHEET ---- */
class PfmsXlsxSheet
{
    public $name;
    public $filterRange = null;
    public $printTitleRow = null;

    private $cells = [];       // [row][col] => xml
    private $rowHeights = [];
    private $colWidths = [];
    private $merges = [];
    private $freezeRow = 0;
    private $gridlines = true;
    private $landscape = false;
    private $tab = null;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /* ---- cells (row, col are 1-based) ---- */

    public function text($r, $c, $value, $style = 0)
    {
        $value = (string)$value;
        if (mb_strlen($value) > 32000) {
            $value = mb_substr($value, 0, 32000);
        }
        $this->cells[$r][$c] = '<c r="' . PfmsXlsx::col($c) . $r . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
                             . PfmsXlsx::esc($value) . '</t></is></c>';
    }

    public function num($r, $c, $value, $style = 0)
    {
        $this->cells[$r][$c] = '<c r="' . PfmsXlsx::col($c) . $r . '" s="' . $style . '"><v>' . self::numStr($value) . '</v></c>';
    }

    /** $ymd = 'YYYY-MM-DD' -> real Excel date (serial number) */
    public function date($r, $c, $ymd, $style = 0)
    {
        $t = strtotime($ymd . ' 00:00:00 UTC');
        if ($t === false) {
            $this->text($r, $c, $ymd, $style);
            return;
        }
        $this->num($r, $c, $t / 86400 + 25569, $style);
    }

    /** Formula with a cached result (so viewers that don't recalculate still show numbers). */
    public function formula($r, $c, $formula, $cached, $style = 0)
    {
        $isStr = is_string($cached);
        $this->cells[$r][$c] = '<c r="' . PfmsXlsx::col($c) . $r . '" s="' . $style . '"' . ($isStr ? ' t="str"' : '') . '>'
                             . '<f>' . PfmsXlsx::esc(ltrim($formula, '=')) . '</f>'
                             . '<v>' . ($isStr ? PfmsXlsx::esc($cached) : self::numStr($cached)) . '</v></c>';
    }

    /** Empty cell that only carries a style (fills, borders). */
    public function blank($r, $c, $style = 0)
    {
        $this->cells[$r][$c] = '<c r="' . PfmsXlsx::col($c) . $r . '" s="' . $style . '"/>';
    }

    /* ---- layout ---- */

    public function colWidths(array $widths)          // [1 => 14, 2 => 20, ...]
    {
        $this->colWidths = $widths;
    }

    public function rowHeight($r, $pts)
    {
        $this->rowHeights[$r] = $pts;
    }

    public function merge($range)
    {
        $this->merges[] = $range;
    }

    public function freezeRows($n)
    {
        $this->freezeRow = $n;
    }

    public function autoFilter($range)
    {
        $this->filterRange = $range;
    }

    public function repeatRow($row)
    {
        $this->printTitleRow = $row;
    }

    public function hideGridlines()
    {
        $this->gridlines = false;
    }

    public function landscape()
    {
        $this->landscape = true;
    }

    public function tabColor($rgb)
    {
        $this->tab = $rgb;
    }

    private static function numStr($n)
    {
        if (is_int($n)) {
            return (string)$n;
        }
        $n = (float)$n;
        if (!is_finite($n)) {
            return '0';
        }
        return rtrim(rtrim(sprintf('%.10F', $n), '0'), '.') ?: '0';
    }

    /* ---- xml ---- */

    public function toXml($selected = false)
    {
        $maxRow = 1;
        $maxCol = 1;
        foreach ($this->cells as $r => $cols) {
            $maxRow = max($maxRow, $r);
            $maxCol = max($maxCol, max(array_keys($cols)));
        }
        foreach ($this->rowHeights as $r => $_) {
            $maxRow = max($maxRow, $r);
        }

        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        $x .= '<sheetPr>' . ($this->tab ? '<tabColor rgb="FF' . $this->tab . '"/>' : '') . '<pageSetUpPr fitToPage="1"/></sheetPr>';
        $x .= '<dimension ref="A1:' . PfmsXlsx::col($maxCol) . $maxRow . '"/>';

        $x .= '<sheetViews><sheetView workbookViewId="0"' . ($this->gridlines ? '' : ' showGridLines="0"') . ($selected ? ' tabSelected="1"' : '') . '>';
        if ($this->freezeRow > 0) {
            $x .= '<pane ySplit="' . $this->freezeRow . '" topLeftCell="A' . ($this->freezeRow + 1) . '" activePane="bottomLeft" state="frozen"/>'
                . '<selection pane="bottomLeft" activeCell="A' . ($this->freezeRow + 1) . '" sqref="A' . ($this->freezeRow + 1) . '"/>';
        }
        $x .= '</sheetView></sheetViews>';
        $x .= '<sheetFormatPr defaultRowHeight="15"/>';

        if ($this->colWidths) {
            ksort($this->colWidths);
            $x .= '<cols>';
            foreach ($this->colWidths as $c => $w) {
                $x .= '<col min="' . $c . '" max="' . $c . '" width="' . $w . '" customWidth="1"/>';
            }
            $x .= '</cols>';
        }

        $x .= '<sheetData>';
        $rows = array_unique(array_merge(array_keys($this->cells), array_keys($this->rowHeights)));
        sort($rows);
        foreach ($rows as $r) {
            $x .= '<row r="' . $r . '"' . (isset($this->rowHeights[$r]) ? ' ht="' . $this->rowHeights[$r] . '" customHeight="1"' : '') . '>';
            if (isset($this->cells[$r])) {
                ksort($this->cells[$r]);
                $x .= implode('', $this->cells[$r]);
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';

        if ($this->filterRange) {
            $x .= '<autoFilter ref="' . $this->filterRange . '"/>';
        }
        if ($this->merges) {
            $x .= '<mergeCells count="' . count($this->merges) . '">';
            foreach ($this->merges as $m) {
                $x .= '<mergeCell ref="' . $m . '"/>';
            }
            $x .= '</mergeCells>';
        }

        $x .= '<pageMargins left="0.5" right="0.5" top="0.7" bottom="0.7" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="' . ($this->landscape ? 'landscape' : 'portrait') . '" fitToWidth="1" fitToHeight="0"/>'
            . '<headerFooter><oddFooter>&amp;LPFMS&amp;CPage &amp;P of &amp;N&amp;R&amp;A</oddFooter></headerFooter>'
            . '</worksheet>';

        return $x;
    }
}
