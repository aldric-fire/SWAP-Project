<?php
/**
 * Stock Request Data Access
 *
 * Manages stock request operations.
 * Uses PDO prepared statements only. (OWASP A03: Injection)
 */

require_once __DIR__ . '/db.php';

/**
 * Submit stock request
 */
function submit_stock_request(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO stock_requests (item_id, requested_by, quantity, priority_score, status)
         VALUES (:item_id, :requested_by, :quantity, :priority_score, :status)'
    );

    $stmt->execute([
        ':item_id' => (int)$data['item_id'],
        ':requested_by' => (int)$data['requested_by'],
        ':quantity' => (int)$data['quantity'],
        ':priority_score' => (int)$data['priority_score'],
        ':status' => 'Pending'
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Fetch all pending requests
 */
function fetch_pending_requests(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT r.request_id, r.item_id, i.item_name, r.quantity, r.priority_score, 
                r.status, u.username as requested_by, r.created_at
         FROM stock_requests r
         LEFT JOIN inventory_items i ON i.item_id = r.item_id
         LEFT JOIN users u ON u.user_id = r.requested_by
         WHERE r.status = "Pending"
         ORDER BY r.priority_score DESC, r.created_at ASC'
    );
    return $stmt->fetchAll();
}

/**
 * Fetch request by ID
 */
function fetch_request_by_id(PDO $pdo, int $requestId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.*, i.item_name, i.quantity as current_quantity, 
                u.username as requested_by_name, m.username as manager_name
         FROM stock_requests r
         LEFT JOIN inventory_items i ON i.item_id = r.item_id
         LEFT JOIN users u ON u.user_id = r.requested_by
         LEFT JOIN users m ON m.user_id = r.manager_id
         WHERE r.request_id = :id'
    );
    $stmt->execute([':id' => $requestId]);
    return $stmt->fetch() ?: null;
}

/**
 * Approve stock request
 */
function approve_request(PDO $pdo, int $requestId, int $managerId): bool
{
    $stmt = $pdo->prepare(
        'UPDATE stock_requests SET status = :status, manager_id = :manager_id, updated_at = NOW()
         WHERE request_id = :request_id'
    );

    return $stmt->execute([
        ':status' => 'Approved',
        ':manager_id' => $managerId,
        ':request_id' => $requestId
    ]);
}

/**
 * Reject stock request
 */
function reject_request(PDO $pdo, int $requestId, int $managerId): bool
{
    $stmt = $pdo->prepare(
        'UPDATE stock_requests SET status = :status, manager_id = :manager_id, updated_at = NOW()
         WHERE request_id = :request_id'
    );

    return $stmt->execute([
        ':status' => 'Rejected',
        ':manager_id' => $managerId,
        ':request_id' => $requestId
    ]);
}

/**
 * Calculate priority score based on urgency
 * Higher score = higher priority
 */
function calculate_priority(int $quantity, string $urgency): int
{
    $baseScore = $quantity;
    
    switch ($urgency) {
        case 'High':
            return $baseScore * 3;
        case 'Medium':
            return $baseScore * 2;
        case 'Low':
        default:
            return $baseScore * 1;
    }
}

/**
 * Fetch user's stock requests
 */
function fetch_user_requests(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT r.request_id, r.item_id, i.item_name, r.quantity, r.priority_score, 
                r.status, r.created_at, r.updated_at
         FROM stock_requests r
         LEFT JOIN inventory_items i ON i.item_id = r.item_id
         WHERE r.requested_by = :user_id
         ORDER BY r.created_at DESC'
    );
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}
?>
