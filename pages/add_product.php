<?php
/**
 * Add Inventory Item Page
 *
 * Displays a form to create new inventory items and handles form submission.
 * Validates input data and inserts new items into the database.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../middleware/csrf.php';
require_once __DIR__ . '/../config/inventory.php';
require_once __DIR__ . '/../config/audit.php';

// Only authorized roles may add inventory items (OWASP A01)
require_role(['Admin', 'Manager', 'Staff']);

$pageTitle = 'Add Inventory Item';

// Fetch suppliers for selection (FK integrity)
$suppliers = fetch_suppliers($pdo);
$supplierIds = array_column($suppliers, 'supplier_id');

/**
 * Handle form submission
 * Validates and inserts new inventory item into database
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_validate();

    $itemName = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $minThreshold = filter_var($_POST['min_threshold'] ?? null, FILTER_VALIDATE_INT);
    $supplierId = filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT);

    if ($itemName === '' || $quantity === false || $minThreshold === false || $quantity < 0 || $minThreshold < 0) {
        $error = 'Please fill all fields correctly.';
    } elseif ($supplierId && !in_array($supplierId, $supplierIds, true)) {
        $error = 'Invalid supplier selection.';
    } else {
        // Derive status server-side (prevent tampering)
        if ($quantity <= 0) {
            $status = 'Out of Stock';
        } elseif ($quantity <= $minThreshold) {
            $status = 'Low Stock';
        } else {
            $status = 'Available';
        }

        $itemId = insert_inventory_item($pdo, [
            'item_name' => $itemName,
            'category' => ($category === '' ? null : $category),
            'quantity' => $quantity,
            'min_threshold' => $minThreshold,
            'supplier_id' => $supplierId ?: null,
            'last_updated_by' => $_SESSION['user_id'],
            'status' => $status
        ]);

        if ($itemId > 0) {
            $success = 'Inventory item added successfully!';
            $itemName = $category = '';
            $quantity = $minThreshold = '';
            $supplierId = '';

            // Audit log (example: CREATE inventory_items id=15)
            log_audit(
                $pdo,
                (int)$_SESSION['user_id'],
                'CREATE',
                'inventory_items',
                $itemId,
                'Inventory item created by authorized user.'
            );
        } else {
            $error = 'Error adding inventory item.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SIAMS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>Add New Inventory Item</h2>
            <div class="top-header-actions">
                <div class="header-icons">
                    <button class="icon-btn" title="Help">ℹ️</button>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
        <div class="card">
            <h2>Add New Inventory Item</h2>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" onsubmit="return validateInventoryForm()">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="item_name">Item Name:</label>
                    <input type="text" id="item_name" name="item_name" placeholder="e.g., Desk Chair, Laptop Computer" value="<?php echo isset($itemName) ? htmlspecialchars($itemName) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category (optional):</label>
                    <input type="text" id="category" name="category" placeholder="e.g., Furniture, Electronics, Office Supplies" value="<?php echo isset($category) ? htmlspecialchars($category) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="0" placeholder="e.g., 15" value="<?php echo isset($quantity) ? (int)$quantity : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="min_threshold">Minimum Threshold:</label>
                    <input type="number" id="min_threshold" name="min_threshold" min="0" placeholder="e.g., 5" value="<?php echo isset($minThreshold) ? (int)$minThreshold : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="supplier_id">Supplier (optional):</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">-- Select supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo (int)$supplier['supplier_id']; ?>" <?php echo (isset($supplierId) && (int)$supplierId === (int)$supplier['supplier_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Add Item</button>
                <a href="<?php echo BASE_URL; ?>/index.php" class="btn">Cancel</a>
            </form>
        </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>

    <script src="<?php echo BASE_URL; ?>/javascripts/script.js"></script>
</body>
</html>
