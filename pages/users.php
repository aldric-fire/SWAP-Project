<?php
/**
 * User Management Page (Admin only)
 *
 * View all users, create, edit, and delete users
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../config/users_mgmt.php';
require_once __DIR__ . '/../config/audit.php';

require_role(['Admin']);

$users = fetch_all_users($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>👥 User Management</h2>
            <div class="top-header-actions">
                <div class="header-icons">
                    <a href="<?php echo BASE_URL; ?>/pages/create_user.php" class="btn btn-primary">+ Add New User</a>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <div class="card">
                    <h3 class="mb-lg">All Users (<?php echo count($users); ?>)</h3>
                    
                    <?php if (count($users) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge" style="background: #e0e7ff; color: #4f46e5;">
                                        <?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'Active'): ?>
                                        <span class="badge" style="background: #d1fae5; color: #059669;">Active</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #fee2e2; color: #dc2626;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(substr($user['created_at'], 0, 10), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $user['last_login'] ? htmlspecialchars(substr($user['last_login'], 0, 10), ENT_QUOTES, 'UTF-8') : '<em>Never</em>'; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/pages/edit_user.php?id=<?php echo (int)$user['user_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="<?php echo BASE_URL; ?>/pages/delete_user.php?id=<?php echo (int)$user['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted">No users found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
