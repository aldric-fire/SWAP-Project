<?php
/**
 * Generate test users with bcrypt hashes
 * Run once and copy SQL output to phpMyAdmin
 */

$password = "password123";
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

echo "=== BCRYPT HASH ===\n";
echo $hash . "\n\n";

echo "=== SQL INSERT STATEMENTS ===\n";
echo "INSERT INTO users (username, password_hash, full_name, role, status) VALUES\n";
echo "('staff_user', '" . $hash . "', 'Sara Thompson', 'Staff', 'Active'),\n";
echo "('manager_user', '" . $hash . "', 'Michael Johnson', 'Manager', 'Active'),\n";
echo "('auditor_user', '" . $hash . "', 'Anne Davis', 'Auditor', 'Active');\n";

echo "\n=== TEST CREDENTIALS ===\n";
echo "Staff User: staff_user / password123\n";
echo "Manager User: manager_user / password123\n";
echo "Auditor User: auditor_user / password123\n";
echo "Admin User: admin / password123 (already created)\n";
?>
