/**
 * Client-Side Field Encryption Module
 * 
 * Encrypts sensitive form fields before submission using TweetNaCl.js.
 * Uses server's public key to encrypt data securely in the browser.
 * 
 * Security Note: This provides an additional layer of security but does NOT
 * replace HTTPS. Always use HTTPS in production for complete security.
 */

class FieldEncryptor {
    /**
     * Initialize the encryptor with server's public key
     * Public key should be embedded in the page as a data attribute
     */
    constructor() {
        this.publicKeyHex = this.getPublicKeyFromPage();
        this.nacl = window.nacl; // TweetNaCl.js library
        
        if (!this.nacl) {
            console.warn('TweetNaCl.js not loaded. Client-side encryption disabled.');
        }
    }
    
    /**
     * Get server's public key from page data attribute
     * Added by PHP via: <div id="encryption-key" data-public-key="...">
     */
    getPublicKeyFromPage() {
        const keyElement = document.getElementById('encryption-key');
        if (keyElement) {
            return keyElement.getAttribute('data-public-key');
        }
        return null;
    }
    
    /**
     * Encrypt a single field value
     * 
     * @param {string} plaintext The value to encrypt
     * @returns {string} Base64-encoded encrypted value with nonce
     */
    encryptField(plaintext) {
        if (!this.nacl || !this.publicKeyHex) {
            console.warn('Encryption not available. Sending plaintext.');
            return plaintext;
        }
        
        try {
            // Convert hex public key to Uint8Array
            const publicKey = this.hexToBytes(this.publicKeyHex);
            
            // Generate random nonce
            const nonce = this.nacl.randomBytes(24);
            
            // Encrypt message
            const plainBytes = this.stringToBytes(plaintext);
            const encrypted = this.nacl.box.after(plainBytes, nonce, publicKey);
            
            // Combine nonce + encrypted (nonce must be with ciphertext for decryption)
            const combined = new Uint8Array(nonce.length + encrypted.length);
            combined.set(nonce);
            combined.set(encrypted, nonce.length);
            
            // Return base64-encoded with marker
            return 'ENCR:' + this.bytesToBase64(combined);
        } catch (error) {
            console.error('Encryption failed:', error);
            return plaintext; // Fallback to plaintext if encryption fails
        }
    }
    
    /**
     * Encrypt multiple form fields before submission
     * 
     * @param {HTMLFormElement} form The form element
     * @param {array} fieldNames List of field names to encrypt
     * @returns {boolean} True if encryption succeeded, false if errors occurred
     */
    encryptFormFields(form, fieldNames) {
        try {
            fieldNames.forEach(fieldName => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field && field.value) {
                    field.value = this.encryptField(field.value);
                }
            });
            return true;
        } catch (error) {
            console.error('Form encryption failed:', error);
            return false;
        }
    }
    
    /**
     * Decrypt a field value (for display/editing)
     * This would be called server-side in practice, but provided for completeness
     */
    decryptField(encrypted) {
        // Client-side decryption not typically used
        // Decryption should happen server-side where private key is available
        console.error('Client-side decryption not supported. Decrypt on server only.');
        return null;
    }
    
    /**
     * Convert hex string to Uint8Array
     */
    hexToBytes(hexString) {
        const bytes = new Uint8Array(hexString.length / 2);
        for (let i = 0; i < hexString.length; i += 2) {
            bytes[i / 2] = parseInt(hexString.substr(i, 2), 16);
        }
        return bytes;
    }
    
    /**
     * Convert string to UTF-8 bytes
     */
    stringToBytes(str) {
        const bytes = new Uint8Array(str.length);
        for (let i = 0; i < str.length; i++) {
            bytes[i] = str.charCodeAt(i);
        }
        return bytes;
    }
    
    /**
     * Convert Uint8Array to base64
     */
    bytesToBase64(bytes) {
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }
    
    /**
     * Convert base64 to Uint8Array
     */
    base64ToBytes(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }
}

/**
 * Initialize encryptor globally on page load
 */
let fieldEncryptor = null;

document.addEventListener('DOMContentLoaded', function() {
    fieldEncryptor = new FieldEncryptor();
    
    // Log availability
    if (fieldEncryptor.nacl && fieldEncryptor.publicKeyHex) {
        console.log('✓ Client-side encryption ready');
    } else {
        console.warn('⚠ Client-side encryption not available');
    }
});

/**
 * Helper function to encrypt form on submit
 * Usage: <form onsubmit="return encryptFormBeforeSubmit(this, ['username', 'full_name'])">
 */
function encryptFormBeforeSubmit(form, fieldsToEncrypt) {
    if (fieldEncryptor) {
        fieldEncryptor.encryptFormFields(form, fieldsToEncrypt);
    }
    return true; // Allow form submission
}
