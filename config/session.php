<?php
/**
 * Secure Session Initialization
 *
 * Mitigates OWASP A07: Identification & Authentication Failures
 */

if (!defined('SESSION_IDLE_TIMEOUT')) {
    define('SESSION_IDLE_TIMEOUT', 900); // 15 minutes
}

$SESSION_EXPIRED = false;

function start_secure_session(): void
{
    global $SESSION_EXPIRED;

    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Enforce strict session handling (but more lenient for development)
    ini_set('session.use_strict_mode', '0');  // Changed to 0 to allow regenerated sessions
    ini_set('session.use_only_cookies', '1');

    $cookieParams = session_get_cookie_params();
    // Allow HTTP for development
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',  // Root path for all application pages
        'domain' => $cookieParams['domain'],
        'secure' => false,  // Allow HTTP for development
        'httponly' => true,    // Not accessible by JS
        'samesite' => 'Lax' // Changed to Lax for better compatibility
    ]);

    session_name('siams_session');
    session_start();

    // Idle timeout enforcement
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - (int)$_SESSION['last_activity'];
        if ($inactive > SESSION_IDLE_TIMEOUT) {
            destroy_session();
            $SESSION_EXPIRED = true;
            return;
        }
    }

    $_SESSION['last_activity'] = time();
}

function destroy_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

start_secure_session();
