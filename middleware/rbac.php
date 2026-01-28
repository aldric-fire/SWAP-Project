<?php
/**
 * Role-Based Access Control (RBAC)
 *
 * Mitigates OWASP A01: Broken Access Control
 */

require_once __DIR__ . '/../config/session.php';

const ALLOWED_ROLES = ['Admin', 'Manager', 'Staff', 'Auditor'];

function require_login(): void
{
    global $SESSION_EXPIRED;

    if ($SESSION_EXPIRED || empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        http_response_code(403);
        echo 'Forbidden: authentication required.';
        exit;
    }

    if (!in_array($_SESSION['role'], ALLOWED_ROLES, true)) {
        http_response_code(403);
        echo 'Forbidden: invalid role.';
        exit;
    }
}

/**
 * Enforce role-based access.
 *
 * @param array $roles Allowed roles for the page.
 */
function require_role(array $roles): void
{
    require_login();

    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        echo 'Forbidden: insufficient permissions.';
        exit;
    }
}
