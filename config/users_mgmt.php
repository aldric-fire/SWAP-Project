<?php
/**
 * User Management Data Access
 *
 * Centralizes user-related database operations.
 * Uses PDO prepared statements only. (OWASP A03: Injection)
 * Includes field-level encryption for sensitive PII (full_name)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/encryption.php';

/**
 * Fetch all users
 * Decrypts full_name field before returning
 */
function fetch_all_users(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT user_id, username, full_name, role, status, created_at, last_login
         FROM users
         ORDER BY role DESC, username ASC'
    );
    $users = $stmt->fetchAll();
    
    // Decrypt sensitive fields
    foreach ($users as &$user) {
        $user['full_name'] = decrypt_field($user['full_name']);
    }
    
    return $users;
}

/**
 * Fetch user by ID
 * Decrypts full_name field before returning
 */
function fetch_user_by_id(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Decrypt sensitive fields
        $user['full_name'] = decrypt_field($user['full_name']);
    }
    
    return $user ?: null;
}

/**
 * Create new user
 * Encrypts full_name before storing in database
 */
function create_user(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, full_name, role, status)
         VALUES (:username, :password_hash, :full_name, :role, :status)'
    );

    $stmt->execute([
        ':username' => $data['username'],
        ':password_hash' => $data['password_hash'],
        ':full_name' => encrypt_field($data['full_name']),
        ':role' => $data['role'],
        ':status' => $data['status'] ?? 'Active'
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Update user
 * Encrypts full_name before updating in database
 */
function update_user(PDO $pdo, array $data): bool
{
    // Only update if user_id is provided and valid
    if (empty($data['user_id'])) {
        return false;
    }

    $stmt = $pdo->prepare(
        'UPDATE users SET full_name = :full_name, role = :role, status = :status WHERE user_id = :user_id'
    );

    return $stmt->execute([
        ':user_id' => (int)$data['user_id'],
        ':full_name' => encrypt_field($data['full_name']),
        ':role' => $data['role'],
        ':status' => $data['status']
    ]);
}

/**
 * Delete user
 */
function delete_user(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :id');
    return $stmt->execute([':id' => $userId]);
}

/**
 * Check if username exists (for validation)
 */
function username_exists(PDO $pdo, string $username, ?int $excludeUserId = null): bool
{
    if ($excludeUserId) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username AND user_id != :exclude_id');
        $stmt->execute([':username' => $username, ':exclude_id' => $excludeUserId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
    }
    return (int)$stmt->fetchColumn() > 0;
}
?>
