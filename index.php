<?php
/**
 * Homepage - Inventory Items List
 *
 * Displays all inventory items from the database.
 * Handles item deletion with confirmation and displays success/error messages.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/middleware/rbac.php';
require_once __DIR__ . '/middleware/csrf.php';
require_once __DIR__ . '/config/inventory.php';
require_once __DIR__ . '/config/audit.php';

// Require authentication for access (OWASP A01)
require_login();

$pageTitle = 'Home - Inventory Items';

/**
 * Handle inventory item deletion
 * Processes delete requests and removes item from database
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_validate();

    // Only Admin/Manager can delete (OWASP A01)
    require_role(['Admin', 'Manager']);

    $id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            if (delete_inventory_item($pdo, $id)) {
                $success = 'Inventory item deleted successfully!';

                // Audit log (example: DELETE inventory_items id=10)
                log_audit(
                    $pdo,
                    (int)$_SESSION['user_id'],
                    'DELETE',
                    'inventory_items',
                    $id,
                    'Inventory item deleted by authorized user.'
                );
            } else {
                $error = 'Could not delete inventory item.';
            }
        } catch (PDOException $e) {
            // Log error but don't show detailed message to user (security)
            error_log('Delete inventory item error: ' . $e->getMessage());
            $error = 'Could not delete inventory item.';
        }
    } else {
        $error = 'Invalid item ID.';
    }
}

// Fetch all inventory items ordered by last updated
$items = fetch_inventory_items($pdo);

$canManage = in_array($_SESSION['role'], ['Admin', 'Manager'], true);
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
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>Inventory Items</h2>
            <div class="top-header-actions">
                <div class="search-box">
                    <input type="text" placeholder="Search inventory...">
                </div>
                <div class="header-icons">
                    <button class="icon-btn" title="Notifications">🔔</button>
                    <button class="icon-btn" title="Settings">⚙️</button>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
        <div class="card">
            <h2>Inventory Items</h2>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($items)): ?>
                <div class="products-grid">
                    <?php foreach ($items as $row): ?>
                        <div class="product-card">
                            <h3><?php echo htmlspecialchars($row['item_name']); ?></h3>
                            <p class="description">Category: <?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></p>
                            <p class="description">Supplier: <?php echo htmlspecialchars($row['supplier_name'] ?? 'Unassigned'); ?></p>
                            <p class="description">Quantity: <?php echo (int)$row['quantity']; ?></p>
                            <p class="description">Min Threshold: <?php echo (int)$row['min_threshold']; ?></p>
                            <p class="description">Status: <?php echo htmlspecialchars($row['status']); ?></p>

                            <div>
                                <a href="<?php echo BASE_URL; ?>/pages/edit_product.php?id=<?php echo (int)$row['item_id']; ?>" class="btn btn-edit">Edit</a>
                                <?php if ($canManage): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete_id" value="<?php echo (int)$row['item_id']; ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirmDelete('<?php echo htmlspecialchars($row['item_name']); ?>')">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No inventory items found.<br><a href="<?php echo BASE_URL; ?>/pages/add_product.php">Add your first item</a></p>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="<?php echo BASE_URL; ?>/javascripts/script.js"></script>
</body>
</html>
