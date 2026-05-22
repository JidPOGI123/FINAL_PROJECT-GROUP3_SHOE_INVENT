<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)$_POST['delete_id'];
    $delInv = $conn->prepare("DELETE FROM inventory WHERE shoe_id=?");
    $delInv->bind_param("i", $del_id);
    $delInv->execute();
    $delShoe = $conn->prepare("DELETE FROM shoes WHERE id=?");
    $delShoe->bind_param("i", $del_id);
    $delShoe->execute();
    header("Location: inventory.php?deleted=1");
    exit();
}

// Filters
$search      = trim($_GET['search'] ?? '');
$brandFilter = (int)($_GET['brand'] ?? 0);
$where  = []; $params = []; $types = '';

if ($search) {
    $where[]  = '(s.name LIKE ? OR s.model_code LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($brandFilter) {
    $where[] = 's.brand_id = ?'; $params[] = $brandFilter; $types .= 'i';
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT s.id, s.name, s.model_code, s.gender, s.price, s.image, s.is_trending,
           b.name as brand_name,
           COALESCE(SUM(i.quantity), 0) as total_stock
    FROM shoes s
    JOIN brands b ON s.brand_id = b.id
    LEFT JOIN inventory i ON i.shoe_id = s.id
    $whereSQL
    GROUP BY s.id
    ORDER BY b.name, s.name
";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Group by brand
$byBrand = [];
while ($row = $result->fetch_assoc()) {
    $byBrand[$row['brand_name']][] = $row;
}

$brands = $conn->query("SELECT * FROM brands ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Inventory</h1>
            <span class="page-sub">All shoe models</span>
        </div>
        <div class="topbar-right">
            <a href="../process/export_inventory_sheets.php" class="btn btn-ghost">📊 Export to Sheets</a>
            <a href="inventory_add.php" class="btn btn-primary">➕ Add Shoe</a>
        </div>
    </header>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Shoe deleted successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['exported'])): ?>
        <div class="alert alert-success">✅ Inventory exported to Google Sheets successfully.</div>
    <?php endif; ?>

    <form method="GET" class="filter-bar" style="margin-bottom:0;">
        <input type="text" name="search" class="input-field" style="flex:1;min-width:200px;"
               placeholder="Search by name or model..." value="<?= htmlspecialchars($search) ?>">
        <select name="brand" class="input-field filter-select">
            <option value="">All Brands</option>
            <?php while ($b = $brands->fetch_assoc()): ?>
            <option value="<?= $b['id'] ?>" <?= $brandFilter == $b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?>
            </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="inventory.php" class="btn btn-ghost">Reset</a>
    </form>

    <?php if (empty($byBrand)): ?>
        <div class="card" style="margin-top:20px;">
            <table class="data-table">
                <tbody><tr><td colspan="8" class="empty-row">No shoes found.</td></tr></tbody>
            </table>
        </div>
    <?php else: foreach ($byBrand as $brandName => $rows): ?>
        <div style="margin-top:24px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;padding-bottom:8px;border-bottom:2px solid var(--border);">
                <div style="width:4px;height:24px;background:var(--accent);border-radius:2px;flex-shrink:0;"></div>
                <span style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:.06em;color:var(--text-primary);"><?= htmlspecialchars($brandName) ?></span>
                <span style="font-size:.75rem;color:var(--text-muted);background:var(--bg-card);border:1px solid var(--border);padding:3px 10px;border-radius:20px;"><?= count($rows) ?> model<?= count($rows) != 1 ? 's' : '' ?></span>
            </div>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Shoe</th>
                            <th>Brand</th>
                            <th>Model Code</th>
                            <th>Gender</th>
                            <th>Price</th>
                            <th>Total Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $shoe): ?>
                        <tr>
                            <td>
                                <div class="shoe-cell">
                                    <?php if ($shoe['image']): ?>
                                        <img src="../assets/images/<?= htmlspecialchars($shoe['image']) ?>" class="shoe-thumb" alt="">
                                    <?php else: ?>
                                        <span class="shoe-thumb-placeholder">👟</span>
                                    <?php endif; ?>
                                    <span style="font-weight:600;color:var(--text-primary);">
                                        <?= htmlspecialchars($shoe['name']) ?>
                                        <?php if ($shoe['is_trending']): ?><span style="font-size:.7rem;">🔥</span><?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($shoe['brand_name']) ?></td>
                            <td style="font-family:monospace;font-size:.8rem;"><?= htmlspecialchars($shoe['model_code'] ?? '—') ?></td>
                            <td><?= ucfirst($shoe['gender']) ?></td>
                            <td>₱<?= number_format($shoe['price'], 2) ?></td>
                            <td><strong><?= $shoe['total_stock'] ?></strong></td>
                            <td>
                                <?php if ($shoe['total_stock'] == 0): ?>
                                    <span class="badge badge-rejected">Out of Stock</span>
                                <?php elseif ($shoe['total_stock'] <= 10): ?>
                                    <span class="badge badge-pending">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-approved">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="shoe_edit.php?id=<?= $shoe['id'] ?>" class="btn btn-edit btn-sm">✏️ Edit Info</a>
                                    <a href="inventory_edit.php?id=<?= $shoe['id'] ?>" class="btn btn-ghost btn-sm">📦 Edit Stock</a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete this shoe? This cannot be undone.');">
                                        <input type="hidden" name="delete_id" value="<?= $shoe['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; endif; ?>

</main>
<script src="../assets/js/main.js"></script>
</body>
</html>