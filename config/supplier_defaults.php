<?php
/**
 * Supplier Lead Times Configuration
 * 
 * Defines default lead times (in days) for each supplier.
 * Used in priority calculation and delivery timeline estimation.
 * 
 * SECURITY: Read-only configuration, no user input.
 */

// Map supplier_id to lead_time_days
// Update these based on your actual supplier data
$SUPPLIER_LEAD_TIMES = [
    // Format: supplier_id => lead_time_in_days
    // Example: if supplier_id=1 has 7-day lead time, add: 1 => 7
];

/**
 * Get supplier lead time with safe fallback
 * 
 * @param int $supplier_id - Supplier ID from database
 * @return int Lead time in days (default 7 if not found)
 */
function get_supplier_lead_time($supplier_id) {
    global $SUPPLIER_LEAD_TIMES;
    
    // Validate input
    $supplier_id = (int)$supplier_id;
    
    // Return configured lead time or default to 7 days
    return isset($SUPPLIER_LEAD_TIMES[$supplier_id]) 
        ? (int)$SUPPLIER_LEAD_TIMES[$supplier_id]
        : 7; // Default lead time
}

/**
 * Calculate expected delivery date
 * Safe calculation that prevents future date attacks
 * 
 * @param string $approval_date - Date of approval (YYYY-MM-DD)
 * @param int $lead_time_days - Lead time in days
 * @return string Expected delivery date (YYYY-MM-DD)
 */
function calculate_delivery_date($approval_date, $lead_time_days) {
    // Validate date format
    $date = DateTime::createFromFormat('Y-m-d', $approval_date);
    if (!$date || $date->format('Y-m-d') !== $approval_date) {
        return null; // Invalid date
    }
    
    // Validate lead time is positive integer
    $lead_time_days = max(1, (int)$lead_time_days);
    
    // Calculate delivery date
    $date->modify("+{$lead_time_days} days");
    return $date->format('Y-m-d');
}
