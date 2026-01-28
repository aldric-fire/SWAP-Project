<?php
/**
 * Admin Dashboard (Admin only)
 *
 * Server-side RBAC enforcement (OWASP A01: Broken Access Control)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_role(['Admin']);

// Fetch dashboard statistics
$totalItems = $pdo->query("SELECT COUNT(*) FROM inventory_items")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status = 'Low Stock'")->fetchColumn();
$outOfStock = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status = 'Out of Stock'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SIAMS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>Welcome <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
            <div class="top-header-actions">
                <div class="search-box">
                    <input type="text" placeholder="Search...">
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
                    <h3 class="mb-lg">Overview</h3>

                    <!-- Dashboard Statistics -->
                    <div class="stats-grid">
                        <div class="stat-card info">
                            <div class="stat-card-header">
                                <div>
                                    <h3>Total Items</h3>
                                </div>
                                <div class="stat-icon">📦</div>
                            </div>
                            <div class="stat-value"><?php echo (int)$totalItems; ?></div>
                            <div class="stat-label">Total Products</div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-card-header">
                                <div>
                                    <h3>Low Stock</h3>
                                </div>
                                <div class="stat-icon">📊</div>
                            </div>
                            <div class="stat-value"><?php echo (int)$lowStock; ?></div>
                            <div class="stat-label">Items need restock</div>
                        </div>
                        
                        <div class="stat-card danger">
                            <div class="stat-card-header">
                                <div>
                                    <h3>Out of Stock</h3>
                                </div>
                                <div class="stat-icon">📛</div>
                            </div>
                            <div class="stat-value"><?php echo (int)$outOfStock; ?></div>
                            <div class="stat-label">Out of Stock</div>
                        </div>
                        
                        <div class="stat-card success">
                            <div class="stat-card-header">
                                <div>
                                    <h3>Active Users</h3>
                                </div>
                                <div class="stat-icon">👥</div>
                            </div>
                            <div class="stat-value"><?php echo (int)$totalUsers; ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-xl">
                        <h3 class="mb-lg">Quick Actions</h3>
                        <div class="btn-group">
                            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary">View All Inventory</a>
                            <a href="<?php echo BASE_URL; ?>/pages/add_product.php" class="btn btn-success">Add New Item</a>
                            <a href="<?php echo BASE_URL; ?>/pages/about.php" class="btn btn-secondary">System Info</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
