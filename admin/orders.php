<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$statusFilter = $_GET['status'] ?? '';
$where = $statusFilter ? "WHERE sr.status = '$statusFilter'" : '';

$orders = $conn->query("
    SELECT sr.id, sr.status, sr.notes, sr.created_at, sr.updated_at,
           u.username, u.branch_name,
           COUNT(ri.id) as item_count,
           SUM(ri.quantity) as total_qty
    FROM stock_requests sr
    JOIN users u ON u.id = sr.branch_id
    LEFT JOIN request_items ri ON ri.request_id = sr.id
    $where
    GROUP BY sr.id
    ORDER BY sr.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Stock Requests</h1>
            <span class="page-sub">Incoming requests from branches</span>
        </div>
    </header>

    <!-- Status Filter Tabs -->
    <div class="tab-bar">
        <a href="orders.php" class="tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
        <a href="orders.php?status=pending"  class="tab <?= $statusFilter==='pending'  ? 'active':'' ?>">🕐 Pending</a>
        <a href="orders.php?status=approved" class="tab <?= $statusFilter==='approved' ? 'active':'' ?>">✅ Approved</a>
        <a href="orders.php?status=rejected" class="tab <?= $statusFilter==='rejected' ? 'active':'' ?>">❌ Rejected</a>
    </div>

    <section class="card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Branch</th>
                    <th>Items</th>
                    <th>Total Qty</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td>#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <span class="branch-name"><?= htmlspecialchars($order['branch_name'] ?? $order['username']) ?></span>
                    </td>
                    <td><?= $order['item_count'] ?> shoe(s)</td>
                    <td><?= number_format($order['total_qty']) ?> pairs</td>
                    <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                    <td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                    <td class="action-cell">
                        <a href="orders_approve.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-edit">View / Manage</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($orders->num_rows === 0): ?>
                <tr><td colspan="7" class="empty-row">No requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>