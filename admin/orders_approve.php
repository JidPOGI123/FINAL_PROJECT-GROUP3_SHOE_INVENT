<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: orders.php"); exit(); }

$orderStmt = $conn->prepare("
    SELECT sr.*, u.username, u.branch_name, u.email
    FROM stock_requests sr
    JOIN users u ON u.id = sr.branch_id
    WHERE sr.id = ?
");
$orderStmt->bind_param("i", $id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
if (!$order) { header("Location: orders.php"); exit(); }

// Get request items
$items = $conn->prepare("
    SELECT ri.*, s.name as shoe_name, s.gender, s.image, b.name as brand_name,
           COALESCE(inv.quantity,0) as current_stock
    FROM request_items ri
    JOIN shoes s ON s.id = ri.shoe_id
    JOIN brands b ON b.id = s.brand_id
    LEFT JOIN inventory inv ON inv.shoe_id = ri.shoe_id AND inv.size = ri.size
    WHERE ri.request_id = ?
");
$items->bind_param("i", $id);
$items->execute();
$itemRows = $items->get_result()->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($action === 'approve') {
        // Deduct stock for each item
        $canFulfill = true;
        foreach ($itemRows as $item) {
            if ($item['current_stock'] < $item['quantity']) {
                $canFulfill = false;
                $error = "Cannot approve: insufficient stock for '{$item['shoe_name']}' size {$item['size']} (requested {$item['quantity']}, available {$item['current_stock']}).";
                break;
            }
        }
        if ($canFulfill) {
            foreach ($itemRows as $item) {
                $upd = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE shoe_id=? AND size=?");
                $upd->bind_param("iis", $item['quantity'], $item['shoe_id'], $item['size']);
                $upd->execute();
            }
            $upd = $conn->prepare("UPDATE stock_requests SET status='approved', notes=? WHERE id=?");
            $upd->bind_param("si", $adminNote, $id);
            $upd->execute();
            $success = "Request #".str_pad($id,4,'0',STR_PAD_LEFT)." approved. Stock deducted.";
            $order['status'] = 'approved';
        }
    } elseif ($action === 'reject') {
        $upd = $conn->prepare("UPDATE stock_requests SET status='rejected', notes=? WHERE id=?");
        $upd->bind_param("si", $adminNote, $id);
        $upd->execute();
        $success = "Request rejected.";
        $order['status'] = 'rejected';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request #<?= str_pad($id,4,'0',STR_PAD_LEFT) ?> – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <a href="orders.php" class="back-link">← Back to Orders</a>
            <h1 class="page-title">Request #<?= str_pad($id,4,'0',STR_PAD_LEFT) ?></h1>
        </div>
        <div class="topbar-right">
            <span class="badge badge-<?= $order['status'] ?> badge-lg"><?= ucfirst($order['status']) ?></span>
        </div>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="two-col-layout">
        <!-- Request Info -->
        <section class="card">
            <div class="card-header"><h2 class="card-title">Branch Info</h2></div>
            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">Branch</span>
                    <span class="info-value"><?= htmlspecialchars($order['branch_name'] ?? $order['username']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($order['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Requested</span>
                    <span class="info-value"><?= date('F j, Y h:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <?php if ($order['notes']): ?>
                <div class="info-row">
                    <span class="info-label">Note</span>
                    <span class="info-value"><?= htmlspecialchars($order['notes']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Action Form -->
        <?php if ($order['status'] === 'pending'): ?>
        <section class="card">
            <div class="card-header"><h2 class="card-title">Action</h2></div>
            <form method="POST" class="shoe-form">
                <div class="form-group">
                    <label class="form-label">Admin Note (optional)</label>
                    <textarea name="admin_note" class="input-field" rows="3" placeholder="Reason for approval or rejection..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="action" value="approve" class="btn btn-primary">✅ Approve</button>
                    <button type="submit" name="action" value="reject"  class="btn btn-danger"
                            onclick="return confirm('Reject this request?')">❌ Reject</button>
                </div>
            </form>
        </section>
        <?php endif; ?>
    </div>

    <!-- Items Table -->
    <section class="card">
        <div class="card-header"><h2 class="card-title">Requested Items</h2></div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Shoe</th>
                    <th>Brand</th>
                    <th>Gender</th>
                    <th>Size</th>
                    <th>Requested Qty</th>
                    <th>Current Stock</th>
                    <th>Sufficient?</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itemRows as $item): ?>
                <?php $sufficient = $item['current_stock'] >= $item['quantity']; ?>
                <tr>
                    <td class="shoe-cell">
                        <?php if ($item['image']): ?>
                            <img src="../assets/images/<?= htmlspecialchars($item['image']) ?>" class="shoe-thumb" alt="">
                        <?php else: ?>
                            <span class="shoe-thumb-placeholder">👟</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['shoe_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($item['brand_name']) ?></td>
                    <td><?= ucfirst($item['gender']) ?></td>
                    <td>US <?= $item['size'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= $item['current_stock'] ?></td>
                    <td>
                        <?php if ($sufficient): ?>
                            <span class="stock-status status-in">✅ Yes</span>
                        <?php else: ?>
                            <span class="stock-status status-out">❌ No</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>