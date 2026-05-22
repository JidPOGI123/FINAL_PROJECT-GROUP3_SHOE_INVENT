<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireBranch();

$branch_id   = $_SESSION['user_id'];
$search      = trim($_GET['search'] ?? '');
$brand_filter = (int)($_GET['brand'] ?? 0);

// Brands for filter dropdown
$brands = $conn->query("SELECT id, name FROM brands ORDER BY name");

// Build query
$where = "WHERE bi.branch_id = ?";
$params = [$branch_id];
$types  = "i";

if ($search) {
    $where   .= " AND (s.name LIKE ? OR b.name LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if ($brand_filter) {
    $where   .= " AND b.id = ?";
    $params[] = $brand_filter;
    $types   .= "i";
}

$stmt = $conn->prepare("
    SELECT bi.shoe_id, bi.colorway_id, bi.size, bi.quantity, bi.updated_at,
           s.name as shoe_name, s.image as shoe_image, s.price,
           b.name as brand_name,
           sc.name as colorway_name, sc.image as colorway_image
    FROM inventory bi
    JOIN shoes s  ON s.id  = bi.shoe_id
    JOIN brands b ON b.id  = s.brand_id
    LEFT JOIN shoe_colorways sc ON sc.id = bi.colorway_id
    $where
    ORDER BY b.name, s.name, bi.size
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Summary counts
$totalUnits  = array_sum(array_column($stocks, 'quantity'));
$lowStockCnt = count(array_filter($stocks, fn($r) => $r['quantity'] > 0 && $r['quantity'] <= 5));
$outOfStock  = count(array_filter($stocks, fn($r) => $r['quantity'] == 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stocks – ShStorage Branch</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/branch.css">
</head>
<body>
<?php include '../includes/sidebar_branch.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">My Stocks</h1>
            <span class="page-sub">Inventory received from approved requests</span>
        </div>
        <div class="topbar-right">
            <a href="request.php" class="btn btn-primary">+ Request More Stock</a>
        </div>
    </header>

    <!-- Summary Cards -->
    <div class="branch-stats">
        <div class="branch-stat-card green">
            <span class="bsc-icon">📦</span>
            <span class="bsc-value"><?= number_format($totalUnits) ?></span>
            <span class="bsc-label">Total Units</span>
        </div>
        <div class="branch-stat-card blue">
            <span class="bsc-icon">👟</span>
            <span class="bsc-value"><?= count($stocks) ?></span>
            <span class="bsc-label">Size Variants</span>
        </div>
        <div class="branch-stat-card yellow">
            <span class="bsc-icon">⚠️</span>
            <span class="bsc-value"><?= $lowStockCnt ?></span>
            <span class="bsc-label">Low Stock (≤5)</span>
        </div>
        <div class="branch-stat-card red">
            <span class="bsc-icon">❌</span>
            <span class="bsc-value"><?= $outOfStock ?></span>
            <span class="bsc-label">Out of Stock</span>
        </div>
    </div>

    <!-- Filters -->
    <section class="card" style="padding: 16px 24px;">
        <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div>
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Shoe name or brand…"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div>
                <label class="form-label">Brand</label>
                <select name="brand" class="form-control">
                    <option value="">All Brands</option>
                    <?php while ($br = $brands->fetch_assoc()): ?>
                    <option value="<?= $br['id'] ?>" <?= $brand_filter == $br['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($br['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <?php if ($search || $brand_filter): ?>
                <a href="my_stocks.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </section>

    <!-- Stock Table -->
    <section class="card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Shoe</th>
                    <th>Brand</th>
                    <th>Colorway</th>
                    <th>Size</th>
                    <th>Qty on Hand</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stocks)): ?>
                <tr>
                    <td colspan="7" class="empty-row">
                        No stock received yet.
                        <?php if (!$search && !$brand_filter): ?>
                            <a href="request.php">Submit a stock request to get started.</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($stocks as $row):
                    $statusClass = $row['quantity'] == 0 ? 'rejected' : ($row['quantity'] <= 5 ? 'pending' : 'approved');
                    $statusLabel = $row['quantity'] == 0 ? 'Out of Stock' : ($row['quantity'] <= 5 ? 'Low Stock' : 'In Stock');
                    $img = $row['colorway_image'] ?? $row['shoe_image'];
                ?>
                <tr>
                    <td style="display:flex; align-items:center; gap:10px;">
                        <?php if ($img): ?>
                            <img src="../assets/images/<?= htmlspecialchars($img) ?>"
                                 alt="" style="width:40px; height:40px; object-fit:cover; border-radius:6px;">
                        <?php else: ?>
                            <div style="width:40px;height:40px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;">👟</div>
                        <?php endif; ?>
                        <?= htmlspecialchars($row['shoe_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['brand_name']) ?></td>
                    <td><?= $row['colorway_name'] ? htmlspecialchars($row['colorway_name']) : '<span style="color:#999">—</span>' ?></td>
                    <td>US <?= htmlspecialchars($row['size']) ?></td>
                    <td><strong><?= $row['quantity'] ?></strong> pair(s)</td>
                    <td><span class="badge badge-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                    <td><?= date('M d, Y', strtotime($row['updated_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>