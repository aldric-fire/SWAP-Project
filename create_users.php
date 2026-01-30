<?php
/**
 * Create Test Users Script
 * Navigate to: http://localhost/SWAP-Project/create_users.php
 */

require_once __DIR__ . '/config/db.php';

// Test users to create
$testUsers = [
    ['username' => 'admin', 'password' => 'password123', 'full_name' => 'Administrator', 'role' => 'Admin'],
    ['username' => 'manager_user', 'password' => 'password123', 'full_name' => 'John Manager', 'role' => 'Manager'],
    ['username' => 'staff_user', 'password' => 'password123', 'full_name' => 'Jane Staff', 'role' => 'Staff'],
    ['username' => 'auditor_user', 'password' => 'password123', 'full_name' => 'Audit Officer', 'role' => 'Auditor'],
];

try {
    foreach ($testUsers as $user) {
        // Check if user already exists
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ?');
        $stmt->execute([$user['username']]);
        
        if ($stmt->rowCount() === 0) {
            // Hash the password with bcrypt
            $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 10]);
            
            // Insert user
            $insertStmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?)'
            );
            $insertStmt->execute([
                $user['username'],
                $passwordHash,
                $user['full_name'],
                $user['role'],
                'Active'
            ]);
            
            echo "✓ Created user: <strong>{$user['username']}</strong> (Password: {$user['password']})<br>";
        } else {
            echo "ℹ User already exists: <strong>{$user['username']}</strong><br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>You can now login with these credentials:</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
    foreach ($testUsers as $user) {
        echo "<tr><td>{$user['username']}</td><td>{$user['password']}</td><td>{$user['role']}</td></tr>";
    }
    echo "</table>";
    echo "<p><a href='http://localhost/SWAP-Project'>← Back to Login</a></p>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>
