<?php
/**
 * View Report Page (Admin only)
 * Allows filtering, viewing, and managing reports
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../config/audit.php';

require_role(['Admin']);

$error = '';
$message = '';

// Get report type
$reportType = $_GET['type'] ?? 'audit';

// Filters
$fromDate = $_GET['from'] ?? '';
$toDate   = $_GET['to'] ?? '';

// Handle archive action
if (isset($_POST['archive_old'])) {
    $stmt = $pdo->prepare("
        DELETE FROM audit_logs
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $stmt->execute();

    audit_log($pdo, $_SESSION['user_id'], 'ARCHIVE_REPORTS', 'Archived audit logs older than 1 year');
    $message = 'Old audit logs archived successfully.';
}

// Load report data
try {
    if ($reportType === 'audit') {
        $query = "
            SELECT a.*, u.username
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE 1=1
        ";

        $params = [];

        if ($fromDate) {
            $query .= " AND a.created_at >= :from";
            $params['from'] = $fromDate . ' 00:00:00';
        }

        if ($toDate) {
            $query .= " AND a.created_at <= :to";
            $params['to'] = $toDate . ' 23:59:59';
        }

        $query .= " ORDER BY a.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $error = 'Unknown report type selected.';
        $reports = [];
    }

} catch (Exception $e) {
    $error = 'Failed to load report data.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Report - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body class="has-sidebar">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-wrapper">
    <header class="top-header">
        <h2>📊 System Reports</h2>
    </header>

    <main>
        <div class="container">

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Filter Form -->
            <form method="get" class="mb-lg">
                <input type="hidden" name="type" value="audit">

                <label>From</label>
                <input type="date" name="from" value="<?php echo htmlspecialchars($fromDate); ?>">

                <label>To</label>
                <input type="date" name="to" value="<?php echo htmlspecialchars($toDate); ?>">

                <button class="btn btn-sm btn-primary">Filter</button>

                <a href="export_report.php?type=audit&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>"
                   class="btn btn-sm btn-secondary">
                    Export Report
                </a>
            </form>

            <!-- Report Table -->
            <div class="card">
                <h3>Audit Logs (<?php echo count($reports); ?>)</h3>

                <?php if ($reports): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($row['username'] ?? 'System'); ?></td>
                            <td><code><?php echo htmlspecialchars($row['action']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted">No records found.</p>
                <?php endif; ?>
            </div>

            <!-- Archive Action -->
            <form method="post" class="mt-lg">
                <button name="archive_old" class="btn btn-danger btn-sm"
                    onclick="return confirm('Archive audit logs older than 1 year?');">
                    Archive Old Audit Logs
                </button>
            </form>

        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>

</body>
</html>
