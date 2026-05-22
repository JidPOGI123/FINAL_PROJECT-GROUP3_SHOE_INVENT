<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once '../config/mailer.php';
requireBranch();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../branch/request.php");
    exit();
}

$branch_id = $_SESSION['user_id'];
$note      = trim($_POST['note'] ?? '');
$items     = $_POST['items'] ?? [];

// Validate items
$validItems = [];
foreach ($items as $item) {
    $shoe_id     = (int)($item['shoe_id'] ?? 0);
    $size        = trim($item['size'] ?? '');
    $quantity    = (int)($item['quantity'] ?? 0);
    $colorway_id = ($item['colorway_id'] !== '' && $item['colorway_id'] !== null)
                    ? (int)$item['colorway_id'] : null;
    $colorway    = trim($item['colorway'] ?? '');

    if ($shoe_id && $size && $quantity > 0) {
        $validItems[] = compact('shoe_id', 'size', 'quantity', 'colorway_id', 'colorway');
    }
}

if (empty($validItems)) {
    header("Location: ../branch/request.php?error=empty");
    exit();
}

// Check for existing pending request
$existing = $conn->prepare("SELECT id FROM stock_requests WHERE branch_id = ? AND status = 'pending'");
$existing->bind_param("i", $branch_id);
$existing->execute();
if ($existing->get_result()->num_rows > 0) {
    header("Location: ../branch/request.php?error=pending_exists");
    exit();
}

// Create stock request
$stmt = $conn->prepare("INSERT INTO stock_requests (branch_id, notes) VALUES (?, ?)");
$stmt->bind_param("is", $branch_id, $note);
$stmt->execute();
$request_id = $stmt->insert_id;

// Insert request items
foreach ($validItems as $item) {
    $ins = $conn->prepare("INSERT INTO request_items (request_id, shoe_id, colorway_id, colorway, size, quantity) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("iiissi", $request_id, $item['shoe_id'], $item['colorway_id'], $item['colorway'], $item['size'], $item['quantity']);
    $ins->execute();
}

$adminEmail = 'monkeygoku934@gmail.com'; // ← change this to the admin's email
sendRequestNotification($adminEmail, $_SESSION['branch_name'], $request_id, 'submitted');

header("Location: ../branch/request_history.php?success=submitted");
exit();
?>