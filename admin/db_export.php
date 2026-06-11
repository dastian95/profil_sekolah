<?php
session_start();
if (empty($_SESSION['is_super'])) {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once dirname(__DIR__) . '/conn.php';
require_once __DIR__ . '/_constants.php'; // log_admin_action()

$all_tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$tbl        = trim($_GET['tbl'] ?? '');
$search     = trim($_GET['q'] ?? '');

if (!$tbl || !in_array($tbl, $all_tables, true)) {
    http_response_code(400);
    exit('Tabel tidak valid.');
}

$columns   = $conn->query("DESCRIBE `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
$text_cols = array_filter($columns, fn($c) => preg_match('/char|text|enum/i', $c['Type']));
$where = ''; $params = [];
if ($search && $text_cols) {
    $parts = [];
    foreach ($text_cols as $c) { $parts[] = "`{$c['Field']}` LIKE ?"; $params[] = "%$search%"; }
    $where = 'WHERE ' . implode(' OR ', $parts);
}

$pk_col = 'id';
foreach ($columns as $c) {
    if ($c['Key'] === 'PRI') { $pk_col = $c['Field']; break; }
}

$ds = $conn->prepare("SELECT * FROM `$tbl` $where ORDER BY `$pk_col` ASC");
$ds->execute($params);
$rows = $ds->fetchAll(PDO::FETCH_ASSOC);

log_admin_action($conn, 'DB_EXPORT_CSV', "Tabel: $tbl, " . count($rows) . " baris");

$filename = $tbl . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');

$out = fopen('php://output', 'w');
// BOM untuk Excel UTF-8
fwrite($out, "\xEF\xBB\xBF");

if (!empty($rows)) {
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $row) fputcsv($out, $row);
}

fclose($out);
