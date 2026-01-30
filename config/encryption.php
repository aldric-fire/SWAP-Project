<?php
/**
 * Field-Level Encryption Module
 * 
 * Provides symmetric encryption (AES-256-GCM) for sensitive database fields.
 * Uses OpenSSL for encryption at rest.
 * 
 * SECURITY NOTES:
 * - Keep ENCRYPTION_KEY in .env or environment variables, NOT in source code
 * - Regenerate keys periodically and implement key rotation
 * - Encrypted data includes IV and HMAC for integrity verification
 */

/**
 * Get encryption key from environment or use fallback for development
 * 
 * Production: Set via environment variable
 *   export ENCRYPTION_KEY="your-64-character-hex-string-here"
 */
function get_encryption_key(): string
{
    $key = getenv('ENCRYPTION_KEY');
    
    if (!$key) {
        // Development fallback - CHANGE THIS IN PRODUCTION
        $key = 'dev_key_only_change_in_production_environment_variable_needed_here';
        // Hash to 32 bytes (256 bits) for AES-256
        $key = hash('sha256', $key, true);
        $key = bin2hex($key); // Convert to hex for consistency
    }
    
    return $key;
}

/**
 * Encrypt a string value using AES-256-GCM
 * 
 * @param string $plaintext The value to encrypt
 * @param string|null $key Optional encryption key (uses default if null)
 * @return string Base64-encoded encrypted data with IV and tag
 * 
 * Format: base64(IV || ENCRYPTED_DATA || AUTH_TAG)
 */
function encrypt_field(string $plaintext, ?string $key = null): string
{
    if (empty($plaintext)) {
        return $plaintext; // Return empty strings as-is
    }
    
    $key = $key ?? get_encryption_key();
    $cipher = 'aes-256-gcm';
    
    // Generate random IV (16 bytes for GCM)
    $iv = openssl_random_pseudo_bytes(16);
    
    // Encrypt data
    $encrypted = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        throw new Exception('Encryption failed: ' . openssl_error_string());
    }
    
    // Get authentication tag (16 bytes)
    $tag = '';
    openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    // Combine: IV || ENCRYPTED_DATA || AUTH_TAG
    $combined = $iv . $encrypted . $tag;
    
    // Return base64-encoded for safe database storage
    return 'ENC:' . base64_encode($combined);
}

/**
 * Decrypt a field that was encrypted with encrypt_field()
 * 
 * @param string $encrypted Base64-encoded encrypted data
 * @param string|null $key Optional decryption key (uses default if null)
 * @return string Decrypted plaintext
 * 
 * @throws Exception If decryption fails or tag verification fails
 */
function decrypt_field(string $encrypted, ?string $key = null): string
{
    if (empty($encrypted)) {
        return $encrypted; // Return empty strings as-is
    }
    
    // Check for encryption marker
    if (strpos($encrypted, 'ENC:') !== 0) {
        return $encrypted; // Not encrypted, return as-is
    }
    
    try {
        $key = $key ?? get_encryption_key();
        $cipher = 'aes-256-gcm';
        
        // Remove 'ENC:' prefix and decode from base64
        $combined = base64_decode(substr($encrypted, 4), true);
        
        if ($combined === false) {
            throw new Exception('Invalid base64 encoding');
        }
        
        // Extract IV, encrypted data, and tag
        $iv = substr($combined, 0, 16);
        $tag = substr($combined, -16);
        $ciphertext = substr($combined, 16, -16);
        
        // Decrypt and verify tag
        $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($plaintext === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }
        
        return $plaintext;
    } catch (Exception $e) {
        // Log error but don't expose details
        error_log('Decryption error: ' . $e->getMessage());
        throw new Exception('Could not decrypt field');
    }
}

/**
 * Check if a field is encrypted
 * 
 * @param string $value The value to check
 * @return bool True if field starts with 'ENC:' prefix
 */
function is_encrypted(string $value): bool
{
    return strpos($value, 'ENC:') === 0;
}

/**
 * Encrypt an array of fields
 * Useful for batch encryption
 * 
 * @param array $data Key-value pairs
 * @param array $fieldsToEncrypt List of keys to encrypt
 * @return array Data with specified fields encrypted
 */
function encrypt_fields(array $data, array $fieldsToEncrypt): array
{
    foreach ($fieldsToEncrypt as $field) {
        if (isset($data[$field])) {
            $data[$field] = encrypt_field($data[$field]);
        }
    }
    return $data;
}

/**
 * Decrypt an array of fields
 * Useful for batch decryption
 * 
 * @param array $data Key-value pairs
 * @param array $fieldsToDecrypt List of keys to decrypt
 * @return array Data with specified fields decrypted
 */
function decrypt_fields(array $data, array $fieldsToDecrypt): array
{
    foreach ($fieldsToDecrypt as $field) {
        if (isset($data[$field])) {
            $data[$field] = decrypt_field($data[$field]);
        }
    }
    return $data;
}

/**
 * List of fields that should be encrypted
 * Maps table names to field names that require encryption
 */
const ENCRYPTED_FIELDS = [
    'users' => [
        'full_name' => true,  // Employee/user full name (PII)
    ],
    'suppliers' => [
        'supplier_name' => true,      // Supplier company name
        'contact_person' => true,     // Contact person (PII)
        'email' => true,              // Email address (PII)
        'phone' => true,              // Phone number (PII)
        'address' => true,            // Physical address (PII)
    ],
    'inventory_items' => [
        'item_name' => true,          // Item name/description
    ],
    'stock_requests' => [
        // Generally not needed, but can add if items contain sensitive info
    ],
    'audit_logs' => [
        'description' => true,        // Audit descriptions may contain sensitive info
    ],
];

/**
 * Get encrypted fields for a specific table
 * 
 * @param string $table Table name
 * @return array List of encrypted field names
 */
function get_encrypted_fields_for_table(string $table): array
{
    return array_keys(ENCRYPTED_FIELDS[$table] ?? []);
}

?>
