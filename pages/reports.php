<?php
/**
 * Reports Page (Manager/Admin)
 *
 * Generate and view various reports
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';

require_login();

// Fetch inventory summary
$inventorySummary = $pdo->query(
    'SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN status = "Available" THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = "Low Stock" THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN status = "Out of Stock" THEN 1 ELSE 0 END) as out_of_stock,
        SUM(quantity) as total_quantity
     FROM inventory_items'
)->fetch();

// Fetch request summary
$requestSummary = $pdo->query(
    'SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = "Pending" THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = "Approved" THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = "Rejected" THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed
     FROM stock_requests'
)->fetch();

// Fetch low stock items
$lowStockItems = $pdo->query(
    'SELECT item_id, item_name, quantity, min_threshold, status
     FROM inventory_items
     WHERE status IN ("Low Stock", "Out of Stock")
     ORDER BY quantity ASC
     LIMIT 10'
)->fetchAll();

// Fetch audit summary (last 30 days)
$auditSummary = $pdo->query(
    'SELECT 
        action_type,
        COUNT(*) as count
     FROM audit_logs
     WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY action_type
     ORDER BY count DESC'
)->fetchAll();

// Fetch top requesters
$topRequesters = $pdo->query(
    'SELECT 
        u.username,
        COUNT(r.request_id) as request_count
     FROM stock_requests r
     LEFT JOIN users u ON u.user_id = r.requested_by
     GROUP BY r.requested_by
     ORDER BY request_count DESC
     LIMIT 5'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>📊 System Reports</h2>
        </header>

        <main>
            <div class="container">
                <!-- Inventory Summary -->
                <div class="card mb-lg">
                    <h3 class="mb-lg">📦 Inventory Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="background: #f0fdf4; padding: 15px; border-radius: 5px; border-left: 4px solid #059669;">
                            <p style="color: #666; font-size: 0.9em;">Total Items</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #059669;"><?php echo (int)$inventorySummary['total_items']; ?></p>
                        </div>
                        <div style="background: #d1fae5; padding: 15px; border-radius: 5px; border-left: 4px solid #10b981;">
                            <p style="color: #666; font-size: 0.9em;">Available</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #10b981;"><?php echo (int)$inventorySummary['available']; ?></p>
                        </div>
                        <div style="background: #fef3c7; padding: 15px; border-radius: 5px; border-left: 4px solid #f59e0b;">
                            <p style="color: #666; font-size: 0.9em;">Low Stock</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #f59e0b;"><?php echo (int)$inventorySummary['low_stock']; ?></p>
                        </div>
                        <div style="background: #fee2e2; padding: 15px; border-radius: 5px; border-left: 4px solid #dc2626;">
                            <p style="color: #666; font-size: 0.9em;">Out of Stock</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #dc2626;"><?php echo (int)$inventorySummary['out_of_stock']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Request Summary -->
                <div class="card mb-lg">
                    <h3 class="mb-lg">📋 Stock Requests Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
                        <div style="background: #ede9fe; padding: 15px; border-radius: 5px; border-left: 4px solid #7c3aed;">
                            <p style="color: #666; font-size: 0.9em;">Total</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #7c3aed;"><?php echo (int)$requestSummary['total_requests']; ?></p>
                        </div>
                        <div style="background: #fef3c7; padding: 15px; border-radius: 5px; border-left: 4px solid #f59e0b;">
                            <p style="color: #666; font-size: 0.9em;">Pending</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #f59e0b;"><?php echo (int)$requestSummary['pending']; ?></p>
                        </div>
                        <div style="background: #d1fae5; padding: 15px; border-radius: 5px; border-left: 4px solid #059669;">
                            <p style="color: #666; font-size: 0.9em;">Approved</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #059669;"><?php echo (int)$requestSummary['approved']; ?></p>
                        </div>
                        <div style="background: #fee2e2; padding: 15px; border-radius: 5px; border-left: 4px solid #dc2626;">
                            <p style="color: #666; font-size: 0.9em;">Rejected</p>
                            <p style="font-size: 1.8em; font-weight: bold; color: #dc2626;"><?php echo (int)$requestSummary['rejected']; ?></p>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Low Stock Items -->
                    <div class="card">
                        <h3 class="mb-lg">⚠️ Low Stock Items</h3>
                        <?php if (count($lowStockItems) > 0): ?>
                        <table style="font-size: 0.95em;">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Current</th>
                                    <th>Min</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)$item['quantity']; ?></td>
                                    <td><?php echo (int)$item['min_threshold']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted">No low stock items.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Top Requesters -->
                    <div class="card">
                        <h3 class="mb-lg">👥 Top Requesters</h3>
                        <?php if (count($topRequesters) > 0): ?>
                        <table style="font-size: 0.95em;">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Requests</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topRequesters as $requester): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($requester['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><strong><?php echo (int)$requester['request_count']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted">No requests yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Audit Activity (Last 30 days) -->
                <div class="card mt-lg">
                    <h3 class="mb-lg">📝 Audit Activity (Last 30 Days)</h3>
                    <?php if (count($auditSummary) > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
                        <?php foreach ($auditSummary as $audit): ?>
                        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px; text-align: center;">
                            <p style="font-size: 1.5em; font-weight: bold; color: #1f2937;"><?php echo (int)$audit['count']; ?></p>
                            <p style="color: #666; font-size: 0.9em;"><?php echo htmlspecialchars($audit['action_type'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No audit activity.</p>
                    <?php endif; ?>
                </div>

                <div class="mt-xl">
                    <a href="<?php echo BASE_URL; ?>/pages/manager_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
