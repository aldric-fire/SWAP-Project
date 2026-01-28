<?php
/**
 * About Page
 *
 * Static information page describing the application features and technologies.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_login();

$pageTitle = 'About';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - SIAMS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>About SIAMS</h2>
            <div class="top-header-actions">
                <div class="header-icons">
                    <button class="icon-btn" title="Info">ℹ️</button>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <div class="card">
            <h2>About SIAMS</h2>
            <p>This is a secure inventory and asset management system built with PHP and MySQL.</p>

            <h3>Features:</h3>
            <ul>
                <li>Add new inventory items with quantity and thresholds</li>
                <li>View all inventory items and stock status</li>
                <li>Edit existing inventory items</li>
                <li>Delete items with confirmation (Admin/Manager)</li>
                <li>Clean separation of HTML, CSS, and JavaScript</li>
            </ul>

            <h3>Technologies Used:</h3>
            <ul>
                <li>PHP 7.4+</li>
                <li>MySQL Database</li>
                <li>HTML5</li>
                <li>CSS3</li>
                <li>JavaScript (ES6)</li>
            </ul>

            <p><a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary">Back to Inventory</a></p>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
