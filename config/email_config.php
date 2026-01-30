<?php
/**
 * Email Configuration
 * 
 * SECURITY: Contains no sensitive credentials - those should come from environment variables
 * or system-level mail configuration. This file only controls behavior/features.
 */

// Email feature flags
define('ENABLE_EMAIL_NOTIFICATIONS', true);  // Set to false to disable all emails
define('ENABLE_APPROVAL_EMAILS', true);      // Notify staff when request approved
define('ENABLE_REJECTION_EMAILS', true);     // Notify staff when request rejected
define('ENABLE_LOW_STOCK_ALERTS', true);     // Notify managers of low stock

// Email templates/formatting
define('EMAIL_SENDER_NAME', 'SIAMS System');
define('EMAIL_SENDER_ADDRESS', 'siams-noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// Email retry settings
define('EMAIL_RETRY_ON_FAILURE', false);     // Don't retry failed emails (keep system fast)
define('EMAIL_LOG_FAILURES', true);          // Log email failures to error log

// Batch notification settings
define('BATCH_LOW_STOCK_CHECKS', true);      // Only check low stock once per request
