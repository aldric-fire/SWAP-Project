<?php
/**
 * Approve Stock Requests Page (Manager)
 *
 * Managers review and approve/reject stock requests
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../config/requests.php';
require_once __DIR__ . '/../config/audit.php';

require_login();

// Handle approval/rejection
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $action = $_GET['action'];
    $requestId = filter_var($_GET['request_id'], FILTER_VALIDATE_INT);

    if ($requestId) {
        if ($action === 'approve') {
            approve_request($pdo, $requestId, (int)$_SESSION['user_id']);
            log_audit($pdo, (int)$_SESSION['user_id'], 'APPROVE', 'stock_requests', $requestId, 'Approved stock request');
        } elseif ($action === 'reject') {
            reject_request($pdo, $requestId, (int)$_SESSION['user_id']);
            log_audit($pdo, (int)$_SESSION['user_id'], 'REJECT', 'stock_requests', $requestId, 'Rejected stock request');
        }
    }
    header('Location: ' . BASE_URL . '/pages/approve_request.php');
    exit;
}

// Fetch pending requests
$pendingRequests = fetch_pending_requests($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Stock Requests - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>✅ Approve Stock Requests</h2>
            <div class="top-header-actions">
                <div class="header-icons">
                    <span style="background: #fef3c7; color: #92400e; padding: 8px 12px; border-radius: 4px; font-weight: bold;">
                        <?php echo count($pendingRequests); ?> Pending
                    </span>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <div class="card">
                    <h3 class="mb-lg">Pending Requests (<?php echo count($pendingRequests); ?>)</h3>
                    
                    <?php if (count($pendingRequests) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Priority</th>
                                    <th>Requested By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $req): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($req['item_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php echo (int)$req['quantity']; ?></td>
                                    <td>
                                        <span style="background: 
                                            <?php 
                                            if ($req['priority_score'] > 150) echo '#fee2e2; color: #dc2626;';
                                            elseif ($req['priority_score'] > 100) echo '#fef3c7; color: #92400e;';
                                            else echo '#d1fae5; color: #059669;';
                                            ?>
                                            padding: 4px 8px; border-radius: 3px; font-size: 0.9em; font-weight: bold;">
                                            <?php echo (int)$req['priority_score']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($req['requested_by'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($req['created_at'], 0, 10), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="?action=approve&request_id=<?php echo (int)$req['request_id']; ?>" class="btn btn-sm" style="background: #059669; color: white; padding: 6px 12px; border-radius: 3px; text-decoration: none; cursor: pointer;">✓ Approve</a>
                                        <a href="?action=reject&request_id=<?php echo (int)$req['request_id']; ?>" class="btn btn-sm" style="background: #dc2626; color: white; padding: 6px 12px; border-radius: 3px; text-decoration: none; cursor: pointer;">✗ Reject</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="padding: 20px; text-align: center; background: #f0fdf4; border-radius: 5px;">
                        <p style="color: #059669; font-size: 1.1em;">✓ All requests have been processed!</p>
                        <p class="text-muted">No pending requests at the moment.</p>
                    </div>
                    <?php endif; ?>

                    <div class="mt-xl">
                        <a href="<?php echo BASE_URL; ?>/pages/manager_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
