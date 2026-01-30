<?php
/**
 * Create Sample Data Script
 * Navigate to: http://localhost/SWAP-Project/create_sample_data.php
 * Run AFTER create_users.php
 */

require_once __DIR__ . '/config/db.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Sample Data Generator</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 40px; background: #f3f4f6; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
    h1 { color: #1f2937; }
    h2 { color: #059669; border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-top: 30px; }
    .success { color: #059669; }
    .info { color: #3b82f6; }
    .error { color: #dc2626; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; }
    th { background: #f3f4f6; font-weight: 600; }
    .btn { display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    .btn:hover { background: #059669; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Sample Data Generator</h1>";

try {
    // ========================================
    // 1. CREATE SUPPLIERS
    // ========================================
    echo "<h2>📦 Suppliers</h2>";
    
    $suppliers = [
        ['name' => 'TechSupply Co.', 'contact' => 'Robert Chen', 'email' => 'robert@techsupply.com', 'phone' => '+1-555-0101', 'address' => '123 Tech Street, Silicon Valley, CA 94025'],
        ['name' => 'Office Essentials Ltd.', 'contact' => 'Sarah Johnson', 'email' => 'sarah@officeessentials.com', 'phone' => '+1-555-0102', 'address' => '456 Business Ave, New York, NY 10001'],
        ['name' => 'Hardware Plus', 'contact' => 'Michael Brown', 'email' => 'michael@hardwareplus.com', 'phone' => '+1-555-0103', 'address' => '789 Industrial Blvd, Chicago, IL 60601'],
        ['name' => 'Premium Goods Inc.', 'contact' => 'Emily Davis', 'email' => 'emily@premiumgoods.com', 'phone' => '+1-555-0104', 'address' => '321 Quality Road, Austin, TX 78701'],
        ['name' => 'FastShip Distributors', 'contact' => 'David Wilson', 'email' => 'david@fastship.com', 'phone' => '+1-555-0105', 'address' => '654 Logistics Lane, Seattle, WA 98101'],
    ];
    
    foreach ($suppliers as $supplier) {
        $stmt = $pdo->prepare('SELECT supplier_id FROM suppliers WHERE supplier_name = ?');
        $stmt->execute([$supplier['name']]);
        
        if ($stmt->rowCount() === 0) {
            $insertStmt = $pdo->prepare(
                'INSERT INTO suppliers (supplier_name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)'
            );
            $insertStmt->execute([
                $supplier['name'],
                $supplier['contact'],
                $supplier['email'],
                $supplier['phone'],
                $supplier['address']
            ]);
            echo "<span class='success'>✓ Created supplier: <strong>{$supplier['name']}</strong></span><br>";
        } else {
            echo "<span class='info'>ℹ Supplier already exists: <strong>{$supplier['name']}</strong></span><br>";
        }
    }
    
    // ========================================
    // 2. CREATE INVENTORY ITEMS
    // ========================================
    echo "<h2>📋 Inventory Items</h2>";
    
    $inventoryItems = [
        // Electronics
        ['name' => 'Laptop Dell XPS 15', 'category' => 'Electronics', 'quantity' => 25, 'min' => 10, 'supplier_id' => 1, 'status' => 'Available'],
        ['name' => 'Wireless Mouse Logitech', 'category' => 'Electronics', 'quantity' => 150, 'min' => 50, 'supplier_id' => 1, 'status' => 'Available'],
        ['name' => 'USB-C Hub Multiport', 'category' => 'Electronics', 'quantity' => 45, 'min' => 20, 'supplier_id' => 1, 'status' => 'Available'],
        ['name' => 'Monitor 27" 4K', 'category' => 'Electronics', 'quantity' => 8, 'min' => 15, 'supplier_id' => 1, 'status' => 'Low Stock'],
        ['name' => 'Keyboard Mechanical RGB', 'category' => 'Electronics', 'quantity' => 62, 'min' => 25, 'supplier_id' => 1, 'status' => 'Available'],
        
        // Office Supplies
        ['name' => 'Printer Paper A4 (500 sheets)', 'category' => 'Office Supplies', 'quantity' => 120, 'min' => 50, 'supplier_id' => 2, 'status' => 'Available'],
        ['name' => 'Blue Pens (Box of 50)', 'category' => 'Office Supplies', 'quantity' => 35, 'min' => 20, 'supplier_id' => 2, 'status' => 'Available'],
        ['name' => 'Sticky Notes Assorted', 'category' => 'Office Supplies', 'quantity' => 88, 'min' => 40, 'supplier_id' => 2, 'status' => 'Available'],
        ['name' => 'File Folders Letter Size', 'category' => 'Office Supplies', 'quantity' => 15, 'min' => 30, 'supplier_id' => 2, 'status' => 'Low Stock'],
        ['name' => 'Whiteboard Markers Set', 'category' => 'Office Supplies', 'quantity' => 42, 'min' => 15, 'supplier_id' => 2, 'status' => 'Available'],
        
        // Furniture
        ['name' => 'Office Chair Ergonomic', 'category' => 'Furniture', 'quantity' => 18, 'min' => 10, 'supplier_id' => 3, 'status' => 'Available'],
        ['name' => 'Standing Desk Adjustable', 'category' => 'Furniture', 'quantity' => 5, 'min' => 8, 'supplier_id' => 3, 'status' => 'Low Stock'],
        ['name' => 'Filing Cabinet 4-Drawer', 'category' => 'Furniture', 'quantity' => 12, 'min' => 5, 'supplier_id' => 3, 'status' => 'Available'],
        ['name' => 'Conference Table 8-Seater', 'category' => 'Furniture', 'quantity' => 3, 'min' => 2, 'supplier_id' => 3, 'status' => 'Available'],
        
        // Tools & Equipment
        ['name' => 'Screwdriver Set Professional', 'category' => 'Tools', 'quantity' => 28, 'min' => 10, 'supplier_id' => 4, 'status' => 'Available'],
        ['name' => 'Cordless Drill 20V', 'category' => 'Tools', 'quantity' => 14, 'min' => 8, 'supplier_id' => 4, 'status' => 'Available'],
        ['name' => 'Measuring Tape 25ft', 'category' => 'Tools', 'quantity' => 35, 'min' => 20, 'supplier_id' => 4, 'status' => 'Available'],
        ['name' => 'Safety Goggles', 'category' => 'Safety Equipment', 'quantity' => 75, 'min' => 30, 'supplier_id' => 5, 'status' => 'Available'],
        ['name' => 'Hard Hat ANSI Approved', 'category' => 'Safety Equipment', 'quantity' => 42, 'min' => 25, 'supplier_id' => 5, 'status' => 'Available'],
        ['name' => 'First Aid Kit Complete', 'category' => 'Safety Equipment', 'quantity' => 18, 'min' => 10, 'supplier_id' => 5, 'status' => 'Available'],
    ];
    
    foreach ($inventoryItems as $item) {
        $stmt = $pdo->prepare('SELECT item_id FROM inventory_items WHERE item_name = ?');
        $stmt->execute([$item['name']]);
        
        if ($stmt->rowCount() === 0) {
            $insertStmt = $pdo->prepare(
                'INSERT INTO inventory_items (item_name, category, quantity, min_threshold, supplier_id, last_updated_by, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $insertStmt->execute([
                $item['name'],
                $item['category'],
                $item['quantity'],
                $item['min'],
                $item['supplier_id'],
                1, // Admin user
                $item['status']
            ]);
            echo "<span class='success'>✓ Created item: <strong>{$item['name']}</strong> (Qty: {$item['quantity']})</span><br>";
        } else {
            echo "<span class='info'>ℹ Item already exists: <strong>{$item['name']}</strong></span><br>";
        }
    }
    
    // ========================================
    // 3. CREATE STOCK REQUESTS
    // ========================================
    echo "<h2>📝 Stock Requests</h2>";
    
    $stockRequests = [
        // Pending requests
        ['item_id' => 4, 'requested_by' => 3, 'quantity' => 10, 'priority' => 185, 'status' => 'Pending', 'manager_id' => null, 'note' => 'Monitor (Low stock, high priority)'],
        ['item_id' => 9, 'requested_by' => 3, 'quantity' => 20, 'priority' => 125, 'status' => 'Pending', 'manager_id' => null, 'note' => 'File Folders (Low stock)'],
        ['item_id' => 12, 'requested_by' => 3, 'quantity' => 5, 'priority' => 95, 'status' => 'Pending', 'manager_id' => null, 'note' => 'Standing Desk (Low stock)'],
        
        // Approved requests
        ['item_id' => 1, 'requested_by' => 3, 'quantity' => 5, 'priority' => 75, 'status' => 'Approved', 'manager_id' => 2, 'note' => 'Laptops'],
        ['item_id' => 6, 'requested_by' => 3, 'quantity' => 50, 'priority' => 60, 'status' => 'Approved', 'manager_id' => 2, 'note' => 'Printer Paper'],
        ['item_id' => 15, 'requested_by' => 3, 'quantity' => 10, 'priority' => 45, 'status' => 'Approved', 'manager_id' => 2, 'note' => 'Screwdriver Sets'],
        
        // Rejected requests
        ['item_id' => 14, 'requested_by' => 3, 'quantity' => 2, 'priority' => 30, 'status' => 'Rejected', 'manager_id' => 2, 'note' => 'Conference Table (not critical)'],
        ['item_id' => 2, 'requested_by' => 3, 'quantity' => 100, 'priority' => 40, 'status' => 'Rejected', 'manager_id' => 2, 'note' => 'Wireless Mouse (sufficient stock)'],
    ];
    
    // Clear existing requests to avoid duplicates
    $pdo->exec('DELETE FROM stock_requests');
    
    foreach ($stockRequests as $request) {
        $insertStmt = $pdo->prepare(
            'INSERT INTO stock_requests (item_id, requested_by, quantity, priority_score, status, manager_id) 
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insertStmt->execute([
            $request['item_id'],
            $request['requested_by'],
            $request['quantity'],
            $request['priority'],
            $request['status'],
            $request['manager_id']
        ]);
        echo "<span class='success'>✓ Created request: <strong>{$request['note']}</strong> - {$request['status']}</span><br>";
    }
    
    // ========================================
    // 4. CREATE AUDIT LOGS
    // ========================================
    echo "<h2>📊 Audit Logs</h2>";
    
    $auditLogs = [
        // Login activities
        ['user_id' => 1, 'action' => 'LOGIN', 'table' => 'users', 'target_id' => 1, 'desc' => 'Admin logged in successfully'],
        ['user_id' => 2, 'action' => 'LOGIN', 'table' => 'users', 'target_id' => 2, 'desc' => 'Manager logged in successfully'],
        ['user_id' => 3, 'action' => 'LOGIN', 'table' => 'users', 'target_id' => 3, 'desc' => 'Staff logged in successfully'],
        
        // Inventory operations
        ['user_id' => 1, 'action' => 'CREATE', 'table' => 'inventory_items', 'target_id' => 1, 'desc' => 'Created new inventory item: Laptop Dell XPS 15'],
        ['user_id' => 1, 'action' => 'CREATE', 'table' => 'inventory_items', 'target_id' => 2, 'desc' => 'Created new inventory item: Wireless Mouse Logitech'],
        ['user_id' => 1, 'action' => 'UPDATE', 'table' => 'inventory_items', 'target_id' => 4, 'desc' => 'Updated quantity for Monitor 27" 4K'],
        
        // Request operations
        ['user_id' => 3, 'action' => 'CREATE', 'table' => 'stock_requests', 'target_id' => 1, 'desc' => 'Submitted stock request for item ID 4 (qty: 10)'],
        ['user_id' => 3, 'action' => 'CREATE', 'table' => 'stock_requests', 'target_id' => 4, 'desc' => 'Submitted stock request for item ID 1 (qty: 5)'],
        ['user_id' => 2, 'action' => 'APPROVE', 'table' => 'stock_requests', 'target_id' => 4, 'desc' => 'Manager approved stock request ID 4'],
        ['user_id' => 2, 'action' => 'REJECT', 'table' => 'stock_requests', 'target_id' => 7, 'desc' => 'Manager rejected stock request ID 7'],
        
        // User management
        ['user_id' => 1, 'action' => 'CREATE', 'table' => 'users', 'target_id' => 2, 'desc' => 'Admin created new manager account'],
        ['user_id' => 1, 'action' => 'CREATE', 'table' => 'users', 'target_id' => 3, 'desc' => 'Admin created new staff account'],
        ['user_id' => 1, 'action' => 'UPDATE', 'table' => 'users', 'target_id' => 3, 'desc' => 'Admin updated staff user permissions'],
    ];
    
    // Clear existing audit logs to avoid duplicates
    $pdo->exec('DELETE FROM audit_logs');
    
    foreach ($auditLogs as $log) {
        $insertStmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action_type, target_table, target_id, description) 
             VALUES (?, ?, ?, ?, ?)'
        );
        $insertStmt->execute([
            $log['user_id'],
            $log['action'],
            $log['table'],
            $log['target_id'],
            $log['desc']
        ]);
        echo "<span class='success'>✓ Created log: <strong>{$log['desc']}</strong></span><br>";
    }
    
    // ========================================
    // SUMMARY
    // ========================================
    echo "<h2>✅ Setup Complete!</h2>";
    
    // Get counts
    $supplierCount = $pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
    $itemCount = $pdo->query('SELECT COUNT(*) FROM inventory_items')->fetchColumn();
    $requestCount = $pdo->query('SELECT COUNT(*) FROM stock_requests')->fetchColumn();
    $auditCount = $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    
    echo "<table>";
    echo "<tr><th>Data Type</th><th>Count</th></tr>";
    echo "<tr><td>Suppliers</td><td>{$supplierCount}</td></tr>";
    echo "<tr><td>Inventory Items</td><td>{$itemCount}</td></tr>";
    echo "<tr><td>Stock Requests</td><td>{$requestCount}</td></tr>";
    echo "<tr><td>Audit Logs</td><td>{$auditCount}</td></tr>";
    echo "</table>";
    
    echo "<p><strong>🎉 All sample data has been created successfully!</strong></p>";
    echo "<p>You can now use the system with realistic demo data.</p>";
    echo "<a href='auth/login.php' class='btn'>→ Go to Login Page</a>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>
