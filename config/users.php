<?php
/**
 * User Data Access
 *
 * Centralizes SQL away from presentation files.
 */

require_once __DIR__ . '/db.php';

function fetch_user_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT user_id, username, password_hash, role, status FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function update_last_login(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
}
