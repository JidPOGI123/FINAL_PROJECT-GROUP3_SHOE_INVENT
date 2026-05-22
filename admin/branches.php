<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username    = trim($_POST['username'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $branch_name = trim($_POST['branch_name'] ?? '');
        $password    = $_POST['password'] ?? '';

        if (!$username || !$email || !$password) {
            $error = "Username, email and password are required.";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, branch_name) VALUES (?,?,?,'branch',?)");
                $stmt->bind_param("ssss", $username, $email, $hash, $branch_name);
                $stmt->execute();
                $success = "Branch account '$username' created successfully!";
            }
        }
    } elseif ($action === 'delete') {
        $uid = (int)$_POST['user_id'];
        $conn->prepare("DELETE FROM users WHERE id=? AND role='branch'")->execute([$uid]);
        $success = "Branch account deleted.";
    }
}

$branches = $conn->query("
    SELECT u.*,
           COUNT(DISTINCT sr.id) as total_requests,
           SUM(CASE WHEN sr.status='pending'  THEN 1 ELSE 0 END) as pending_count,
           SUM(CASE WHEN sr.status='approved' THEN 1 ELSE 0 END) as approved_count
    FROM users u
    LEFT JOIN stock_requests sr ON sr.branch_id = u.id
    WHERE u.role = 'branch'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branches – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Branch Accounts</h1>
            <span class="page-sub">Manage third-party shop accounts</span>
        </div>
        <div class="topbar-right">
            <button class="btn btn-primary" onclick="toggleModal('addModal')">+ Add Branch</button>
        </div>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <section class="card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Branch Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Total Requests</th>
                    <th>Pending</th>
                    <th>Approved</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while ($b = $branches->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($b['branch_name'] ?? '—') ?></strong></td>
                    <td><?= htmlspecialchars($b['username']) ?></td>
                    <td><?= htmlspecialchars($b['email']) ?></td>
                    <td><?= $b['total_requests'] ?></td>
                    <td><span class="badge badge-pending"><?= $b['pending_count'] ?? 0 ?></span></td>
                    <td><span class="badge badge-approved"><?= $b['approved_count'] ?? 0 ?></span></td>
                    <td><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
                    <td class="action-cell">
                        <a href="orders.php?branch=<?= $b['id'] ?>" class="btn btn-sm btn-edit">View Orders</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this branch account?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($branches->num_rows === 0): ?>
                <tr><td colspan="9" class="empty-row">No branch accounts yet. Add one!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<!-- Add Branch Modal -->
<div class="modal-overlay" id="addModal" onclick="if(event.target===this)toggleModal('addModal')">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Add Branch Account</h2>
            <button class="modal-close" onclick="toggleModal('addModal')">✕</button>
        </div>
        <form method="POST" class="shoe-form">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label class="form-label">Branch / Shop Name</label>
                <input type="text" name="branch_name" class="input-field" placeholder="e.g. ShStorage - SM Branch">
            </div>
            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="input-field" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Account</button>
                <button type="button" class="btn btn-ghost" onclick="toggleModal('addModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(id) {
    const modal = document.getElementById(id);
    modal.classList.toggle('active');
}
</script>
</body>
</html>