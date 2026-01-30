<?php
/**
 * Report Queries & Helpers
 *
 * Centralised report-related database functions
 */

if (!isset($pdo)) {
    die('Database connection not found.');
}

/**
 * Inventory summary
 */
function get_inventory_summary(PDO $pdo)
{
    $stmt = $pdo->query(
        "SELECT 
            COUNT(*) AS total_items,
            SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) AS available,
            SUM(CASE WHEN status = 'Low Stock' THEN 1 ELSE 0 END) AS low_stock,
            SUM(CASE WHEN status = 'Out of Stock' THEN 1 ELSE 0 END) AS out_of_stock,
            SUM(quantity) AS total_quantity
        FROM inventory_items"
    );
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Stock request summary
 */
function get_request_summary(PDO $pdo)
{
    $stmt = $pdo->query(
        "SELECT 
            COUNT(*) AS total_requests,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
        FROM stock_requests"
    );
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Low stock items
 */
function get_low_stock_items(PDO $pdo, int $limit = 10)
{
    $stmt = $pdo->prepare(
        "SELECT item_id, item_name, quantity, min_threshold, status
         FROM inventory_items
         WHERE status IN ('Low Stock', 'Out of Stock')
         ORDER BY quantity ASC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Audit activity summary (last X days)
 */
function get_audit_summary(PDO $pdo, int $days = 30)
{
    $stmt = $pdo->prepare(
        "SELECT action, COUNT(*) AS count
         FROM audit_logs
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
         GROUP BY action
         ORDER BY count DESC"
    );
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Top requesters
 */
function get_top_requesters(PDO $pdo, int $limit = 5)
{
    $stmt = $pdo->prepare(
        "SELECT 
            u.username,
            COUNT(r.request_id) AS request_count
         FROM stock_requests r
         LEFT JOIN users u ON u.user_id = r.requested_by
         GROUP BY r.requested_by
         ORDER BY request_count DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch audit logs with date filter (used by view/export)
 */
function get_audit_logs(PDO $pdo, ?string $from = null, ?string $to = null)
{
    $query = "
        SELECT a.created_at, u.username, a.action, a.description, a.ip_address
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.user_id
        WHERE 1=1
    ";

    $params = [];

    if ($from) {
        $query .= " AND a.created_at >= :from";
        $params['from'] = $from . ' 00:00:00';
    }

    if ($to) {
        $query .= " AND a.created_at <= :to";
        $params['to'] = $to . ' 23:59:59';
    }

    $query .= " ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
