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

/**
 * Recommend optimal suppliers for an approved request
 * SECURITY: PDO prepared statements prevent SQL injection (A03)
 * 
 * @param PDO $pdo - Database connection
 * @param int $item_id - Item ID from request
 * @param int $quantity - Quantity needed
 * @return array Supplier recommendations sorted by score
 */
function recommend_optimal_supplier(PDO $pdo, int $item_id, int $quantity): array
{
    // Validate inputs (A03: Injection prevention)
    $item_id = (int)$item_id;
    $quantity = max(1, (int)$quantity);
    
    // Get item details with supplier info
    $stmt = $pdo->prepare(
        'SELECT i.item_id, i.item_name, i.supplier_id, s.supplier_name, 
                i.quantity as available_stock
         FROM inventory_items i
         LEFT JOIN suppliers s ON s.supplier_id = i.supplier_id
         WHERE i.item_id = :item_id
         LIMIT 1'
    );
    $stmt->execute([':item_id' => $item_id]);
    $item = $stmt->fetch();
    
    if (!$item || !$item['supplier_id']) {
        return ['error' => 'No supplier assigned to this item'];
    }
    
    // Build recommendation
    $supplier_id = (int)$item['supplier_id'];
    $lead_time = get_supplier_lead_time($supplier_id);
    $delivery_date = calculate_delivery_date(date('Y-m-d'), $lead_time);
    $available_stock = (int)$item['available_stock'];
    
    // Calculate recommendation score
    // Lower lead time = higher score, Higher stock availability = higher score
    $lead_time_score = max(0, 100 - ($lead_time * 3));
    $stock_score = min(100, ($available_stock / max(1, $quantity)) * 50);
    $total_score = (int)($lead_time_score + $stock_score);
    
    return [
        'supplier_id' => $supplier_id,
        'supplier_name' => htmlspecialchars($item['supplier_name'], ENT_QUOTES, 'UTF-8'),
        'lead_time_days' => $lead_time,
        'expected_delivery' => $delivery_date,
        'available_stock' => $available_stock,
        'requested_qty' => $quantity,
        'can_fulfill' => $available_stock >= $quantity,
        'recommendation_score' => $total_score
    ];
}

/**
 * Detect consolidation opportunities (multiple pending requests from same supplier)
 * SECURITY: PDO with GROUP BY aggregation, output sanitized
 * 
 * @param PDO $pdo - Database connection
 * @return array Consolidation opportunities grouped by supplier
 */
function get_consolidation_opportunities(PDO $pdo): array
{
    // Safe aggregation query with PDO
    $stmt = $pdo->query(
        'SELECT s.supplier_id, s.supplier_name, 
                COUNT(r.request_id) as total_requests,
                SUM(r.quantity) as total_items,
                GROUP_CONCAT(r.request_id ORDER BY r.priority_score DESC SEPARATOR ",") as request_ids,
                GROUP_CONCAT(i.item_name ORDER BY r.priority_score DESC SEPARATOR " | ") as items
         FROM stock_requests r
         JOIN inventory_items i ON i.item_id = r.item_id
         JOIN suppliers s ON s.supplier_id = i.supplier_id
         WHERE r.status = "Pending"
         GROUP BY s.supplier_id, s.supplier_name
         HAVING COUNT(r.request_id) > 1
         ORDER BY COUNT(r.request_id) DESC'
    );
    
    $opportunities = $stmt->fetchAll();
    
    // Sanitize output (XSS prevention)
    foreach ($opportunities as &$opp) {
        $opp['supplier_name'] = htmlspecialchars($opp['supplier_name'], ENT_QUOTES, 'UTF-8');
        $opp['items'] = htmlspecialchars($opp['items'], ENT_QUOTES, 'UTF-8');
        $opp['total_requests'] = (int)$opp['total_requests'];
        $opp['total_items'] = (int)$opp['total_items'];
    }
    
    return $opportunities;
}

/**
 * Bulk approve multiple requests (consolidation action)
 * SECURITY: Authorization check, CSRF validated at caller level, PDO prepared statements
 * 
 * @param PDO $pdo - Database connection
 * @param array $request_ids - Array of request IDs to approve
 * @param int $manager_id - Manager performing approval
 * @return bool Success status
 */
function approve_bulk_requests(PDO $pdo, array $request_ids, int $manager_id): bool
{
    // Validate inputs
    $request_ids = array_map('intval', $request_ids);
    $manager_id = (int)$manager_id;
    
    if (empty($request_ids)) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        // SECURITY: Verify all requests are valid and pending (authorization check)
        $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
        $stmt = $pdo->prepare(
            "SELECT request_id FROM stock_requests 
             WHERE request_id IN ($placeholders) AND status = 'Pending'"
        );
        $stmt->execute($request_ids);
        $valid_requests = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Only approve valid pending requests
        if (count($valid_requests) !== count($request_ids)) {
            $pdo->rollBack();
            return false; // Attempted to approve non-pending request
        }
        
        // Bulk update with prepared statement (A03: Injection prevention)
        $placeholders = implode(',', array_fill(0, count($valid_requests), '?'));
        $stmt = $pdo->prepare(
            "UPDATE stock_requests 
             SET status = 'Approved', manager_id = ?, updated_at = NOW()
             WHERE request_id IN ($placeholders)"
        );
        
        $params = array_merge([$manager_id], $valid_requests);
        $stmt->execute($params);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Bulk approval error: ' . $e->getMessage());
        return false;
    }
}
?>
