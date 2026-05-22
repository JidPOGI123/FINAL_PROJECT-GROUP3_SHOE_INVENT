<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once '../config/mailer.php';
requireAdmin();

$action     = $_POST['action'] ?? '';
$request_id = (int)($_POST['request_id'] ?? 0);
$admin_note = trim($_POST['admin_note'] ?? '');

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    header("Location: ../admin/orders.php");
    exit();
}

// Get request and verify it's still pending
$stmt = $conn->prepare("SELECT * FROM stock_requests WHERE id = ? AND status = 'pending'");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    header("Location: ../admin/orders.php?error=not_found");
    exit();
}

if ($action === 'approve') {
    // Get all items in the request
    $items = $conn->prepare("
        SELECT ri.*, inv.quantity as current_stock
        FROM request_items ri
        LEFT JOIN inventory inv ON inv.shoe_id = ri.shoe_id AND inv.size = ri.size
        WHERE ri.request_id = ?
    ");
    $items->bind_param("i", $request_id);
    $items->execute();
    $itemRows = $items->get_result()->fetch_all(MYSQLI_ASSOC);

    // Check if all items have sufficient stock
    foreach ($itemRows as $item) {
        if (($item['current_stock'] ?? 0) < $item['quantity']) {
            header("Location: ../admin/orders_approve.php?id=$request_id&error=insufficient");
            exit();
        }
    }

    // Deduct stock for each item
    foreach ($itemRows as $item) {
        $upd = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE shoe_id = ? AND size = ?");
        $upd->bind_param("iis", $item['quantity'], $item['shoe_id'], $item['size']);
        $upd->execute();
    }

    // Add to branch's own stock
    foreach ($itemRows as $item) {
        $ins = $conn->prepare("
            INSERT INTO branch_inventory (branch_id, shoe_id, colorway_id, size, quantity)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $ins->bind_param("iiisi",
            $request['branch_id'],
            $item['shoe_id'],
            $item['colorway_id'],
            $item['size'],
            $item['quantity']
        );
        $ins->execute();
    }

    // Update request status
    $upd = $conn->prepare("UPDATE stock_requests SET status = 'approved', notes = ? WHERE id = ?");
    $upd->bind_param("si", $admin_note, $request_id);
    $upd->execute();

    // Notify branch on approval
    $branchStmt = $conn->prepare("SELECT email, branch_name FROM users WHERE id = ?");
    $branchStmt->bind_param("i", $request['branch_id']);
    $branchStmt->execute();
    $branchUser = $branchStmt->get_result()->fetch_assoc();
    sendRequestNotification($branchUser['email'], $branchUser['branch_name'], $request_id, 'approved', $admin_note);

    header("Location: ../admin/orders_approve.php?id=$request_id&success=approved");

} elseif ($action === 'reject') {
    $upd = $conn->prepare("UPDATE stock_requests SET status = 'rejected', notes = ? WHERE id = ?");
    $upd->bind_param("si", $admin_note, $request_id);
    $upd->execute();

    // Notify branch on rejection
    $branchStmt = $conn->prepare("SELECT email, branch_name FROM users WHERE id = ?");
    $branchStmt->bind_param("i", $request['branch_id']);
    $branchStmt->execute();
    $branchUser = $branchStmt->get_result()->fetch_assoc();
    sendRequestNotification($branchUser['email'], $branchUser['branch_name'], $request_id, 'rejected', $admin_note);

    header("Location: ../admin/orders_approve.php?id=$request_id&success=rejected");
}

exit();
?>