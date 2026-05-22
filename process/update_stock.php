<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$action = $_REQUEST['action'] ?? '';

// ── DELETE SHOE ──────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        // Delete inventory records first (FK), then the shoe
        $conn->prepare("DELETE FROM inventory WHERE shoe_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM shoes WHERE id = ?")->execute([$id]);
    }
    header("Location: ../admin/inventory.php?success=deleted");
    exit();
}

// ── TOGGLE TRENDING ──────────────────────────────────────────
if ($action === 'toggle_trending' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $shoe_id = (int)($_POST['shoe_id'] ?? 0);
    if ($shoe_id) {
        $conn->prepare("UPDATE shoes SET is_trending = NOT is_trending WHERE id = ?")->execute([$shoe_id]);
    }
    header("Location: ../admin/inventory.php");
    exit();
}

// ── BULK STOCK UPDATE ────────────────────────────────────────
if ($action === 'bulk_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = $_POST['stock'] ?? []; // stock[inventory_id] = new_quantity

    foreach ($updates as $inv_id => $qty) {
        $inv_id = (int)$inv_id;
        $qty    = (int)$qty;
        if ($inv_id && $qty >= 0) {
            $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $qty, $inv_id);
            $stmt->execute();
        }
    }
    header("Location: ../admin/inventory.php?success=updated");
    exit();
}

// ── DIRECT QUANTITY UPDATE (single size) ─────────────────────
if ($action === 'update_qty' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $shoe_id  = (int)($_POST['shoe_id'] ?? 0);
    $size     = trim($_POST['size'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($shoe_id && $size && $quantity >= 0) {
        // Check if inventory row exists
        $check = $conn->prepare("SELECT id FROM inventory WHERE shoe_id = ? AND size = ?");
        $check->bind_param("is", $shoe_id, $size);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $upd = $conn->prepare("UPDATE inventory SET quantity = ? WHERE shoe_id = ? AND size = ?");
            $upd->bind_param("iis", $quantity, $shoe_id, $size);
            $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO inventory (shoe_id, size, quantity) VALUES (?, ?, ?)");
            $ins->bind_param("isi", $shoe_id, $size, $quantity);
            $ins->execute();
        }
    }

    header("Location: ../admin/inventory_edit.php?id=$shoe_id&success=1");
    exit();
}

// ── ADD STOCK (restock) ──────────────────────────────────────
if ($action === 'add_stock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $shoe_id  = (int)($_POST['shoe_id'] ?? 0);
    $size     = trim($_POST['size'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($shoe_id && $size && $quantity > 0) {
        $upd = $conn->prepare("UPDATE inventory SET quantity = quantity + ? WHERE shoe_id = ? AND size = ?");
        $upd->bind_param("iis", $quantity, $shoe_id, $size);
        $upd->execute();
    }

    header("Location: ../admin/inventory_edit.php?id=$shoe_id&success=1");
    exit();
}

// Fallback
header("Location: ../admin/inventory.php");
exit();
?>