<?php
/**
 * Export Reports (CSV)
 * Admin only
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../config/audit.php';

require_role(['Admin']);

// Get parameters
$type = $_GET['type'] ?? 'audit';
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

// Validate report type
if ($type !== 'audit') {
    die('Invalid report type');
}

// Build query
$query = "
    SELECT a.created_at, u.username, a.action, a.description, a.ip_address
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE 1=1
";

$params = [];

if (!empty($from)) {
    $query .= " AND a.created_at >= :from";
    $params['from'] = $from . ' 00:00:00';
}

if (!empty($to)) {
    $query .= " AND a.created_at <= :to";
    $params['to'] = $to . ' 23:59:59';
}

$query .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log export action
audit_log(
    $pdo,
    $_SESSION['user_id'],
    'EXPORT_REPORT',
    "Exported audit report from $from to $to"
);

// Send CSV headers
$filename = "audit_report_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV
$output = fopen('php://output', 'w');

// CSV header row
fputcsv($output, [
    'Date',
    'Username',
    'Action',
    'Description',
    'IP Address'
]);

// CSV data rows
foreach ($data as $row) {
    fputcsv($output, [
        $row['created_at'],
        $row['username'] ?? 'System',
        $row['action'],
        $row['description'],
        $row['ip_address']
    ]);
}

fclose($output);
exit;
