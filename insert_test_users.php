<?php
/**
 * Insert test users into database
 */

require_once __DIR__ . '/config/db.php';

$password = "password123";
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

$users = [
    ['staff_user', 'Sara Thompson', 'Staff'],
    ['manager_user', 'Michael Johnson', 'Manager'],
    ['auditor_user', 'Anne Davis', 'Auditor']
];

echo "<h2>Creating Test Users</h2>";

foreach ($users as $user) {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password_hash, full_name, role, status) 
             VALUES (:username, :password_hash, :full_name, :role, :status)'
        );
        
        $stmt->execute([
            ':username' => $user[0],
            ':password_hash' => $hash,
            ':full_name' => $user[1],
            ':role' => $user[2],
            ':status' => 'Active'
        ]);
        
        echo "<p style='color: green;'>✓ Created: <strong>{$user[0]}</strong> ({$user[2]})</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Failed: {$user[0]} - " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<h3>Test Credentials</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin / password123</li>";
echo "<li><strong>Manager:</strong> manager_user / password123</li>";
echo "<li><strong>Staff:</strong> staff_user / password123</li>";
echo "<li><strong>Auditor:</strong> auditor_user / password123</li>";
echo "</ul>";

// Verify users exist
echo "<h3>Database Verification</h3>";
$stmt = $pdo->query('SELECT user_id, username, role, status FROM users ORDER BY role DESC');
$allUsers = $stmt->fetchAll();
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th></tr>";
foreach ($allUsers as $u) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($u['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars($u['username']) . "</td>";
    echo "<td>" . htmlspecialchars($u['role']) . "</td>";
    echo "<td>" . htmlspecialchars($u['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
