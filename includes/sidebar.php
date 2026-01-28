<!-- Sidebar Navigation Component -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>SIAMS</h1>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar">
            <?php 
            $initials = isset($_SESSION['username']) 
                ? strtoupper(substr($_SESSION['username'], 0, 1)) 
                : 'U';
            echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); 
            ?>
        </div>
        <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    
    <nav class="sidebar-menu">
        <ul>
            <li>
                <a href="<?php echo BASE_URL; ?>/pages/admin_dashboard.php" class="menu-icon-dashboard <?php echo (basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' || basename($_SERVER['PHP_SELF']) === 'manager_dashboard.php' || basename($_SERVER['PHP_SELF']) === 'staff_dashboard.php') ? 'active' : ''; ?>">
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/index.php" class="menu-icon-inventory <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>">
                    Inventory
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/pages/add_product.php" class="menu-icon-add <?php echo (basename($_SERVER['PHP_SELF']) === 'add_product.php') ? 'active' : ''; ?>">
                    Add Item
                </a>
            </li>
            
            <!-- User Management (Admin Only) -->
            <?php if ($_SESSION['role'] === 'Admin'): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/pages/users.php" class="menu-icon-users <?php echo (basename($_SERVER['PHP_SELF']) === 'users.php' || basename($_SERVER['PHP_SELF']) === 'create_user.php' || basename($_SERVER['PHP_SELF']) === 'edit_user.php' || basename($_SERVER['PHP_SELF']) === 'delete_user.php') ? 'active' : ''; ?>">
                    👥 Users
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Stock Requests (Staff) -->
            <?php if ($_SESSION['role'] === 'Staff'): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/pages/submit_request.php" class="menu-icon-request <?php echo (basename($_SERVER['PHP_SELF']) === 'submit_request.php') ? 'active' : ''; ?>">
                    📦 Submit Request
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Request Approval (Manager) -->
            <?php if ($_SESSION['role'] === 'Manager'): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/pages/approve_request.php" class="menu-icon-approve <?php echo (basename($_SERVER['PHP_SELF']) === 'approve_request.php') ? 'active' : ''; ?>">
                    ✅ Approve Requests
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Reports (Manager & Admin) -->
            <?php if (in_array($_SESSION['role'], ['Manager', 'Admin'])): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/pages/reports.php" class="menu-icon-reports <?php echo (basename($_SERVER['PHP_SELF']) === 'reports.php') ? 'active' : ''; ?>">
                    📊 Reports
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="<?php echo BASE_URL; ?>/pages/about.php" class="menu-icon-about <?php echo (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : ''; ?>">
                    About
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a>
    </div>
</aside>
