<!-- Navigation Bar Component -->
<nav class="navbar">
    <div class="container">
        <h1>SIAMS</h1>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/pages/add_product.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'add_product.php') ? 'active' : ''; ?>">Add Item</a></li>
            <li><a href="<?php echo BASE_URL; ?>/pages/about.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : ''; ?>">About</a></li>
        </ul>
        <?php if (isset($_SESSION['username']) && isset($_SESSION['role'])): ?>
        <div class="user-info">
            <span class="role-badge"><?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" style="color: rgba(255,255,255,0.9);">Logout</a>
        </div>
        <?php endif; ?>
    </div>
</nav>

