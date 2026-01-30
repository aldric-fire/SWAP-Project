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
/**
 * Format large numbers with abbreviations
 * Supports: K, M, B, T, Q, Qi, S, Se, O, N, D (up to Decillion)
 * Example: 1000 → 1K, 1000000 → 1M, 1000000000000000 → 1Q, etc.
 */
function format_number($number) {
    $number = (float)$number; // Use float to preserve large numbers and avoid int overflow
    
    // Check from largest to smallest scale
    if ($number >= 1000000000000) { // Trillion (1e12)
        $formatted = round($number / 1000000000000, 1);
        return ($formatted == (int)$formatted ? (int)$formatted : $formatted) . 'T';
    } elseif ($number >= 1000000000) { // Billion (1e9)
        $formatted = round($number / 1000000000, 1);
        return ($formatted == (int)$formatted ? (int)$formatted : $formatted) . 'B';
    } elseif ($number >= 1000000) { // Million (1e6)
        $formatted = round($number / 1000000, 1);
        return ($formatted == (int)$formatted ? (int)$formatted : $formatted) . 'M';
    } elseif ($number >= 1000) { // Thousand (1e3)
        $formatted = round($number / 1000, 1);
        return ($formatted == (int)$formatted ? (int)$formatted : $formatted) . 'K';
    }
    
    return (int)$number;
}