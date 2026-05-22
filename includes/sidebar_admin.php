<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function isActive($pages) {
    global $currentPage;
    return in_array($currentPage, (array)$pages) ? 'active' : '';
}
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <span class="sidebar-brand-icon">👟</span>
        <span class="sidebar-brand-name">ShStorage</span>
    </div>

    <div class="sidebar-role-badge">Admin Panel</div>

    <nav class="sidebar-nav">

        <div class="nav-section-label">Main</div>

        <a href="../admin/dashboard.php" class="nav-item <?= isActive('dashboard.php') ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>

        <div class="nav-section-label">Inventory</div>

        <a href="../admin/inventory.php" class="nav-item <?= isActive(['inventory.php','inventory_edit.php','shoe_edit.php']) ?>">
            <span class="nav-icon">📦</span>
            <span class="nav-label">Inventory</span>
        </a>

        <a href="../admin/inventory_add.php" class="nav-item <?= isActive('inventory_add.php') ?>">
            <span class="nav-icon">➕</span>
            <span class="nav-label">Add Shoe</span>
        </a>

        <a href="../admin/brands.php" class="nav-item <?= isActive('brands.php') ?>">
            <span class="nav-icon">🏷️</span>
            <span class="nav-label">Brands</span>
        </a>

        <div class="nav-section-label">Orders</div>

        <a href="../admin/orders.php" class="nav-item <?= isActive(['orders.php','orders_approve.php']) ?>">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">Stock Requests</span>
            <?php
            // Show pending badge
            global $conn;
            if (isset($conn)) {
                $pending = $conn->query("SELECT COUNT(*) as cnt FROM stock_requests WHERE status='pending'")->fetch_assoc()['cnt'];
                if ($pending > 0): ?>
                    <span class="nav-badge"><?= $pending ?></span>
            <?php endif; } ?>
        </a>

        <div class="nav-section-label">Management</div>

        <a href="../admin/branches.php" class="nav-item <?= isActive('branches.php') ?>">
            <span class="nav-icon">🏪</span>
            <span class="nav-label">Branches</span>
        </a>
        <a href="../admin/branch_view.php" class="nav-item <?= isActive('branch_view.php') ?>">
            <span class="nav-icon">👁️</span>
            <span class="nav-label">Branch View</span>
        </a>

    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-username"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <span class="sidebar-userrole">Administrator</span>
            </div>
        </div>
        <a href="../logout.php" class="nav-item nav-logout">
            <span class="nav-icon">🚪</span>
            <span class="nav-label">Logout</span>
        </a>
    </div>

</aside>

<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">☰</button>