<?php
/**
 * Rate Limiting for Login Attempts
 * 
 * Mitigates OWASP A07: Identification and Authentication Failures
 * Prevents brute force attacks on authentication endpoints
 */

/**
 * Check if IP address is rate limited
 * 
 * @param PDO $pdo Database connection
 * @param string $ipAddress IP address to check
 * @param int $maxAttempts Maximum allowed attempts (default: 5)
 * @param int $windowSeconds Time window in seconds (default: 300 = 5 minutes)
 * @return bool True if rate limited (too many attempts)
 */
function is_rate_limited(PDO $pdo, string $ipAddress, int $maxAttempts = 5, int $windowSeconds = 300): bool
{
    // Clean up old attempts (older than window)
    $stmt = $pdo->prepare(
        'DELETE FROM login_attempts 
         WHERE attempt_time < DATE_SUB(NOW(), INTERVAL :window SECOND)'
    );
    $stmt->execute([':window' => $windowSeconds]);
    
    // Count recent attempts from this IP
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) 
         FROM login_attempts 
         WHERE ip_address = :ip 
         AND attempt_time >= DATE_SUB(NOW(), INTERVAL :window SECOND)'
    );
    $stmt->execute([
        ':ip' => $ipAddress,
        ':window' => $windowSeconds
    ]);
    
    $attemptCount = (int)$stmt->fetchColumn();
    
    return $attemptCount >= $maxAttempts;
}

/**
 * Log a failed login attempt
 * 
 * @param PDO $pdo Database connection
 * @param string $username Attempted username
 * @param string $ipAddress IP address of attempt
 * @return void
 */
function log_failed_attempt(PDO $pdo, string $username, string $ipAddress): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (username, ip_address, attempt_time)
         VALUES (:username, :ip, NOW())'
    );
    
    $stmt->execute([
        ':username' => $username,
        ':ip' => $ipAddress
    ]);
}

/**
 * Clear login attempts for an IP after successful login
 * 
 * @param PDO $pdo Database connection
 * @param string $ipAddress IP address to clear
 * @return void
 */
function clear_login_attempts(PDO $pdo, string $ipAddress): void
{
    $stmt = $pdo->prepare(
        'DELETE FROM login_attempts WHERE ip_address = :ip'
    );
    
    $stmt->execute([':ip' => $ipAddress]);
}

/**
 * Get remaining lockout time in seconds
 * 
 * @param PDO $pdo Database connection
 * @param string $ipAddress IP address to check
 * @param int $windowSeconds Time window in seconds
 * @return int Remaining lockout time in seconds (0 if not locked)
 */
function get_lockout_time(PDO $pdo, string $ipAddress, int $windowSeconds = 300): int
{
    $stmt = $pdo->prepare(
        'SELECT MAX(attempt_time) as last_attempt
         FROM login_attempts 
         WHERE ip_address = :ip'
    );
    $stmt->execute([':ip' => $ipAddress]);
    
    $result = $stmt->fetch();
    if (!$result || !$result['last_attempt']) {
        return 0;
    }
    
    $lastAttempt = strtotime($result['last_attempt']);
    $lockoutEnd = $lastAttempt + $windowSeconds;
    $remaining = $lockoutEnd - time();
    
    return max(0, $remaining);
}
