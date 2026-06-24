<?php
/**
 * Minimal XLSX generator menggunakan ZipArchive (built-in PHP).
 * Tidak butuh library eksternal.
 *
 * Penggunaan:
 *   xlsx_send($filename, $headers, $rows, $title)
 *   - $headers : array string kolom
 *   - $rows    : array of array (data)
 *   - $title   : nama sheet (opsional)
 */
function xlsx_send(string $filename, array $headers, array $rows, string $title = 'Sheet1'): void {
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

    // Shared strings: kumpulkan semua string unik
    $strings = []; $si_map = [];
    $collect = function($v) use (&$strings, &$si_map) {
        if (is_numeric($v) || $v === null || $v === '') return;
        $s = (string)$v;
        if (!isset($si_map[$s])) { $si_map[$s] = count($strings); $strings[] = $s; }
    };
    foreach ($headers as $h) $collect($h);
    foreach ($rows as $r) foreach ($r as $c) $collect($c);

    // Escape XML
    $xe = fn($s) => htmlspecialchars((string)$s, ENT_XML1, 'UTF-8');

    // Shared strings XML
    $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
    foreach ($strings as $s) $ssXml .= '<si><t xml:space="preserve">' . $xe($s) . '</t></si>';
    $ssXml .= '</sst>';

    // Sheet XML
    $cell = function(int $col, int $row, $val) use (&$si_map, $xe) {
        $colLetter = '';
        $c = $col + 1;
        while ($c > 0) { $colLetter = chr(65 + ($c - 1) % 26) . $colLetter; $c = (int)(($c - 1) / 26); }
        $ref = $colLetter . $row;
        if ($val === null || $val === '') return "<c r=\"$ref\"><v></v></c>";
        if (is_numeric($val) && !preg_match('/^0\d/', (string)$val)) {
            return "<c r=\"$ref\" t=\"n\"><v>" . $xe($val) . "</v></c>";
        }
        $idx = $si_map[(string)$val] ?? 0;
        return "<c r=\"$ref\" t=\"s\"><v>$idx</v></c>";
    };

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetData>';

    // Header row (row 1, style bold via s="1")
    $sheetXml .= '<row r="1">';
    foreach ($headers as $ci => $h) {
        $colLetter = '';
        $c2 = $ci + 1;
        while ($c2 > 0) { $colLetter = chr(65 + ($c2 - 1) % 26) . $colLetter; $c2 = (int)(($c2 - 1) / 26); }
        $idx = $si_map[(string)$h] ?? 0;
        $sheetXml .= '<c r="' . $colLetter . '1" t="s" s="1"><v>' . $idx . '</v></c>';
    }
    $sheetXml .= '</row>';

    foreach ($rows as $ri => $row) {
        $rowNum = $ri + 2;
        $sheetXml .= "<row r=\"$rowNum\">";
        foreach (array_values($row) as $ci => $val) {
            $sheetXml .= $cell($ci, $rowNum, $val);
        }
        $sheetXml .= '</row>';
    }
    $sheetXml .= '</sheetData></worksheet>';

    // Styles (s=0 normal, s=1 bold)
    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'
        . '</cellXfs></styleSheet>';

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
