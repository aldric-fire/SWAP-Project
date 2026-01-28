<?php
/**
 * Database Connection (PDO)
 *
 * Uses PDO with prepared statements only. (OWASP A03: Injection)
 * Database credentials should be stored outside public directories in production.
 */

// Base URL for redirects and links (HTTP only for development)
$protocol = 'http';  // Use HTTP for development
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = '/SWAP-Project';
if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . '://' . $host . $basePath);
}

// Database configuration
if (!defined('DB_HOST')) { define('DB_HOST', 'localhost'); }
if (!defined('DB_USER')) { define('DB_USER', 'root'); }
if (!defined('DB_PASS')) { define('DB_PASS', ''); }
if (!defined('DB_NAME')) { define('DB_NAME', 'products_db'); }

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Do not leak details to the client (OWASP A07: Identification & Authentication Failures)
    http_response_code(500);
    echo 'Database connection error.';
    exit;
}
