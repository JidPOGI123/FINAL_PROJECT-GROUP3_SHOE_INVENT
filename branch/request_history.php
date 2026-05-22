<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireBranch();

$branch_id    = $_SESSION['user_id'];
$statusFilter = $_GET['status'] ?? '';
$successMsg   = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'submitted') $successMsg = '✅ Your stock request has been submitted successfully! An admin will review it shortly.';
}

// Filter
$validStatuses = ['pending', 'approved', 'rejected'];
$whereStatus   = ($statusFilter && in_array($statusFilter, $validStatuses))
    ? "AND sr.status = '" . $conn->real_escape_string($statusFilter) . "'"
    : '';

$stmt = $conn->prepare("
    SELECT sr.id, sr.status, sr.notes, sr.created_at, sr.updated_at,
           COUNT(ri.id) as item_count,
           SUM(ri.quantity) as total_qty
    FROM stock_requests sr
    LEFT JOIN request_items ri ON ri.request_id = sr.id
    WHERE sr.branch_id = ? $whereStatus
    GROUP BY sr.id
    ORDER BY sr.created_at DESC
");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$requests = $stmt->get_result();

// Expanded request details (for modal / detail view)
$detailId = (int)($_GET['view'] ?? 0);
$detailItems = [];
$detailRequest = null;
if ($detailId) {
    $ds = $conn->prepare("SELECT * FROM stock_requests WHERE id = ? AND branch_id = ?");
    $ds->bind_param("ii", $detailId, $branch_id);
    $ds->execute();
    $detailRequest = $ds->get_result()->fetch_assoc();

    if ($detailRequest) {
        $di = $conn->prepare("
            SELECT ri.size, ri.quantity, s.name as shoe_name, s.image, b.name as brand_name
            FROM request_items ri
            JOIN shoes s ON s.id = ri.shoe_id
            JOIN brands b ON b.id = s.brand_id
            WHERE ri.request_id = ?
            ORDER BY b.name, s.name, ri.size
        ");
        $di->bind_param("i", $detailId);
        $di->execute();
        $detailItems = $di->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests – ShStorage Branch</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/branch.css">
</head>
<body>
<?php include '../includes/sidebar_branch.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">My Requests</h1>
            <span class="page-sub">Track your stock request history</span>
        </div>
        <div class="topbar-right">
            <a href="request.php" class="btn btn-primary">+ New Request</a>
        </div>
    </header>

    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= $successMsg ?></div>
    <?php endif; ?>

    <!-- Detail View (if ?view=ID) -->
    <?php if ($detailRequest): ?>
    <section class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Request #<?= str_pad($detailRequest['id'], 4, '0', STR_PAD_LEFT) ?> — Details</h2>
            <a href="request_history.php" class="card-link">← Back to list</a>
        </div>

        <div class="detail-meta" style="display:flex; gap:24px; flex-wrap:wrap; padding: 12px 0 20px;">
            <div>
                <span class="form-label">Status</span><br>
                <span class="badge badge-<?= $detailRequest['status'] ?>"><?= ucfirst($detailRequest['status']) ?></span>
            </div>
            <div>
                <span class="form-label">Submitted</span><br>
                <?= date('M d, Y h:i A', strtotime($detailRequest['created_at'])) ?>
            </div>
            <div>
                <span class="form-label">Last Updated</span><br>
                <?= date('M d, Y h:i A', strtotime($detailRequest['updated_at'])) ?>
            </div>
            <?php if ($detailRequest['notes']): ?>
            <div>
                <span class="form-label">Notes / Admin Remarks</span><br>
                <?= htmlspecialchars($detailRequest['notes']) ?>
            </div>
            <?php endif; ?>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Shoe</th>
                    <th>Brand</th>
                    <th>Size</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detailItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['shoe_name']) ?></td>
                    <td><?= htmlspecialchars($item['brand_name']) ?></td>
                    <td>US <?= htmlspecialchars($item['size']) ?></td>
                    <td><?= $item['quantity'] ?> pair(s)</td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($detailItems)): ?>
                <tr><td colspan="4" class="empty-row">No items found for this request.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- Status Filter Tabs -->
    <div class="tab-bar">
        <a href="request_history.php" class="tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
        <a href="request_history.php?status=pending"  class="tab <?= $statusFilter === 'pending'  ? 'active' : '' ?>">🕐 Pending</a>
        <a href="request_history.php?status=approved" class="tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">✅ Approved</a>
        <a href="request_history.php?status=rejected" class="tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">❌ Rejected</a>
    </div>

    <!-- Requests Table -->
    <section class="card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Items</th>
                    <th>Total Qty</th>
                    <th>Submitted</th>
                    <th>Updated</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($req = $requests->fetch_assoc()): ?>
                <tr>
                    <td>#<?= str_pad($req['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= $req['item_count'] ?> shoe(s)</td>
                    <td><?= number_format($req['total_qty'] ?? 0) ?> pairs</td>
                    <td><?= date('M d, Y', strtotime($req['created_at'])) ?></td>
                    <td><?= date('M d, Y', strtotime($req['updated_at'])) ?></td>
                    <td><span class="badge badge-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span></td>
                    <td class="action-cell">
                        <a href="request_history.php?view=<?= $req['id'] ?>" class="btn btn-sm btn-edit">View Details</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($requests->num_rows === 0): ?>
                <tr>
                    <td colspan="7" class="empty-row">
                        No requests found.
                        <?php if (!$statusFilter): ?>
                            <a href="request.php">Submit your first request.</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>