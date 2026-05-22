<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

// Total shoes
$totalShoes = $conn->query("SELECT COUNT(*) as cnt FROM shoes")->fetch_assoc()['cnt'];

// Total stock
$totalStock = $conn->query("SELECT SUM(quantity) as total FROM inventory")->fetch_assoc()['total'] ?? 0;

// Low stock items (quantity <= 5)
$lowStock = $conn->query("SELECT COUNT(*) as cnt FROM inventory WHERE quantity <= 5 AND quantity > 0")->fetch_assoc()['cnt'];

// Out of stock
$outOfStock = $conn->query("SELECT COUNT(*) as cnt FROM inventory WHERE quantity = 0")->fetch_assoc()['cnt'];

// Pending requests
$pendingRequests = $conn->query("SELECT COUNT(*) as cnt FROM stock_requests WHERE status='pending'")->fetch_assoc()['cnt'];

// Trending shoes per brand
$trending = $conn->query("
    SELECT s.id, s.name, s.gender, s.price, s.image, b.name as brand_name,
           COALESCE(SUM(i.quantity), 0) as total_stock
    FROM shoes s
    JOIN brands b ON s.brand_id = b.id
    LEFT JOIN inventory i ON i.shoe_id = s.id
    WHERE s.is_trending = 1
    GROUP BY s.id
    ORDER BY b.name, total_stock DESC
");

// Stock per brand for chart
$brandStock = $conn->query("
    SELECT b.name as brand_name, COALESCE(SUM(i.quantity),0) as total
    FROM brands b
    LEFT JOIN shoes s ON s.brand_id = b.id
    LEFT JOIN inventory i ON i.shoe_id = s.id
    GROUP BY b.id
");
$brandLabels = [];
$brandData = [];
while ($row = $brandStock->fetch_assoc()) {
    $brandLabels[] = $row['brand_name'];
    $brandData[] = (int)$row['total'];
}

// Recent requests
$recentRequests = $conn->query("
    SELECT sr.id, sr.status, sr.created_at, u.branch_name, u.username,
           COUNT(ri.id) as item_count
    FROM stock_requests sr
    JOIN users u ON u.id = sr.branch_id
    LEFT JOIN request_items ri ON ri.request_id = sr.id
    GROUP BY sr.id
    ORDER BY sr.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Dashboard</h1>
            <span class="page-sub">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
        <div class="topbar-right">
            <span class="date-badge"><?= date('F j, Y') ?></span>
        </div>
    </header>

    <!-- Stat Cards -->
    <section class="stats-grid">
        <div class="stat-card accent-orange">
            <div class="stat-icon">👟</div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalShoes ?></span>
                <span class="stat-label">Total Shoe Models</span>
            </div>
        </div>
        <div class="stat-card accent-blue">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalStock) ?></span>
                <span class="stat-label">Total Stock Units</span>
            </div>
        </div>
        <div class="stat-card accent-yellow">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
                <span class="stat-value"><?= $lowStock ?></span>
                <span class="stat-label">Low Stock Items</span>
            </div>
        </div>
        <div class="stat-card accent-red">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <span class="stat-value"><?= $outOfStock ?></span>
                <span class="stat-label">Out of Stock</span>
            </div>
        </div>
        <div class="stat-card accent-green">
            <div class="stat-icon">🕐</div>
            <div class="stat-info">
                <span class="stat-value"><?= $pendingRequests ?></span>
                <span class="stat-label">Pending Requests</span>
            </div>
        </div>
    </section>

    <div class="dashboard-grid">
        <!-- Chart -->
        <section class="card chart-card">
            <div class="card-header">
                <h2 class="card-title">Stock by Brand</h2>
            </div>
            <div class="chart-wrap">
                <canvas id="brandChart"></canvas>
            </div>
        </section>

        <!-- Recent Requests -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Requests</h2>
                <a href="orders.php" class="card-link">View All</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Items</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $recentRequests->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['branch_name'] ?? $req['username']) ?></td>
                        <td><?= $req['item_count'] ?> item(s)</td>
                        <td><?= date('M d, Y', strtotime($req['created_at'])) ?></td>
                        <td><span class="badge badge-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($recentRequests->num_rows === 0): ?>
                    <tr><td colspan="4" class="empty-row">No requests yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Trending Shoes -->
    <section class="card trending-section">
        <div class="card-header">
            <h2 class="card-title">🔥 Trending Shoes</h2>
            <a href="inventory.php" class="card-link">Manage Inventory</a>
        </div>
        <div class="trending-grid">
            <?php
            $trendingRows = $trending->fetch_all(MYSQLI_ASSOC);
            if (count($trendingRows) === 0): ?>
                <p class="empty-row">No trending shoes set. <a href="inventory.php">Mark some as trending.</a></p>
            <?php else:
                foreach ($trendingRows as $shoe): ?>
                <div class="trending-card">
                    <div class="trending-img">
                        <?php if ($shoe['image']): ?>
                            <img src="../assets/images/<?= htmlspecialchars($shoe['image']) ?>" alt="<?= htmlspecialchars($shoe['name']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">👟</div>
                        <?php endif; ?>
                        <span class="gender-tag"><?= ucfirst($shoe['gender']) ?></span>
                    </div>
                    <div class="trending-info">
                        <span class="t-brand"><?= htmlspecialchars($shoe['brand_name']) ?></span>
                        <span class="t-name"><?= htmlspecialchars($shoe['name']) ?></span>
                        <span class="t-stock"><?= $shoe['total_stock'] ?> units</span>
                        <span class="t-price">₱<?= number_format($shoe['price'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>
</main>

<script>
const ctx = document.getElementById('brandChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($brandLabels) ?>,
        datasets: [{
            data: <?= json_encode($brandData) ?>,
            backgroundColor: ['#FF5722','#2196F3','#4CAF50'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#ccc', font: { family: 'DM Sans', size: 13 } }
            }
        }
    }
});
</script>
</body>
</html>