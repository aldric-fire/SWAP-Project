<?php
/**
 * Stock Request Data Access
 *
 * Manages stock request operations with enhanced prioritization.
 * Uses PDO prepared statements only. (OWASP A03: Injection)
 * SECURITY: All inputs validated, all outputs escaped, RBAC enforced at caller level
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/supplier_defaults.php';

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
 * Fetch all pending requests with enhanced priority info
 */
function fetch_pending_requests(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT r.request_id, r.item_id, i.item_name, i.quantity as current_quantity, 
                i.min_threshold, i.supplier_id, r.quantity, r.priority_score, 
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
        'SELECT r.*, i.item_name, i.quantity as current_quantity, i.min_threshold, i.supplier_id,
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
    try {
        $pdo->beginTransaction();
        
        // Get request details (item_id and quantity)
        $stmt = $pdo->prepare('SELECT item_id, quantity FROM stock_requests WHERE request_id = :request_id');
        $stmt->execute([':request_id' => (int)$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            $pdo->rollBack();
            return false;
        }
        
        // Update request status to Approved
        $stmt = $pdo->prepare(
            'UPDATE stock_requests SET status = :status, manager_id = :manager_id, updated_at = NOW()
             WHERE request_id = :request_id'
        );
        $stmt->execute([
            ':status' => 'Approved',
            ':manager_id' => (int)$managerId,
            ':request_id' => (int)$requestId
        ]);
        
        // NOTE: Approval does NOT decrement inventory
        // Stock is only reduced when items are physically allocated/dispatched
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Reject stock request
 * Note: Rejecting does NOT modify inventory (request was never fulfilled)
 */
function reject_request(PDO $pdo, int $requestId, int $managerId): bool
{
    $stmt = $pdo->prepare(
        'UPDATE stock_requests SET status = :status, manager_id = :manager_id, updated_at = NOW()
         WHERE request_id = :request_id'
    );

    return $stmt->execute([
        ':status' => 'Rejected',
        ':manager_id' => (int)$managerId,
        ':request_id' => (int)$requestId
    ]);
}

/**
 * Calculate multi-factor priority score
 * SECURITY: All inputs validated before calculation
 * 
 * Factors:
 * - Quantity × Urgency: Base score (quantity weighted by urgency level)
 * - Stock Shortage Ratio: How close to minimum threshold (0-50 points)
 * - Request Frequency: How often this item is requested (0-30 points)
 * - Supplier Lead Time: How long delivery takes (0-20 points)
 * 
 * @param int $quantity - Quantity requested
 * @param string $urgency - Urgency level (Low/Medium/High)
 * @param array $item - Item data from database (quantity, min_threshold, supplier_id)
 * @param int $frequency - Request frequency (how many times in last 30 days)
 * @return int Priority score (higher = more urgent)
 */
function calculate_priority_enhanced(int $quantity, string $urgency, array $item, int $frequency = 0): int
{
    // Validate inputs
    $quantity = max(1, (int)$quantity);
    $urgency = in_array($urgency, ['Low', 'Medium', 'High']) ? $urgency : 'Low';
    $frequency = max(0, (int)$frequency);
    
    // Factor 1: Request Size Ratio (0-300 points)
    // Uses ratio instead of raw quantity to normalize across different stock levels
    $urgency_weights = ['Low' => 1, 'Medium' => 2, 'High' => 3];
    $base_score = 0;
    if (isset($item['quantity']) && $item['quantity'] > 0) {
        // Calculate what percentage of available stock is being requested
        $request_ratio = min(1.0, $quantity / (int)$item['quantity']);
        // Scale to 0-100, then multiply by urgency weight (1-3)
        $base_score = (int)($request_ratio * 100 * $urgency_weights[$urgency]);
    } else {
        // Fallback if item data missing: cap quantity impact at 100
        $base_score = min(100, $quantity) * $urgency_weights[$urgency];
    }
    
    // Factor 2: Stock Shortage Ratio (0-50 points)
    $stock_shortage_score = 0;
    if (isset($item['quantity']) && isset($item['min_threshold'])) {
        $current_qty = max(0, (int)$item['quantity']);
        $min_threshold = max(1, (int)$item['min_threshold']);
        
        // If below minimum, calculate shortage urgency
        if ($current_qty < $min_threshold) {
            $shortage_ratio = min(1.0, ($min_threshold - $current_qty) / $min_threshold);
            $stock_shortage_score = (int)($shortage_ratio * 50);
        }
    }
    
    // Factor 3: Request Frequency Score (0-30 points)
    // High frequency items get priority boost
    $frequency_score = min(30, $frequency * 3);
    
    // Factor 4: Supplier Lead Time Score (0-20 points)
    // Items from slow suppliers get earlier priority
    $lead_time_score = 0;
    if (isset($item['supplier_id']) && $item['supplier_id']) {
        $lead_time = get_supplier_lead_time((int)$item['supplier_id']);
        // Normalize to 0-20 scale (7 days = 0, 30+ days = 20)
        $lead_time_score = max(0, min(20, ($lead_time - 7) * 1));
    }
    
    // Total priority score (max ~400)
    $total_priority = $base_score + $stock_shortage_score + $frequency_score + $lead_time_score;
    
    return max(1, (int)$total_priority); // Ensure at least 1
}

/**
 * LEGACY: Keep old function for backward compatibility
 * New code should use calculate_priority_enhanced()
 */
function calculate_priority(int $quantity, string $urgency): int
{
    return calculate_priority_enhanced($quantity, $urgency, []);
}

/**
 * Get request frequency for an item (last 30 days)
 * SECURITY: Uses prepared statement to prevent SQL injection
 * 
 * @param PDO $pdo - Database connection
 * @param int $item_id - Item ID
 * @return int Number of requests in last 30 days
 */
function get_item_request_frequency(PDO $pdo, int $item_id): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) as frequency FROM stock_requests 
         WHERE item_id = :item_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );
    $stmt->execute([':item_id' => (int)$item_id]);
    $result = $stmt->fetch();
    
    return $result ? (int)$result['frequency'] : 0;
}

/**
 * Get all request frequencies for dashboard
 * SECURITY: Aggregates data safely with prepared statements
 * 
 * @param PDO $pdo - Database connection
 * @return array Associative array [item_id => frequency_count]
 */
function get_all_item_frequencies(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT item_id, COUNT(*) as frequency FROM stock_requests 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY item_id'
    );
    
    $frequencies = [];
    foreach ($stmt->fetchAll() as $row) {
        $frequencies[(int)$row['item_id']] = (int)$row['frequency'];
    }
    
    return $frequencies;
}

/**
 * Get expected delivery date for approved request
 * SECURITY: Uses safe date calculation functions
 * 
 * @param int $supplier_id - Supplier ID (can be null for unassigned items)
 * @param string $approval_date - Date of approval (YYYY-MM-DD)
 * @return string Expected delivery date (YYYY-MM-DD) or null if invalid
 */
function get_expected_delivery_date($supplier_id, $approval_date): ?string
{
    if (!$approval_date) {
        return null;
    }
    
    $lead_time = $supplier_id ? get_supplier_lead_time((int)$supplier_id) : 7;
    return calculate_delivery_date($approval_date, $lead_time);
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
