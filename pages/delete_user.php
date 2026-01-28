<?php
/**
 * Delete User Page (Admin only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../config/users_mgmt.php';
require_once __DIR__ . '/../config/audit.php';

require_role(['Admin']);

$userId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$userId) {
    header('Location: ' . BASE_URL . '/pages/users.php');
    exit;
}

$user = fetch_user_by_id($pdo, $userId);
if (!$user) {
    header('Location: ' . BASE_URL . '/pages/users.php');
    exit;
}

// Prevent deleting yourself
if ($userId === (int)$_SESSION['user_id']) {
    header('Location: ' . BASE_URL . '/pages/users.php?error=Cannot delete your own account');
    exit;
}

// Delete user if confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    try {
        delete_user($pdo, $userId);
        log_audit($pdo, (int)$_SESSION['user_id'], 'DELETE', 'users', $userId, "Deleted user: {$user['username']}");
        header('Location: ' . BASE_URL . '/pages/users.php?success=User deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: ' . BASE_URL . '/pages/users.php?error=Error deleting user');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>⚠️ Delete User</h2>
        </header>

        <main>
            <div class="container">
                <div class="card" style="max-width: 500px; margin: 30px auto; background: #fef2f2; border-left: 4px solid #dc2626;">
                    <h3 style="color: #dc2626;">Confirm User Deletion</h3>
                    
                    <p style="margin: 20px 0; font-size: 1.1em;">
                        Are you sure you want to delete this user?
                    </p>

                    <div style="background: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <p><strong>Username:</strong> <code><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></code></p>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <p style="color: #666; margin: 20px 0;">
                        <strong>⚠️ Warning:</strong> This action cannot be undone. All user data will be permanently deleted.
                    </p>

                    <div style="display: flex; gap: 10px;">
                        <a href="<?php echo BASE_URL; ?>/pages/delete_user.php?id=<?php echo (int)$userId; ?>&confirm=yes" class="btn btn-danger">Delete Permanently</a>
                        <a href="<?php echo BASE_URL; ?>/pages/users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
