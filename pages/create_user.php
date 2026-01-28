<?php
/**
 * Create User Page (Admin only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../middleware/csrf.php';
require_once __DIR__ . '/../config/users_mgmt.php';
require_once __DIR__ . '/../config/audit.php';

require_role(['Admin']);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';

    // Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }

    if (empty($password) || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($fullName) || strlen($fullName) < 2) {
        $errors[] = 'Full name is required.';
    }

    if (!in_array($role, ['Admin', 'Manager', 'Staff', 'Auditor'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (username_exists($pdo, $username)) {
        $errors[] = 'Username already exists.';
    }

    // Create user if no errors
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            $userId = create_user($pdo, [
                'username' => $username,
                'password_hash' => $passwordHash,
                'full_name' => $fullName,
                'role' => $role,
                'status' => 'Active'
            ]);

            // Audit log
            log_audit($pdo, (int)$_SESSION['user_id'], 'CREATE', 'users', $userId, "Created new user: $username ($role)");

            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Error creating user. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - SWAP</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>➕ Create New User</h2>
        </header>

        <main>
            <div class="container">
                <div class="card" style="max-width: 500px; margin: 30px auto;">

                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✓ User created successfully! <a href="<?php echo BASE_URL; ?>/pages/users.php">Back to Users</a>
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
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required placeholder="e.g., john_doe" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" required placeholder="e.g., John Doe" value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="Admin" <?php echo isset($_POST['role']) && $_POST['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="Manager" <?php echo isset($_POST['role']) && $_POST['role'] === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                                <option value="Staff" <?php echo isset($_POST['role']) && $_POST['role'] === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="Auditor" <?php echo isset($_POST['role']) && $_POST['role'] === 'Auditor' ? 'selected' : ''; ?>>Auditor</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required placeholder="Min 8 characters">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                        </div>

                        <button type="submit" class="btn btn-primary">Create User</button>
                        <a href="<?php echo BASE_URL; ?>/pages/users.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
