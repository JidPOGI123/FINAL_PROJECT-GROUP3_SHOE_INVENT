<?php
require_once 'config/auth_check.php';
require_once 'config/db.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? 'admin/dashboard.php' : 'branch/dashboard.php'));
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $branch_name = trim($_POST['branch_name'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    if (!$username || !$email || !$password) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Username or email is already taken.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, branch_name) VALUES (?, ?, ?, 'branch', ?)");
            $stmt->bind_param("ssss", $username, $email, $hash, $branch_name);
            $stmt->execute();
            $success = "Account created successfully! You can now log in.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – ShStorage</title>
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
            <h2 class="login-title">Create Account</h2>
            <p class="login-sub">Register your branch / third-party shop</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <a href="login.php">Go to Login →</a>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label class="form-label">Branch / Shop Name</label>
                    <input type="text" name="branch_name" class="input-field"
                           placeholder="e.g. ShStorage - SM Branch"
                           value="<?= htmlspecialchars($_POST['branch_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="input-field"
                           placeholder="Choose a username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="input-field"
                           placeholder="your@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="passInput"
                               class="input-field" placeholder="Min. 6 characters" required>
                        <button type="button" class="toggle-password" onclick="togglePass('passInput')">👁</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <div class="password-wrap">
                        <input type="password" name="confirm_password" id="confirmInput"
                               class="input-field" placeholder="Re-enter password" required>
                        <button type="button" class="toggle-password" onclick="togglePass('confirmInput')">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-login">Create Account</button>

                <div class="login-divider">Already have an account?</div>
                <a href="login.php" class="btn-register">Back to Login</a>
            </form>
        </div>
    </div>

</div>

<script>
function togglePass(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>