<?php
/**
 * Login Page (Username + Password) - DEBUG VERSION
 *
 * Uses PDO prepared statements, bcrypt verification, and CSRF protection.
 * Mitigates OWASP A07 (Identification & Authentication Failures) and A01.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/csrf.php';
require_once __DIR__ . '/../config/users.php';
require_once __DIR__ . '/../config/audit.php';

$errors = [];
$debug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    } else {
        // Lookup user by username (schema-approved field)
        $user = fetch_user_by_username($pdo, $username);
        $debug .= "User found: " . ($user ? 'YES' : 'NO') . "\n";
        
        if ($user) {
            $debug .= "User status: " . $user['status'] . "\n";
            $debug .= "User role: " . $user['role'] . "\n";
        }

        // Prevent user enumeration timing differences
        $dummyHash = '$2y$10$wH0QjW8sSsvKp0GgHq7A0uQ3z1dZ9l7s0FjWQK8yYj7T9G8t3Zs7S';
        $hashToCheck = $user['password_hash'] ?? $dummyHash;

        $passwordMatch = password_verify($password, $hashToCheck);
        $debug .= "Password match: " . ($passwordMatch ? 'YES' : 'NO') . "\n";
        $debug .= "User active: " . ($user && $user['status'] === 'Active' ? 'YES' : 'NO') . "\n";

        if ($user && $user['status'] === 'Active' && $passwordMatch) {
            $debug .= "LOGIN SUCCESSFUL\n";
            $debug .= "Setting session variables...\n";
            
            // Set session variables first
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            $debug .= "Session user_id: " . $_SESSION['user_id'] . "\n";
            $debug .= "Session username: " . $_SESSION['username'] . "\n";
            $debug .= "Session role: " . $_SESSION['role'] . "\n";

            // Now regenerate session ID to prevent session fixation (OWASP A07)
            session_regenerate_id(true);
            $debug .= "Session regenerated\n";

            // Update last_login for monitoring
            update_last_login($pdo, (int)$user['user_id']);

            // Audit log for login (example: action_type=LOGIN, target_table=users, target_id=5)
            log_audit(
                $pdo,
                (int)$user['user_id'],
                'LOGIN',
                'users',
                (int)$user['user_id'],
                'User logged in successfully.'
            );

            // Role-based redirect (server-side)
            switch ($user['role']) {
                case 'Admin':
                    $redirect = BASE_URL . '/pages/admin_dashboard.php';
                    break;
                case 'Staff':
                    $redirect = BASE_URL . '/pages/staff_dashboard.php';
                    break;
                case 'Manager':
                    $redirect = BASE_URL . '/pages/manager_dashboard.php';
                    break;
                case 'Auditor':
                    $redirect = BASE_URL . '/pages/auditor_dashboard.php';
                    break;
                default:
                    $redirect = BASE_URL . '/index.php';
            }

            $debug .= "Redirecting to: " . $redirect . "\n";
            header('Location: ' . $redirect);
            exit;
        } else {
            $debug .= "LOGIN FAILED\n";
        }

        $errors[] = 'Invalid credentials or inactive account.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <main>
        <h2>Login</h2>

        <?php if (!empty($debug)): ?>
            <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; white-space: pre-wrap; font-family: monospace;">
                <strong>DEBUG OUTPUT:</strong>
                <?php echo htmlspecialchars($debug); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>

            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Login</button>
        </form>
    </main>
</body>
</html>
