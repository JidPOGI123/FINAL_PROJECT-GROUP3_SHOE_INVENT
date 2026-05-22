<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isBranch(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'branch';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: " . getLoginPath() . "?error=Please+log+in+to+continue.");
        exit();
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . getLoginPath() . "?error=Access+denied.+Admins+only.");
        exit();
    }
}

function requireBranch(): void {
    requireLogin();
    if (!isBranch()) {
        // Allow admin to preview branch pages without redirecting
        if (isset($_SESSION['_admin_preview']) && $_SESSION['_admin_preview'] === true) {
            return;
        }
        header("Location: " . getLoginPath() . "?error=Access+denied.+Branch+accounts+only.");
        exit();
    }
}

// Resolves path to login.php regardless of which subfolder calls this
function getLoginPath(): string {
    $depth = substr_count(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/') - 1;
    return str_repeat('../', max(0, $depth - 1)) . 'login.php';
}