<?php
/**
 * Audit Logging
 *
 * Centralizes audit log writes for accountability.
 */

require_once __DIR__ . '/db.php';

function log_audit(PDO $pdo, int $userId, string $actionType, string $targetTable, int $targetId, string $description): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action_type, target_table, target_id, description)
         VALUES (:user_id, :action_type, :target_table, :target_id, :description)'
    );

    $stmt->execute([
        ':user_id' => $userId,
        ':action_type' => $actionType,
        ':target_table' => $targetTable,
        ':target_id' => $targetId,
        ':description' => $description
    ]);
}
