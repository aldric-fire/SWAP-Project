<?php
/**
 * Staff Dashboard (Staff only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';

require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - SIAMS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>Welcome <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
            <div class="top-header-actions">
                <div class="header-icons">
                    <button class="icon-btn" title="Notifications">🔔</button>
                    <button class="icon-btn" title="Settings">⚙️</button>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <div class="card">
                    <h3 class="mb-lg">Staff Dashboard</h3>
                    <p class="text-muted">View inventory, submit stock requests, and access inventory information.</p>
                    
                    <div class="btn-group mt-xl">
                        <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary">View Inventory</a>
                        <a href="<?php echo BASE_URL; ?>/pages/about.php" class="btn btn-secondary">System Info</a>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
