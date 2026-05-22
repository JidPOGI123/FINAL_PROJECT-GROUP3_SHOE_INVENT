<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireBranch();

$branch_id = $_SESSION['user_id'];

// Total shoe models available
$totalShoes = $conn->query("SELECT COUNT(*) as cnt FROM shoes")->fetch_assoc()['cnt'];

// Total stock units across all shoes
$totalStock = $conn->query("SELECT SUM(quantity) as total FROM inventory")->fetch_assoc()['total'] ?? 0;

// Low stock items (quantity <= 5 but > 0)
$lowStock = $conn->query("SELECT COUNT(*) as cnt FROM inventory WHERE quantity <= 5 AND quantity > 0")->fetch_assoc()['cnt'];

// Out of stock items
$outOfStock = $conn->query("SELECT COUNT(*) as cnt FROM inventory WHERE quantity = 0")->fetch_assoc()['cnt'];

// This branch's pending requests
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM stock_requests WHERE branch_id = ? AND status = 'pending'");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$myPending = $stmt->get_result()->fetch_assoc()['cnt'];

// This branch's recent requests
$stmt2 = $conn->prepare("
    SELECT sr.id, sr.status, sr.notes, sr.created_at,
           COUNT(ri.id) as item_count,
           SUM(ri.quantity) as total_qty
    FROM stock_requests sr
    LEFT JOIN request_items ri ON ri.request_id = sr.id
    WHERE sr.branch_id = ?
    GROUP BY sr.id
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$stmt2->bind_param("i", $branch_id);
$stmt2->execute();
$recentRequests = $stmt2->get_result();

// Trending shoes
$trending = $conn->query("
    SELECT s.id, s.name, s.gender, s.price, s.image, b.name as brand_name,
           COALESCE(SUM(i.quantity), 0) as total_stock
    FROM shoes s
    JOIN brands b ON s.brand_id = b.id
    LEFT JOIN inventory i ON i.shoe_id = s.id
    WHERE s.is_trending = 1
    GROUP BY s.id
    ORDER BY total_stock DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – ShStorage Branch</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/branch.css">
</head>
<body>
<?php include '../includes/sidebar_branch.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Dashboard</h1>
            <span class="page-sub">Welcome, <?= htmlspecialchars($_SESSION['branch_name'] ?? $_SESSION['username']) ?></span>
        </div>
        <div class="topbar-right">
            <span class="date-badge"><?= date('F j, Y') ?></span>
        </div>
    </header>

    <?php if ($lowStock > 0): ?>
    <div class="low-stock-alert">
        ⚠️ <strong><?= $lowStock ?> item(s)</strong> are running low on stock.
        <a href="request.php" class="restock-btn">Request Restock</a>
    </div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="branch-stats">
        <div class="branch-stat-card blue">
            <span class="bsc-icon">👟</span>
            <span class="bsc-value"><?= $totalShoes ?></span>
            <span class="bsc-label">Shoe Models</span>
        </div>
        <div class="branch-stat-card green">
            <span class="bsc-icon">📦</span>
            <span class="bsc-value"><?= number_format($totalStock) ?></span>
            <span class="bsc-label">Total Stock Units</span>
        </div>
        <div class="branch-stat-card yellow">
            <span class="bsc-icon">⚠️</span>
            <span class="bsc-value"><?= $lowStock ?></span>
            <span class="bsc-label">Low Stock Items</span>
        </div>
        <div class="branch-stat-card red">
            <span class="bsc-icon">❌</span>
            <span class="bsc-value"><?= $outOfStock ?></span>
            <span class="bsc-label">Out of Stock</span>
        </div>
        <div class="branch-stat-card blue">
            <span class="bsc-icon">🕐</span>
            <span class="bsc-value"><?= $myPending ?></span>
            <span class="bsc-label">My Pending Requests</span>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Recent Requests -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">My Recent Requests</h2>
                <a href="request_history.php" class="card-link">View All</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Items</th>
                        <th>Total Qty</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $recentRequests->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= str_pad($req['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= $req['item_count'] ?> shoe(s)</td>
                        <td><?= number_format($req['total_qty'] ?? 0) ?> pairs</td>
                        <td><?= date('M d, Y', strtotime($req['created_at'])) ?></td>
                        <td><span class="badge badge-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($recentRequests->num_rows === 0): ?>
                    <tr><td colspan="5" class="empty-row">No requests yet. <a href="request.php">Make your first request.</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Quick Actions -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
            </div>
            <div style="display:flex; flex-direction:column; gap:12px; padding: 8px 0;">
                <a href="catalog.php" class="btn btn-primary">👟 Browse Shoe Catalog</a>
                <a href="request.php" class="btn btn-secondary">📋 Submit Stock Request</a>
                <a href="request_history.php" class="btn btn-ghost">🕐 View My Request History</a>
            </div>
        </section>
    </div>

    <!-- Trending Shoes -->
    <section class="card trending-section">
        <div class="card-header">
            <h2 class="card-title">🔥 Trending Shoes</h2>
            <a href="catalog.php" class="card-link">Browse All</a>
        </div>
        <div class="trending-grid-compact">
    <?php
    $trendingRows = $trending->fetch_all(MYSQLI_ASSOC);
    if (count($trendingRows) === 0): ?>
        <p class="empty-row">No trending shoes available at the moment.</p>
    <?php else:
        foreach ($trendingRows as $shoe): ?>
        <div class="trending-card-compact">
            <div class="tc-img">
                <?php if ($shoe['image']): ?>
                    <img src="../assets/images/<?= htmlspecialchars($shoe['image']) ?>" alt="<?= htmlspecialchars($shoe['name']) ?>">
                <?php else: ?>
                    <div class="img-placeholder">👟</div>
                <?php endif; ?>
                <span class="gender-tag"><?= ucfirst($shoe['gender']) ?></span>
            </div>
            <div class="tc-info">
                <span class="tc-brand"><?= htmlspecialchars($shoe['brand_name']) ?></span>
                <span class="tc-name"><?= htmlspecialchars($shoe['name']) ?></span>
                <span class="tc-stock"><?= number_format($shoe['total_stock']) ?> units</span>
                <span class="tc-price">₱<?= number_format($shoe['price'], 2) ?></span>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
    </section>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>