<?php
/**
 * Login Page (Username + Password)
 *
 * Uses PDO prepared statements, bcrypt verification, and CSRF protection.
 * Mitigates OWASP A07 (Identification & Authentication Failures) and A01.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/csrf.php';
require_once __DIR__ . '/../config/users.php';
require_once __DIR__ . '/../config/audit.php';
require_once __DIR__ . '/../config/rate_limit.php';

// Generate CSRF token BEFORE any output or POST handling
$csrf_token = csrf_token();

$errors = [];
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    
    // Check rate limiting BEFORE processing login
    if (is_rate_limited($pdo, $ipAddress)) {
        $lockoutTime = get_lockout_time($pdo, $ipAddress);
        $minutes = ceil($lockoutTime / 60);
        $errors[] = "Too many login attempts. Please try again in {$minutes} minute(s).";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    } else {
        // Lookup user by username (schema-approved field)
        $user = fetch_user_by_username($pdo, $username);

        // Prevent user enumeration timing differences
        $dummyHash = '$2y$10$wH0QjW8sSsvKp0GgHq7A0uQ3z1dZ9l7s0FjWQK8yYj7T9G8t3Zs7S';
        $hashToCheck = $user['password_hash'] ?? $dummyHash;

        if ($user && $user['status'] === 'Active' && password_verify($password, $hashToCheck)) {
            // Clear failed attempts on successful login
            clear_login_attempts($pdo, $ipAddress);
            
            // Set session variables
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['logged_in'] = true;

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

            header('Location: ' . $redirect);
            exit;
        }

        // Log failed attempt and show generic error
        log_failed_attempt($pdo, $username, $ipAddress);
        $errors[] = 'Invalid credentials or inactive account.';
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIAMS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left side - Illustration area */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .illustration-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }

        .illustration-icon {
            font-size: 8rem;
            margin-bottom: 2rem;
            display: block;
        }

        .illustration-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: white;
        }

        .illustration-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 300px;
            line-height: 1.6;
            color: white;
        }

        .decoration-dots {
            position: absolute;
            width: 100px;
            height: 100px;
            opacity: 0.1;
        }

        .decoration-dots.top-right {
            top: 10%;
            right: 10%;
            border: 3px dotted white;
            border-radius: 50%;
        }

        .decoration-dots.bottom-left {
            bottom: 15%;
            left: 15%;
            border: 3px dotted #3b82f6;
            border-radius: 50%;
        }

        /* Right side - Form area */
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: white;
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
        }

        .brand-header {
            margin-bottom: 0.5rem;
        }

        .brand-header span {
            font-size: 0.9rem;
            color: #3b82f6;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .welcome-text {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .welcome-subtitle {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .alert {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-error p {
            margin: 0.25rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-error p::before {
            content: "✗";
            font-size: 1.25rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s;
            background: #f9fafb;
            color: #1f2937;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .btn-sign-in {
            width: 100%;
            padding: 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }

        .btn-sign-in:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-sign-in:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            color: #9ca3af;
            font-size: 0.85rem;
        }

        .login-footer span {
            color: #3b82f6;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .login-left {
                display: none;
            }

            .login-right {
                padding: 2rem;
            }
        }

        @media (max-width: 480px) {
            .login-right {
                padding: 1.5rem;
            }

            .welcome-text {
                font-size: 1.75rem;
            }

            .form-group input {
                padding: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - Illustration -->
        <div class="login-left">
            <div class="decoration-dots top-right"></div>
            <div class="decoration-dots bottom-left"></div>
            
            <div class="illustration-content">
                <span class="illustration-icon">📦</span>
                <h1 class="illustration-title">SIAMS</h1>
                <p class="illustration-subtitle">Secure Inventory & Asset Management System for modern enterprises</p>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="login-right">
            <div class="login-form-container">
                <div class="brand-header">
                    <span>🔐 SIAMS Portal</span>
                </div>
                
                <h2 class="welcome-text">Welcome Back!</h2>
                <p class="welcome-subtitle">Please enter your credentials to access the inventory management system.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrf_field(); ?>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn-sign-in">Sign In</button>
                </form>

                <div class="login-footer">
                    Secure login with <span>role-based access control</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
