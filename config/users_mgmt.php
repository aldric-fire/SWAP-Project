<?php
/**
 * User Management Data Access
 *
 * Centralizes user-related database operations.
 * Uses PDO prepared statements only. (OWASP A03: Injection)
 */

require_once __DIR__ . '/db.php';

/**
 * Fetch all users
 */
function fetch_all_users(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT user_id, username, full_name, role, status, created_at, last_login
         FROM users
         ORDER BY role DESC, username ASC'
    );
    return $stmt->fetchAll();
}

/**
 * Fetch user by ID
 */
function fetch_user_by_id(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Create new user
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
        ':full_name' => $data['full_name'],
        ':role' => $data['role'],
        ':status' => $data['status'] ?? 'Active'
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Update user
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
        ':full_name' => $data['full_name'],
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
