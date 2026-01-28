<?php
/**
 * Logout Endpoint
 *
 * Fully destroys session and logs the action.
 * Mitigates OWASP A07: Identification & Authentication Failures
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/audit.php';

if (!empty($_SESSION['user_id'])) {
    // Audit log for logout (example: action_type=LOGOUT, target_table=users, target_id=5)
    log_audit(
        $pdo,
        (int)$_SESSION['user_id'],
        'LOGOUT',
        'users',
        (int)$_SESSION['user_id'],
        'User logged out.'
    );
}

destroy_session();

header('Location: ' . BASE_URL . '/auth/login.php');
exit;
