<?php
/**
 * Edit User Page (Admin only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../middleware/csrf.php';
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

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $fullName = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($fullName) || strlen($fullName) < 2) {
        $errors[] = 'Full name is required.';
    }

    if (!in_array($role, ['Admin', 'Manager', 'Staff', 'Auditor'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (!in_array($status, ['Active', 'Inactive'], true)) {
        $errors[] = 'Invalid status.';
    }

    // Update user if no errors
    if (empty($errors)) {
        try {
            $updated = update_user($pdo, [
                'user_id' => $userId,
                'full_name' => $fullName,
                'role' => $role,
                'status' => $status
            ]);

            if ($updated) {
                // Audit log
                log_audit($pdo, (int)$_SESSION['user_id'], 'UPDATE', 'users', $userId, "Updated user: {$user['username']}");
                
                // Refresh user data
                $user = fetch_user_by_id($pdo, $userId);
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = 'Error updating user. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>✏️ Edit User: <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </header>

        <main>
            <div class="container">
                <div class="card" style="max-width: 500px; margin: 30px auto;">

                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✓ User updated successfully!
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
                        <?php echo csrf_token(); ?>

                        <div class="form-group">
                            <label>Username:</label>
                            <p><code><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></code> <small>(Read-only)</small></p>
                        </div>

                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" required placeholder="e.g., John Doe" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="Manager" <?php echo $user['role'] === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                                <option value="Staff" <?php echo $user['role'] === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="Auditor" <?php echo $user['role'] === 'Auditor' ? 'selected' : ''; ?>>Auditor</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $user['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <p class="text-muted" style="font-size: 0.9em;">
                            <strong>Created:</strong> <?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?><br>
                            <strong>Last Login:</strong> <?php echo $user['last_login'] ? htmlspecialchars($user['last_login'], ENT_QUOTES, 'UTF-8') : 'Never'; ?>
                        </p>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="<?php echo BASE_URL; ?>/pages/users.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
