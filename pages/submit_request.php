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

// Get pre-selected item from URL parameter (if coming from inventory page)
$preselectedItemId = filter_var($_GET['item_id'] ?? null, FILTER_VALIDATE_INT);

// Fetch inventory items for dropdown
$items = fetch_inventory_items($pdo);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $itemId = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $urgencyNote = trim($_POST['urgency_note'] ?? '');
    $urgency = 'Medium';
    $reportedStockLevel = $_POST['reported_stock_level'] ?? 'Medium';
    $reportedFrequency = $_POST['reported_frequency'] ?? 'Medium';
    $selectedItem = null;

    // XSS Prevention: Sanitize urgency note input
    $urgencyNote = strip_tags($urgencyNote);
    $urgencyNote = htmlspecialchars($urgencyNote, ENT_QUOTES, 'UTF-8');
    
    // Validation
    if (!$itemId) {
        $errors[] = 'Invalid item selected.';
    } else {
        $selectedItem = fetch_inventory_item($pdo, $itemId);
        if (!$selectedItem) {
            $errors[] = 'Selected item not found.';
        }
    }

    if (!$quantity || $quantity < 1) {
        $errors[] = 'Quantity must be at least 1.';
    }

    // Validate project deadline format (must be valid UTC datetime)
    if ($urgencyNote === '') {
        $errors[] = 'Project deadline is required.';
    } else {
        // Check if it's a valid datetime format (YYYY-MM-DD HH:MM or similar)
        // Accept formats: 2026-02-05 18:00, 2026-02-05T18:00, 2026-02-05 18:00 UTC
        $datePattern = '/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(\s+UTC)?$/';
        if (!preg_match($datePattern, $urgencyNote)) {
            $errors[] = 'Project deadline must be in UTC format (e.g., 2026-02-05 18:00 UTC).';
            $urgencyNote = ''; // Clear invalid input
        }
    }

    // Derive urgency level from deadline if valid
    // XSS Prevention: Work with sanitized input
    if ($urgencyNote !== '') {
        $note = mb_strtolower($urgencyNote, 'UTF-8');
        if (preg_match('/\b(urgent|asap|deadline|critical|immediate|emergency)\b/', $note)) {
            $urgency = 'High';
        } elseif (preg_match('/\b(soon|priority|important|needed|require)\b/', $note)) {
            $urgency = 'Medium';
        } else {
            $urgency = 'Low';
        }
    }

    // No quantity limits - staff can request any amount they need
    // Managers will evaluate requests based on availability and priority

    if (!in_array($reportedStockLevel, ['Low', 'Medium', 'High'], true)) {
        $errors[] = 'Invalid stock level selection.';
    }

    if (!in_array($reportedFrequency, ['Low', 'Medium', 'High'], true)) {
        $errors[] = 'Invalid usage frequency selection.';
    }

    // Submit request if no errors
    if (empty($errors)) {
        try {
            // Calculate priority using enhanced multi-factor scoring
            $frequencyMap = ['Low' => 1, 'Medium' => 3, 'High' => 5];
            $frequencyOverride = $frequencyMap[$reportedFrequency] ?? 3;

            $priorityScore = calculate_priority_enhanced($quantity, $urgency, $selectedItem, $frequencyOverride);

            if ($reportedStockLevel === 'Low') {
                $priorityScore += 10;
            } elseif ($reportedStockLevel === 'High') {
                $priorityScore = max(1, $priorityScore - 5);
            }
            
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
                            Request submitted successfully! Waiting for manager approval.
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
                                    <option value="<?php echo (int)$item['item_id']; ?>" <?php echo ($preselectedItemId && $preselectedItemId === (int)$item['item_id']) ? 'selected' : ''; ?>>
                                        <?php 
                                        $displayName = $item['item_name'];
                                        if (strlen($displayName) > 24) {
                                            $displayName = substr($displayName, 0, 21) . '...';
                                        }
                                        echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
                                        ?> 
                                        (Current: <?php echo format_number($item['quantity']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="quantity">Quantity Needed:</label>
                                <input type="number" id="quantity" name="quantity" min="1" max="999999999" required placeholder="e.g., 50">
                            </div>

                            <div class="form-group">
                                <label for="urgency_note">Project deadline (UTC):</label>
                                <input type="text" id="urgency_note" name="urgency_note" maxlength="25" placeholder="2026-02-05 18:00 UTC" required pattern="\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(\s+UTC)?">
                            </div>

                            <div class="form-group">
                                <label for="reported_stock_level">Reported stock level:</label>
                                <select id="reported_stock_level" name="reported_stock_level" required>
                                    <option value="Low">Low (near minimum)</option>
                                    <option value="Medium" selected>Medium (stable)</option>
                                    <option value="High">High (well stocked)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="reported_frequency">Usage frequency:</label>
                                <select id="reported_frequency" name="reported_frequency" required>
                                    <option value="Low">Low (rare)</option>
                                    <option value="Medium" selected>Medium (weekly)</option>
                                    <option value="High">High (daily)</option>
                                </select>
                            </div>

                            <!-- Privacy & Transparency Disclaimer -->
                            <details style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3b82f6;">
                                <summary style="cursor: pointer; font-weight: 600; color: #1e293b; user-select: none;">
                                    📋 Privacy & Priority Score Calculation (Click to expand)
                                </summary>
                                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #475569; line-height: 1.6;">
                                    <strong>Privacy & Security:</strong> Project deadline, reported stock level, and usage frequency are computed into the Priority Score and are not stored in the database. Only the final priority score is saved.
                                    <br><br>
                                    <strong>Priority Score Calculation:</strong><br>
                                    • <strong>Quantity Needed & Project Deadline</strong> (0-300 pts): (Requested Qty ÷ Available Stock) × 100 × Urgency Weight<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;- Urgency derived from project deadline keywords: Low=1, Medium=2, High=3<br>
                                    • <strong>Reported Stock Level</strong>: Low (near minimum) adds +10 pts, High (well stocked) reduces -5 pts<br>
                                    • <strong>Usage Frequency</strong> (0-30 pts): Low (rare)=1, Medium (weekly)=3, High (daily)=5<br>
                                    <br>
                                    <strong>Automatic Background Factors:</strong><br>
                                    • <strong>Database Stock Check</strong> (0-50 pts): If current stock below minimum threshold<br>
                                    • <strong>Supplier Lead Time</strong> (0-20 pts): Based on selected item's supplier (longer delivery = higher priority)<br>
                                    <br>
                                    • <strong>Final Score</strong> = Sum of all factors (typical range: 1-400)
                                </div>
                            </details>

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
                                <p><strong>
                                    <?php
                                    $itemName = htmlspecialchars($req['item_name'] ?? 'Unknown Item', ENT_QUOTES, 'UTF-8');
                                    if (mb_strlen($itemName, 'UTF-8') > 24) {
                                        $itemName = mb_substr($itemName, 0, 24, 'UTF-8') . '…';
                                    }
                                    echo $itemName;
                                    ?>
                                </strong></p>
                                <p style="color: #666; font-size: 0.9em;">
                                    Qty: <?php echo format_number($req['quantity']); ?> | 
                                    Priority: <strong><?php echo (int)$req['priority_score']; ?></strong> | 
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

    <script>
        // Dynamic quantity validation based on selected item
        const itemSelect = document.getElementById('item_id');
        const quantityInput = document.getElementById('quantity');
        const submitButton = document.querySelector('button[type="submit"]');
        const quantityGroup = quantityInput.closest('.form-group');
        const helperEl = document.createElement('div');
        helperEl.className = 'text-muted';
        helperEl.style.marginTop = '6px';
        quantityGroup.appendChild(helperEl);
        
        // Store item data for dynamic validation
        const itemsData = {
            <?php foreach ($items as $item): ?>
            <?php echo (int)$item['item_id']; ?>: {
                minThreshold: <?php echo (int)$item['min_threshold']; ?>,
                currentQty: <?php echo (int)$item['quantity']; ?>,
                name: '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?>'
            },
            <?php endforeach; ?>
        };

        // Update quantity limits when item is selected
        itemSelect.addEventListener('change', function() {
            if (this.value && itemsData[this.value]) {
                const item = itemsData[this.value];
                const currentQty = Number(item.currentQty);

                // No limits - staff can request any amount
                quantityInput.min = '1';
                quantityInput.max = '999999999';
                quantityInput.placeholder = `e.g., 50 (Current stock: ${currentQty})`;
                helperEl.textContent = `Current available stock: ${currentQty}`;
                quantityInput.disabled = false;
                submitButton.disabled = false;
            } else {
                quantityInput.min = '1';
                quantityInput.max = '999999999';
                quantityInput.placeholder = 'e.g., 50';
                helperEl.textContent = '';
                quantityInput.disabled = false;
                submitButton.disabled = false;
            }
        });
    </script>
