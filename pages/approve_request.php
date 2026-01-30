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
require_once __DIR__ . '/../config/notifications.php';
require_once __DIR__ . '/../middleware/csrf.php';

require_login();

// Handle bulk approval (consolidation) - CSRF PROTECTED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_ids'])) {
    csrf_validate(); // SECURITY: CSRF protection
    
    $request_ids = array_filter(array_map('intval', explode(',', $_POST['request_ids'])));
    
    if (!empty($request_ids) && approve_bulk_requests($pdo, $request_ids, (int)$_SESSION['user_id'])) {
        // Log each approval in audit trail
        foreach ($request_ids as $req_id) {
            log_audit($pdo, (int)$_SESSION['user_id'], 'APPROVE', 'stock_requests', $req_id, 'Bulk approved (consolidated order)');
            // Send notification for each request
            send_approval_notification($pdo, $req_id, (int)$_SESSION['user_id']);
        }
        $_SESSION['consolidation_success'] = count($request_ids) . ' requests consolidated and approved!';
    }
    
    header('Location: ' . BASE_URL . '/pages/approve_request.php');
    exit;
}

// Handle approval/rejection
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $action = $_GET['action'];
    $requestId = filter_var($_GET['request_id'], FILTER_VALIDATE_INT);

    if ($requestId) {
        if ($action === 'approve') {
            approve_request($pdo, $requestId, (int)$_SESSION['user_id']);
            log_audit($pdo, (int)$_SESSION['user_id'], 'APPROVE', 'stock_requests', $requestId, 'Approved stock request');
            
            // Get supplier recommendation for this request
            $request = fetch_request_by_id($pdo, $requestId);
            if ($request) {
                $recommendation = recommend_optimal_supplier($pdo, (int)$request['item_id'], (int)$request['quantity']);
                $_SESSION['supplier_recommendation'] = $recommendation;
                $_SESSION['approved_request_id'] = $requestId;
            }
            
            // Send approval notification (non-blocking - don't fail if email fails)
            send_approval_notification($pdo, $requestId, (int)$_SESSION['user_id']);
        } elseif ($action === 'reject') {
            reject_request($pdo, $requestId, (int)$_SESSION['user_id']);
            log_audit($pdo, (int)$_SESSION['user_id'], 'REJECT', 'stock_requests', $requestId, 'Rejected stock request');
            // Send rejection notification (non-blocking)
            send_rejection_notification($pdo, $requestId, (int)$_SESSION['user_id']);
        }
    }
    header('Location: ' . BASE_URL . '/pages/approve_request.php');
    exit;
}

// Fetch pending requests
$pendingRequests = fetch_pending_requests($pdo);

// Get consolidation opportunities
$consolidationOpportunities = get_consolidation_opportunities($pdo);

// Fetch approved and rejected requests
$stmt = $pdo->prepare(
    'SELECT r.request_id, r.item_id, i.item_name, r.quantity, r.priority_score, r.status, 
            r.created_at, r.updated_at, u.username as requested_by, i.quantity as current_quantity
     FROM stock_requests r
     LEFT JOIN inventory_items i ON i.item_id = r.item_id
     LEFT JOIN users u ON u.user_id = r.requested_by
     WHERE r.status IN ("Approved", "Rejected")
     ORDER BY r.updated_at DESC
     LIMIT 50'
);
$stmt->execute();
$processedRequests = $stmt->fetchAll();
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
                    <div class="table-container requests-table-wrap">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th class="col-item">Item</th>
                                    <th class="col-qty text-center">Qty</th>
                                    <th class="col-stock text-center">Stock Level</th>
                                    <th class="col-priority text-center">Priority</th>
                                    <th class="col-requested-by">From</th>
                                    <th class="col-date">Exp. Delivery</th>
                                    <th class="col-actions text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $req): 
                                    $expectedDelivery = get_expected_delivery_date($req['supplier_id'], date('Y-m-d'));
                                    $frequency = get_item_request_frequency($pdo, (int)$req['item_id']);
                                    $stockLevel = htmlspecialchars($req['current_quantity'] ?? 0, ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td class="col-item">
                                        <strong>
                                            <?php
                                            $itemName = htmlspecialchars($req['item_name'], ENT_QUOTES, 'UTF-8');
                                            if (mb_strlen($itemName, 'UTF-8') > 24) {
                                                $itemName = mb_substr($itemName, 0, 24, 'UTF-8') . '…';
                                            }
                                            echo $itemName;
                                            ?>
                                        </strong>
                                    </td>
                                    <td class="col-qty text-center"><?php echo format_number($req['quantity']); ?></td>
                                    <td class="col-stock text-center">
                                        <span class="stock-level">
                                            <?php
                                            $currentQty = (int)($req['current_quantity'] ?? 0);
                                            echo format_number($currentQty);
                                            ?>
                                        </span>
                                    </td>
                                    <td class="col-priority text-center">
                                        <span class="priority-badge" style="background: 
                                            <?php 
                                            if ($req['priority_score'] > 200) echo '#fee2e2; color: #dc2626;';
                                            elseif ($req['priority_score'] > 100) echo '#fef3c7; color: #92400e;';
                                            else echo '#d1fae5; color: #059669;';
                                            ?>">
                                            <?php echo (int)$req['priority_score']; ?>
                                        </span>
                                    </td>
                                    <td class="col-requested-by"><?php echo htmlspecialchars($req['requested_by'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="col-date">
                                        <?php 
                                        if ($expectedDelivery) {
                                            echo htmlspecialchars($expectedDelivery, ENT_QUOTES, 'UTF-8');
                                            $days_until = (int)((strtotime($expectedDelivery) - time()) / 86400);
                                            echo '<br><small style="color: #6b7280;">(~' . $days_until . ' days)</small>';
                                        } else {
                                            echo '<span style="color: #9ca3af;">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="col-actions text-center">
                                        <div class="request-actions">
                                            <a href="?action=approve&request_id=<?php echo (int)$req['request_id']; ?>" class="btn btn-sm btn-success">✓ Approve</a>
                                            <a href="?action=reject&request_id=<?php echo (int)$req['request_id']; ?>" class="btn btn-sm btn-danger">✗ Reject</a>
                                        </div>
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
                </div>

                <!-- Supplier Recommendation Panel -->
                <?php if (isset($_SESSION['supplier_recommendation']) && isset($_SESSION['approved_request_id'])): 
                    $rec = $_SESSION['supplier_recommendation'];
                ?>
                <div class="card" style="margin-top: 30px; border: 2px solid #10b981;">
                    <h3 style="color: #059669;">🚚 Supplier Recommendation (Request #<?php echo (int)$_SESSION['approved_request_id']; ?>)</h3>
                    
                    <?php if (isset($rec['error'])): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($rec['error'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php else: ?>
                        <p style="margin-bottom: 20px; color: #6b7280;">System recommends the following supplier based on lead time and availability:</p>
                        
                        <div style="background: #d1fae5; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981;">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                                <div>
                                    <strong style="color: #059669;">⭐ Recommended Supplier</strong>
                                    <h4 style="margin: 5px 0; color: #047857;"><?php echo $rec['supplier_name']; ?></h4>
                                </div>
                                <div>
                                    <strong style="color: #059669;">Recommendation Score</strong>
                                    <h4 style="margin: 5px 0; color: #047857;"><?php echo (int)$rec['recommendation_score']; ?> / 200</h4>
                                </div>
                            </div>
                            <hr style="border: none; border-top: 1px solid #10b981; margin: 15px 0;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; font-size: 0.95em;">
                                <div>
                                    <span style="color: #6b7280;">Lead Time:</span><br>
                                    <strong><?php echo (int)$rec['lead_time_days']; ?> days</strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Expected Delivery:</span><br>
                                    <strong><?php echo htmlspecialchars($rec['expected_delivery'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Stock Status:</span><br>
                                    <strong style="color: <?php echo $rec['can_fulfill'] ? '#059669' : '#dc2626'; ?>;">
                                        <?php echo $rec['can_fulfill'] ? '✓ Can Fulfill' : '✗ Insufficient Stock'; ?>
                                    </strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Available:</span><br>
                                    <strong><?php echo format_number($rec['available_stock']); ?> units</strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Requested:</span><br>
                                    <strong><?php echo format_number($rec['requested_qty']); ?> units</strong>
                                </div>
                            </div>
                        </div>
                        <p style="margin-top: 15px; font-size: 0.9em; color: #6b7280;">
                            💡 <strong>Next Step:</strong> Contact supplier to place order for approved quantity.
                        </p>
                    <?php endif; ?>
                </div>
                <?php 
                    unset($_SESSION['supplier_recommendation']); 
                    unset($_SESSION['approved_request_id']);
                endif; 
                ?>

                <!-- Order Consolidation Panel -->
                <?php if (!empty($consolidationOpportunities)): ?>
                <div class="card" style="margin-top: 30px; border: 2px solid #fbbf24;">
                    <h3 style="color: #92400e;">📦 Order Consolidation Opportunities</h3>
                    <p style="margin-bottom: 20px; color: #6b7280;">
                        These pending requests can be batched together for efficiency:
                    </p>
                    
                    <div class="table-container requests-table-wrap">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-center">Requests</th>
                                    <th class="text-center">Total Items</th>
                                    <th>Items</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consolidationOpportunities as $opp): ?>
                                <tr>
                                    <td><strong><?php echo $opp['supplier_name']; ?></strong></td>
                                    <td class="text-center">
                                        <span class="priority-badge" style="background: #fef3c7; color: #92400e;">
                                            <?php echo (int)$opp['total_requests']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo format_number($opp['total_items']); ?></td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo substr($opp['items'], 0, 80); ?><?php echo strlen($opp['items']) > 80 ? '...' : ''; ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrf_field(); // SECURITY: CSRF protection ?>
                                            <input type="hidden" name="request_ids" value="<?php echo htmlspecialchars($opp['request_ids'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary" 
                                                    onclick="return confirm('Approve all <?php echo (int)$opp['total_requests']; ?> requests from <?php echo htmlspecialchars($opp['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>?');">
                                                ✓ Approve All (Consolidate)
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px; padding: 12px; background: #fef3c7; border-radius: 6px; font-size: 0.9em; color: #92400e;">
                        💡 <strong>Benefits of Consolidation:</strong> Reduced shipping costs, fewer deliveries, improved efficiency
                    </div>
                </div>
                <?php endif; ?>

                <!-- Success Message for Consolidation -->
                <?php if (isset($_SESSION['consolidation_success'])): ?>
                <div class="alert alert-success" style="margin-top: 20px;">
                    <?php 
                    echo htmlspecialchars($_SESSION['consolidation_success'], ENT_QUOTES, 'UTF-8');
                    unset($_SESSION['consolidation_success']);
                    ?>
                </div>
                <?php endif; ?>

                <!-- Approved & Rejected History -->
                <div class="card" style="margin-top: 30px;">
                    <h3 class="mb-lg">Request History (Approved & Rejected)</h3>
                    
                    <?php if (count($processedRequests) > 0): ?>
                    <div class="table-container requests-table-wrap">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th class="col-item">Item</th>
                                    <th class="col-qty text-center">Qty</th>
                                    <th class="col-priority text-center">Priority</th>
                                    <th class="col-requested-by">From</th>
                                    <th class="col-date">Date</th>
                                    <th class="col-date">Status</th>
                                    <th class="col-actions text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processedRequests as $req): ?>
                                <tr>
                                    <td class="col-item">
                                        <strong>
                                            <?php
                                            $itemName = htmlspecialchars($req['item_name'], ENT_QUOTES, 'UTF-8');
                                            if (mb_strlen($itemName, 'UTF-8') > 24) {
                                                $itemName = mb_substr($itemName, 0, 24, 'UTF-8') . '…';
                                            }
                                            echo $itemName;
                                            ?>
                                        </strong>
                                    </td>
                                    <td class="col-qty text-center"><?php echo format_number($req['quantity']); ?></td>
                                    <td class="col-priority text-center">
                                        <span class="priority-badge" style="background: 
                                            <?php 
                                            if ($req['priority_score'] > 200) echo '#fee2e2; color: #dc2626;';
                                            elseif ($req['priority_score'] > 100) echo '#fef3c7; color: #92400e;';
                                            else echo '#d1fae5; color: #059669;';
                                            ?>">
                                            <?php echo (int)$req['priority_score']; ?>
                                        </span>
                                    </td>
                                    <td class="col-requested-by"><?php echo htmlspecialchars($req['requested_by'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="col-date"><?php echo htmlspecialchars(substr($req['created_at'], 0, 10), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="col-date">
                                        <span style="font-weight: bold; color: <?php 
                                            echo $req['status'] === 'Approved' ? '#059669' : '#dc2626'; 
                                        ?>;">
                                            <?php echo htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="col-actions text-center">
                                        <div class="request-actions">
                                            <?php if ($req['status'] !== 'Approved'): ?>
                                            <a href="?action=approve&request_id=<?php echo (int)$req['request_id']; ?>" class="btn btn-sm btn-success">✓ Approve</a>
                                            <?php endif; ?>
                                            <?php if ($req['status'] !== 'Rejected'): ?>
                                            <a href="?action=reject&request_id=<?php echo (int)$req['request_id']; ?>" class="btn btn-sm btn-danger">✗ Reject</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No approved or rejected requests yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
