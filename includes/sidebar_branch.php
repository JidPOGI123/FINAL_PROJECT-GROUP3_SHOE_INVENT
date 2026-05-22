<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function isBranchActive($pages) {
    global $currentPage;
    return in_array($currentPage, (array)$pages) ? 'active' : '';
}
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <span class="sidebar-brand-icon">👟</span>
        <span class="sidebar-brand-name">ShStorage</span>
    </div>

    <div class="sidebar-role-badge branch-badge">Branch Portal</div>

    <nav class="sidebar-nav">

        <div class="nav-section-label">Overview</div>

        <a href="../branch/dashboard.php" class="nav-item <?= isBranchActive('dashboard.php') ?>">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">Dashboard</span>
        </a>

        <div class="nav-section-label">Stocks</div>

        <a href="../branch/catalog.php" class="nav-item <?= isBranchActive('catalog.php') ?>">
            <span class="nav-icon">👟</span>
            <span class="nav-label">Browse Shoes</span>
        </a>

        <a href="../branch/request.php" class="nav-item <?= isBranchActive('request.php') ?>">
            <span class="nav-icon">📋</span>
            <span class="nav-label">Request Stock</span>
        </a>

        <a href="../branch/my_stocks.php" class="nav-item <?= isBranchActive('my_stocks.php') ?>">
            <span class="nav-icon">📦</span>
            <span class="nav-label">My Stocks</span>
        </a>

        <div class="nav-section-label">History</div>

        <a href="../branch/request_history.php" class="nav-item <?= isBranchActive('request_history.php') ?>">
            <span class="nav-icon">🕐</span>
            <span class="nav-label">My Requests</span>
            <?php
            // Show pending badge for branch
            global $conn;
            if (isset($conn) && isset($_SESSION['user_id'])) {
                $uid = $_SESSION['user_id'];
                $pending = $conn->prepare("SELECT COUNT(*) as cnt FROM stock_requests WHERE branch_id=? AND status='pending'");
                $pending->bind_param("i", $uid);
                $pending->execute();
                $cnt = $pending->get_result()->fetch_assoc()['cnt'];
                if ($cnt > 0): ?>
                    <span class="nav-badge"><?= $cnt ?></span>
            <?php endif; } ?>
        </a>

    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar branch-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'B', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-username"><?= htmlspecialchars($_SESSION['branch_name'] ?? $_SESSION['username'] ?? 'Branch') ?></span>
                <span class="sidebar-userrole">Branch Account</span>
            </div>
        </div>
        <a href="../logout.php" class="nav-item nav-logout">
            <span class="nav-icon">🚪</span>
            <span class="nav-label">Logout</span>
        </a>
    </div>

</aside>

<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">☰</button>