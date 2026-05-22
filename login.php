<?php
require_once 'config/auth_check.php';
require_once 'config/db.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? 'admin/dashboard.php' : 'branch/dashboard.php'));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['_admin_id'] = $row['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['branch_name']= $user['branch_name'];

            header("Location: " . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'branch/dashboard.php'));
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – ShStorage</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<div class="login-wrapper">

    <div class="login-left">
        <div class="brand-block">
            <div class="brand-logo-icon">👟</div>
            <h1 class="brand-title">ShStorage</h1>
            <p class="brand-tagline">Sneaker Inventory & Stock Management</p>
        </div>
        <div class="brand-features">
            <div class="feature-item">📦 Real-time Inventory Tracking</div>
            <div class="feature-item">🔥 Trending Shoes Per Brand</div>
            <div class="feature-item">🏪 Branch Stock Requests</div>
            <div class="feature-item">📊 Admin Analytics Dashboard</div>
        </div>
        <div class="brand-footer">Nike · Adidas · Converse</div>
    </div>

    <div class="login-right">
        <div class="login-box">
            <h2 class="login-title">Welcome Back</h2>
            <p class="login-sub">Sign in to your ShStorage account</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label class="form-label">Username or Email</label>
                    <input type="text" name="username" class="input-field"
                           placeholder="Enter username or email"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="passwordInput"
                               class="input-field" placeholder="Enter password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-login">Sign In</button>

                <div class="login-divider">Don't have an account?</div>
                <a href="register.php" class="btn-register">Register as Branch</a>
            </form>

            <p class="login-hint">Admin accounts are created by the system administrator.</p>
        </div>
    </div>

</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>