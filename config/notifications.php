<?php
/**
 * Email Notification System
 * 
 * Handles sending emails for stock request approvals, rejections, and alerts.
 * SECURITY: All inputs validated, no injection vectors, no sensitive data in email logs
 */

require_once __DIR__ . '/email_config.php';

/**
 * Build safe email headers using configured sender.
 */
function build_email_headers(): string
{
    $fromAddress = defined('EMAIL_SENDER_ADDRESS') ? EMAIL_SENDER_ADDRESS : 'siams-noreply@localhost';
    $fromName = defined('EMAIL_SENDER_NAME') ? EMAIL_SENDER_NAME : 'SIAMS System';

    $safeFromAddress = filter_var($fromAddress, FILTER_VALIDATE_EMAIL) ? $fromAddress : 'siams-noreply@localhost';
    $safeFromName = trim(preg_replace('/[\r\n]+/', ' ', $fromName));

    $headers = 'From: ' . $safeFromName . ' <' . $safeFromAddress . ">\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return $headers;
}

/**
 * Send request approval notification to staff
 * SECURITY: Email recipient validated from database, no user input in to/from fields
 * 
 * @param PDO $pdo - Database connection
 * @param int $request_id - Request ID
 * @param int $manager_id - Manager ID who approved
 * @return bool Success/failure
 */
function send_approval_notification($pdo, int $request_id, int $manager_id): bool
{
    try {
        if (!defined('ENABLE_EMAIL_NOTIFICATIONS') || !ENABLE_EMAIL_NOTIFICATIONS) {
            return false;
        }
        if (defined('ENABLE_APPROVAL_EMAILS') && !ENABLE_APPROVAL_EMAILS) {
            return false;
        }

        // Fetch request details safely
        $stmt = $pdo->prepare(
            'SELECT r.*, i.item_name, u.username as requested_by_name, u.email as staff_email
             FROM stock_requests r
             LEFT JOIN inventory_items i ON i.item_id = r.item_id
             LEFT JOIN users u ON u.user_id = r.requested_by
             WHERE r.request_id = :id'
        );
        $stmt->execute([':id' => (int)$request_id]);
        $request = $stmt->fetch();
        
        if (!$request || !$request['staff_email']) {
            return false; // No valid email
        }

        if (!filter_var($request['staff_email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Fetch manager details
        $stmt = $pdo->prepare('SELECT full_name FROM users WHERE user_id = :id');
        $stmt->execute([':id' => (int)$manager_id]);
        $manager = $stmt->fetch();
        $manager_name = $manager ? htmlspecialchars($manager['full_name'], ENT_QUOTES, 'UTF-8') : 'Manager';
        
        // Build email
        $to = $request['staff_email'];
        $subject = "Stock Request Approved - Item: " . htmlspecialchars($request['item_name'], ENT_QUOTES, 'UTF-8');
        
        $message = "Dear " . htmlspecialchars($request['requested_by_name'], ENT_QUOTES, 'UTF-8') . ",\n\n";
        $message .= "Your stock request has been APPROVED!\n\n";
        $message .= "Request Details:\n";
        $message .= "  Item: " . htmlspecialchars($request['item_name'], ENT_QUOTES, 'UTF-8') . "\n";
        $message .= "  Quantity: " . (int)$request['quantity'] . "\n";
        $message .= "  Requested: " . htmlspecialchars(substr($request['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') . "\n";
        $message .= "  Approved By: " . $manager_name . "\n\n";
        $message .= "Priority Score: " . (int)$request['priority_score'] . "\n";
        $message .= "Status: " . htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') . "\n\n";
        $message .= "---\nThis is an automated notification from SIAMS.\n";

        $headers = build_email_headers();
        
        // Send email (non-blocking - don't fail request if email fails)
        return @mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        if (!defined('EMAIL_LOG_FAILURES') || EMAIL_LOG_FAILURES) {
            error_log('Email notification error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Send request rejection notification to staff
 * SECURITY: Email recipient validated from database, no injection vectors
 * 
 * @param PDO $pdo - Database connection
 * @param int $request_id - Request ID
 * @param int $manager_id - Manager ID who rejected
 * @return bool Success/failure
 */
function send_rejection_notification($pdo, int $request_id, int $manager_id): bool
{
    try {
        if (!defined('ENABLE_EMAIL_NOTIFICATIONS') || !ENABLE_EMAIL_NOTIFICATIONS) {
            return false;
        }
        if (defined('ENABLE_REJECTION_EMAILS') && !ENABLE_REJECTION_EMAILS) {
            return false;
        }

        // Fetch request details safely
        $stmt = $pdo->prepare(
            'SELECT r.*, i.item_name, u.username as requested_by_name, u.email as staff_email
             FROM stock_requests r
             LEFT JOIN inventory_items i ON i.item_id = r.item_id
             LEFT JOIN users u ON u.user_id = r.requested_by
             WHERE r.request_id = :id'
        );
        $stmt->execute([':id' => (int)$request_id]);
        $request = $stmt->fetch();
        
        if (!$request || !$request['staff_email']) {
            return false; // No valid email
        }

        if (!filter_var($request['staff_email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Fetch manager details
        $stmt = $pdo->prepare('SELECT full_name FROM users WHERE user_id = :id');
        $stmt->execute([':id' => (int)$manager_id]);
        $manager = $stmt->fetch();
        $manager_name = $manager ? htmlspecialchars($manager['full_name'], ENT_QUOTES, 'UTF-8') : 'Manager';
        
        // Build email
        $to = $request['staff_email'];
        $subject = "Stock Request Not Approved - Item: " . htmlspecialchars($request['item_name'], ENT_QUOTES, 'UTF-8');
        
        $message = "Dear " . htmlspecialchars($request['requested_by_name'], ENT_QUOTES, 'UTF-8') . ",\n\n";
        $message .= "Your stock request could not be approved at this time.\n\n";
        $message .= "Request Details:\n";
        $message .= "  Item: " . htmlspecialchars($request['item_name'], ENT_QUOTES, 'UTF-8') . "\n";
        $message .= "  Quantity: " . (int)$request['quantity'] . "\n";
        $message .= "  Requested: " . htmlspecialchars(substr($request['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') . "\n";
        $message .= "  Reviewed By: " . $manager_name . "\n\n";
        $message .= "Please contact your manager for more information.\n\n";
        $message .= "---\nThis is an automated notification from SIAMS.\n";

        $headers = build_email_headers();
        
        // Send email (non-blocking)
        return @mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        if (!defined('EMAIL_LOG_FAILURES') || EMAIL_LOG_FAILURES) {
            error_log('Email notification error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Send low stock alert to managers
 * SECURITY: Only managers receive alerts, all data validated
 * 
 * @param PDO $pdo - Database connection
 * @param int $item_id - Item ID below threshold
 * @return bool Success/failure
 */
function send_low_stock_alert($pdo, int $item_id): bool
{
    try {
        if (!defined('ENABLE_EMAIL_NOTIFICATIONS') || !ENABLE_EMAIL_NOTIFICATIONS) {
            return false;
        }
        if (defined('ENABLE_LOW_STOCK_ALERTS') && !ENABLE_LOW_STOCK_ALERTS) {
            return false;
        }

        // Fetch item details
        $stmt = $pdo->prepare('SELECT item_name, quantity, min_threshold FROM inventory_items WHERE item_id = :id');
        $stmt->execute([':id' => (int)$item_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return false;
        }
        
        // Get all manager emails
        $stmt = $pdo->query('SELECT email FROM users WHERE role = "Manager" AND status = "Active"');
        $managers = $stmt->fetchAll();
        
        if (empty($managers)) {
            return false; // No managers to notify
        }
        
        $subject = "⚠️ Low Stock Alert: " . htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8');
        
        $message = "Stock Alert - Item Below Minimum Threshold\n\n";
        $message .= "Item: " . htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') . "\n";
        $message .= "Current Stock: " . (int)$item['quantity'] . "\n";
        $message .= "Minimum Threshold: " . (int)$item['min_threshold'] . "\n\n";
        $message .= "Please review inventory and consider ordering more stock.\n\n";
        $message .= "---\nThis is an automated notification from SIAMS.\n";
        
        $headers = build_email_headers();
        
        $sent = 0;
        foreach ($managers as $manager) {
            if (!isset($manager['email']) || !filter_var($manager['email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            if (@mail($manager['email'], $subject, $message, $headers)) {
                $sent++;
            }
        }
        
        return $sent > 0;
        
    } catch (Exception $e) {
        if (!defined('EMAIL_LOG_FAILURES') || EMAIL_LOG_FAILURES) {
            error_log('Low stock alert error: ' . $e->getMessage());
        }
        return false;
    }
}
