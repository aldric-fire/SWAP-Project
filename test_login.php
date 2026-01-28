<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/users.php';

// Test database connection
echo "Database connected!<br>";

// Test fetching user
$user = fetch_user_by_username($pdo, 'admin');
if ($user) {
    echo "User found!<br>";
    echo "User ID: " . htmlspecialchars($user['user_id']) . "<br>";
    echo "Username: " . htmlspecialchars($user['username']) . "<br>";
    echo "Password Hash: " . htmlspecialchars($user['password_hash']) . "<br>";
    echo "Status: " . htmlspecialchars($user['status']) . "<br>";
    echo "Role: " . htmlspecialchars($user['role']) . "<br>";
    
    // Test password verification
    $testPassword = 'test123';
    $result = password_verify($testPassword, $user['password_hash']);
    echo "<br>Password verify result for '$testPassword': " . ($result ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "User not found!<br>";
}
?>
