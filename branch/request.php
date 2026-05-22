<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireBranch();

$branch_id = $_SESSION['user_id'];
$error = '';

// Check if there's already a pending request
$pendingStmt = $conn->prepare("SELECT id FROM stock_requests WHERE branch_id = ? AND status = 'pending'");
$pendingStmt->bind_param("i", $branch_id);
$pendingStmt->execute();
$hasPending = $pendingStmt->get_result()->num_rows > 0;

// Handle error messages from redirect
$errorMsg = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'empty')          $errorMsg = 'Your request cart is empty. Please add at least one item.';
    if ($_GET['error'] === 'pending_exists') $errorMsg = 'You already have a pending request. Please wait for it to be reviewed before submitting a new one.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Stock – ShStorage Branch</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/branch.css">
</head>
<body>
<?php include '../includes/sidebar_branch.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Request Stock</h1>
            <span class="page-sub">Build and submit your stock request</span>
        </div>
        <div class="topbar-right">
            <a href="catalog.php" class="btn btn-secondary">+ Add from Catalog</a>
        </div>
    </header>

    <?php if ($errorMsg): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($hasPending): ?>
        <div class="alert alert-warning">
            ⚠️ You already have a <strong>pending request</strong>. You cannot submit a new one until it is reviewed.
            <a href="request_history.php" class="btn btn-sm btn-ghost" style="margin-left:12px;">View My Requests</a>
        </div>
    <?php endif; ?>

    <div class="request-layout">

        <!-- Cart / Items Panel -->
        <section class="card request-cart-panel">
            <div class="card-header">
                <h2 class="card-title">📋 Request Cart</h2>
                <span class="cart-summary" id="cartTotal">0 items</span>
            </div>

            <div id="cartEmpty" class="empty-row" style="padding: 32px 0; text-align:center;">
                Your cart is empty.<br>
                <a href="catalog.php" class="card-link">Browse the catalog</a> to add items.
            </div>

            <div id="cartItems"></div>

            <form id="requestForm" method="POST" action="../process/request_stock.php">
                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label">Note / Remarks (optional)</label>
                    <textarea name="note" class="input-field" rows="3" placeholder="Any special notes for this request..." <?= $hasPending ? 'disabled' : '' ?>></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" id="submitBtn" class="btn btn-primary btn-lg" disabled <?= $hasPending ? 'style="opacity:.5;" title="Pending request exists"' : '' ?>>
                        Submit Request
                    </button>
                    <button type="button" class="btn btn-ghost btn-lg" onclick="clearCart()">Clear Cart</button>
                </div>
            </form>
        </section>

        <!-- Info / Tips Panel -->
        <aside class="card request-info-panel">
            <div class="card-header">
                <h2 class="card-title">ℹ️ How it works</h2>
            </div>
            <ol class="info-steps">
                <li>Browse the <a href="catalog.php">Shoe Catalog</a> and add the shoes and sizes you need.</li>
                <li>Adjust quantities in your cart.</li>
                <li>Add any notes and submit the request.</li>
                <li>An admin will review and approve or reject the request.</li>
                <li>Track your requests in <a href="request_history.php">My Requests</a>.</li>
            </ol>

            <div class="info-note">
                <strong>Note:</strong> Only one pending request is allowed at a time. Submit a new request after your current one is reviewed.
            </div>
        </aside>

    </div>
</main>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/request.js"></script>
<script>
// Clear localStorage cart after successful submission
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('cleared') === '1') {
    localStorage.removeItem('shstorage_cart');
    cart = [];
    renderCart();
    updateCartBadge();
}

// Clear cart on form submit (before redirect happens)
document.getElementById('requestForm').addEventListener('submit', function() {
    localStorage.removeItem('shstorage_cart');
});

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear all items from your cart?')) {
        cart = [];
        saveCart();
        renderCart();
        updateCartBadge();
    }
}
</script>
</body>
</html>