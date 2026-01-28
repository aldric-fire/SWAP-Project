<?php
/**
 * Submit Stock Request Page (Staff)
 *
 * Allows staff to submit requests for stock
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../middleware/csrf.php';
require_once __DIR__ . '/../config/inventory.php';
require_once __DIR__ . '/../config/requests.php';
require_once __DIR__ . '/../config/audit.php';

require_login();

// Fetch inventory items for dropdown
$items = fetch_inventory_items($pdo);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $itemId = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $urgency = $_POST['urgency'] ?? 'Low';

    // Validation
    if (!$itemId) {
        $errors[] = 'Invalid item selected.';
    }

    if (!$quantity || $quantity < 1 || $quantity > 10000) {
        $errors[] = 'Quantity must be between 1 and 10,000.';
    }

    if (!in_array($urgency, ['Low', 'Medium', 'High'], true)) {
        $errors[] = 'Invalid urgency level.';
    }

    // Submit request if no errors
    if (empty($errors)) {
        try {
            $priorityScore = calculate_priority($quantity, $urgency);
            $requestId = submit_stock_request($pdo, [
                'item_id' => $itemId,
                'requested_by' => (int)$_SESSION['user_id'],
                'quantity' => $quantity,
                'priority_score' => $priorityScore
            ]);

            // Audit log
            log_audit($pdo, (int)$_SESSION['user_id'], 'CREATE', 'stock_requests', $requestId, "Submitted stock request for item $itemId (qty: $quantity)");

            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Error submitting request. Please try again.';
        }
    }
}

// Fetch user's requests
$userRequests = fetch_user_requests($pdo, (int)$_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Stock Request - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>📋 Submit Stock Request</h2>
        </header>

        <main>
            <div class="container">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Form -->
                    <div class="card">
                        <h3 class="mb-lg">New Request</h3>

                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            ✓ Request submitted successfully! Waiting for manager approval.
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p>✗ <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <label for="item_id">Item:</label>
                                <select id="item_id" name="item_id" required>
                                    <option value="">-- Select Item --</option>
                                    <?php foreach ($items as $item): ?>
                                    <option value="<?php echo (int)$item['item_id']; ?>">
                                        <?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?> 
                                        (Current: <?php echo (int)$item['quantity']; ?>, Min: <?php echo (int)$item['min_threshold']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="quantity">Quantity Needed:</label>
                                <input type="number" id="quantity" name="quantity" min="1" max="10000" required placeholder="e.g., 50">
                            </div>

                            <div class="form-group">
                                <label for="urgency">Urgency:</label>
                                <select id="urgency" name="urgency" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit Request</button>
                            <a href="<?php echo BASE_URL; ?>/pages/staff_dashboard.php" class="btn btn-secondary">Back</a>
                        </form>
                    </div>

                    <!-- Your Requests -->
                    <div class="card">
                        <h3 class="mb-lg">Your Requests (<?php echo count($userRequests); ?>)</h3>
                        
                        <?php if (count($userRequests) > 0): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($userRequests as $req): ?>
                            <div style="padding: 12px; border-bottom: 1px solid #eee; background: #f9f9f9; margin-bottom: 8px; border-radius: 4px;">
                                <p><strong><?php echo htmlspecialchars($req['item_name'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                <p style="color: #666; font-size: 0.9em;">
                                    Qty: <?php echo (int)$req['quantity']; ?> | 
                                    Status: <span style="font-weight: bold; color: <?php 
                                        echo $req['status'] === 'Approved' ? '#059669' : 
                                             ($req['status'] === 'Rejected' ? '#dc2626' : '#f59e0b'); 
                                    ?>;"><?php echo htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </p>
                                <p style="color: #999; font-size: 0.85em;"><?php echo htmlspecialchars(substr($req['created_at'], 0, 10), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No requests submitted yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
