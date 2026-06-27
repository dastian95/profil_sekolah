<?php
/**
 * Minimal XLSX generator menggunakan ZipArchive (built-in PHP).
 * Tidak butuh library eksternal.
 *
 * Style index yang tersedia (pakai di $cell_style_fn):
 *   XLSX_NORMAL   = 0  — normal
 *   XLSX_BOLD     = 1  — bold (dipakai otomatis untuk header)
 *   XLSX_GREEN    = 2  — background hijau + teks putih  (terima)
 *   XLSX_RED      = 3  — background merah + teks putih  (gugur)
 *   XLSX_YELLOW   = 4  — background kuning              (diproses)
 *   XLSX_BLUE     = 5  — background biru muda           (lengkap)
 *   XLSX_GRAY     = 6  — background abu-abu             (lainnya)
 *
 * Penggunaan:
 *   xlsx_send($filename, $headers, $rows, $title, $cell_style_fn)
 *   - $cell_style_fn : callable($col_index, $value) => style_int|null
 */
const XLSX_NORMAL = 0;
const XLSX_BOLD   = 1;
const XLSX_GREEN  = 2;
const XLSX_RED    = 3;
const XLSX_YELLOW = 4;
const XLSX_BLUE   = 5;
const XLSX_GRAY   = 6;

function xlsx_send(string $filename, array $headers, array $rows, string $title = 'Sheet1', ?callable $cell_style_fn = null): void {
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');

    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        // Fallback ke CSV jika ZipArchive gagal
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . str_replace('.xlsx', '.csv', $filename) . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }

    // ── Shared strings ────────────────────────────────────────────────────────
    $strings = []; $si_map = [];
    $collect = function($v) use (&$strings, &$si_map) {
        if ($v === null || $v === '') return;
        // Angka tanpa 0 di depan ditulis sbg cell numerik → tak butuh shared string.
        // Angka berawalan 0 (NISN/No Telp) diperlakukan sbg teks oleh cell builder,
        // jadi tetap harus dikumpulkan agar 0 di depan tidak hilang.
        if (is_numeric($v) && !preg_match('/^0\d/', (string)$v)) return;
        $s = (string)$v;
        if (!isset($si_map[$s])) { $si_map[$s] = count($strings); $strings[] = $s; }
    };
    foreach ($headers as $h) $collect($h);
    foreach ($rows as $r) foreach ($r as $c) $collect($c);

    $xe = fn($s) => htmlspecialchars((string)$s, ENT_XML1, 'UTF-8');

    $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
    foreach ($strings as $s) $ssXml .= '<si><t xml:space="preserve">' . $xe($s) . '</t></si>';
    $ssXml .= '</sst>';

    // ── Col-letter helper ─────────────────────────────────────────────────────
    $colLtr = function(int $col): string {
        $ltr = ''; $c = $col + 1;
        while ($c > 0) { $ltr = chr(65 + ($c - 1) % 26) . $ltr; $c = (int)(($c - 1) / 26); }
        return $ltr;
    };

    // ── Cell builder ──────────────────────────────────────────────────────────
    $cell = function(int $col, int $row, $val, int $style = 0) use (&$si_map, $xe, $colLtr): string {
        $ref = $colLtr($col) . $row;
        $s   = $style > 0 ? " s=\"$style\"" : '';
        if ($val === null || $val === '') return "<c r=\"$ref\"$s><v></v></c>";
        if (is_numeric($val) && !preg_match('/^0\d/', (string)$val)) {
            return "<c r=\"$ref\" t=\"n\"$s><v>" . $xe($val) . '</v></c>';
        }
        $idx = $si_map[(string)$val] ?? 0;
        return "<c r=\"$ref\" t=\"s\"$s><v>$idx</v></c>";
    };

    // ── Hitung lebar kolom otomatis ───────────────────────────────────────────
    $colWidths = [];
    foreach ($headers as $ci => $h) {
        $colWidths[$ci] = mb_strlen((string)$h);
    }
    foreach ($rows as $row) {
        foreach (array_values($row) as $ci => $val) {
            $len = mb_strlen((string)$val);
            if (!isset($colWidths[$ci]) || $len > $colWidths[$ci]) $colWidths[$ci] = $len;
        }
    }

    // ── Sheet XML ─────────────────────────────────────────────────────────────
    $colsXml = '<cols>';
    foreach ($colWidths as $ci => $maxLen) {
        $width = min(max($maxLen * 1.15 + 2, 8), 60); // min 8, max 60
        $colsXml .= '<col min="' . ($ci+1) . '" max="' . ($ci+1) . '" width="' . number_format($width, 2, '.', '') . '" customWidth="1" bestFit="1"/>';
    }
    $colsXml .= '</cols>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . $colsXml
        . '<sheetData>';

    // Header row — bold
    $sheetXml .= '<row r="1">';
    foreach ($headers as $ci => $h) {
        $sheetXml .= $cell($ci, 1, $h, XLSX_BOLD);
    }
    $sheetXml .= '</row>';

    // Data rows
    foreach ($rows as $ri => $row) {
        $rowNum = $ri + 2;
        $sheetXml .= "<row r=\"$rowNum\">";
        foreach (array_values($row) as $ci => $val) {
            $style = $cell_style_fn ? ((int)($cell_style_fn($ci, $val) ?? 0)) : 0;
            $sheetXml .= $cell($ci, $rowNum, $val, $style);
        }
        $sheetXml .= '</row>';
    }
    $sheetXml .= '</sheetData></worksheet>';

    // ── Styles XML ────────────────────────────────────────────────────────────
    // fills: 0=none, 1=gray125(required), 2=green, 3=red, 4=yellow, 5=blue, 6=gray
    // fonts: 0=normal, 1=bold, 2=bold+white
    // cellXfs: 0=normal, 1=bold, 2=green+white, 3=red+white, 4=yellow, 5=blue, 6=gray
    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="3">'
        .   '<font><sz val="11"/><name val="Calibri"/></font>'
        .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'
        .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="8">'
        .   '<fill><patternFill patternType="none"/></fill>'
        .   '<fill><patternFill patternType="gray125"/></fill>'
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FF16A34A"/></patternFill></fill>'  // green
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FFDC2626"/></patternFill></fill>'  // red
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF08A"/></patternFill></fill>'  // yellow
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FFBFDBFE"/></patternFill></fill>'  // blue
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/></patternFill></fill>'  // gray
        .   '<fill><patternFill patternType="none"/></fill>'
        . '</fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="7">'
        .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'             // 0 normal
        .   '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'             // 1 bold
        .   '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"/>'  // 2 green+white
        .   '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFill="1" applyFont="1"/>'  // 3 red+white
        .   '<xf numFmtId="0" fontId="0" fillId="4" borderId="0" xfId="0" applyFill="1"/>'               // 4 yellow
        .   '<xf numFmtId="0" fontId="0" fillId="5" borderId="0" xfId="0" applyFill="1"/>'               // 5 blue
        .   '<xf numFmtId="0" fontId="0" fillId="6" borderId="0" xfId="0" applyFill="1"/>'               // 6 gray
        . '</cellXfs></styleSheet>';

    // ── ZIP structure ─────────────────────────────────────────────────────────
    $sheetTitle = $xe($title);
    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>');
    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');
    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="' . $sheetTitle . '" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->addFromString('xl/sharedStrings.xml',     $ssXml);
    $zip->addFromString('xl/styles.xml',            $stylesXml);
    $zip->close();

    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Pragma: no-cache');
    readfile($tmp);
    unlink($tmp);
    exit;
}
