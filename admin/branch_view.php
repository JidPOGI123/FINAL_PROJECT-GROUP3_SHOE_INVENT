<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

// Save admin identity before anything else
$adminUserId = $_SESSION['user_id'];
$adminRole   = $_SESSION['role'];

// Get all branches for the selector
$branches = $conn->query("SELECT id, username, branch_name FROM users WHERE role = 'branch' ORDER BY branch_name");

// Which branch are we previewing?
$viewId  = (int)($_GET['branch_id'] ?? 0);
$viewTab = $_GET['tab'] ?? 'dashboard';

$branchInfo = null;
if ($viewId) {
    $s = $conn->prepare("SELECT id, username, branch_name FROM users WHERE id = ? AND role = 'branch'");
    $s->bind_param("i", $viewId);
    $s->execute();
    $branchInfo = $s->get_result()->fetch_assoc();
}

// Pre-render the branch preview BEFORE any HTML output (avoids header() warnings)
$previewHTML = '';
if ($branchInfo) {
    $_SESSION['_admin_preview'] = true;
    $_SESSION['role']           = 'branch';
    $_SESSION['user_id']        = $viewId;
    $_SESSION['branch_name']    = $branchInfo['branch_name'] ?: $branchInfo['username'];

    ob_start();
    if ($viewTab === 'dashboard') {
        include '../branch/dashboard.php';
    } elseif ($viewTab === 'catalog') {
        include '../branch/catalog.php';
    } elseif ($viewTab === 'requests') {
        include '../branch/request_history.php';
    }
    $rawOutput = ob_get_clean();

    // Restore admin session immediately
    $_SESSION['role']    = $adminRole;
    $_SESSION['user_id'] = $adminUserId;
    unset($_SESSION['branch_name'], $_SESSION['_admin_preview']);

    // Extract only the <main> content
    if (preg_match('/<main[^>]*>(.*?)<\/main>/s', $rawOutput, $m)) {
        $previewHTML = $m[1];
    } else {
        $previewHTML = $rawOutput;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch View – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/branch.css">
    <style>
        .branch-selector-bar {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .branch-selector-bar label { font-weight: 600; white-space: nowrap; }
        .branch-selector-bar select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border, #ccc);
            font-size: 14px;
            min-width: 200px;
        }
        .branch-selector-bar .btn { padding: 8px 18px; }
        .preview-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .preview-tab {
            padding: 8px 18px;
            border-radius: 8px;
            border: 1px solid var(--border, #ccc);
            background: transparent;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: inherit;
        }
        .preview-tab.active {
            background: var(--primary, #111);
            color: #fff;
            border-color: var(--primary, #111);
        }
        .preview-frame-wrapper {
            border: 2px dashed var(--border, #ccc);
            border-radius: 12px;
            overflow: hidden;
        }
        .preview-label {
            background: var(--border, #f3f4f6);
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #888;
        }
        .no-branch-selected {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
            font-size: 16px;
        }
        .no-branch-selected span { font-size: 48px; display: block; margin-bottom: 12px; }
        .preview-frame-wrapper a,
        .preview-frame-wrapper button,
        .preview-frame-wrapper input[type="submit"],
        .preview-frame-wrapper input[type="button"] {
            cursor: not-allowed !important;
            opacity: 0.7;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Branch View</h1>
            <span class="page-sub">Preview any branch's perspective</span>
        </div>
    </header>

    <!-- Branch Selector -->
    <form method="GET" class="branch-selector-bar">
        <label for="branch_id">👁️ Viewing as:</label>
        <select name="branch_id" id="branch_id">
            <option value="">— Select a branch —</option>
            <?php while ($b = $branches->fetch_assoc()): ?>
                <option value="<?= $b['id'] ?>" <?= $viewId == $b['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['branch_name'] ?: $b['username']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <input type="hidden" name="tab" value="<?= htmlspecialchars($viewTab) ?>">
        <button type="submit" class="btn btn-primary">View Branch</button>
        <?php if ($branchInfo): ?>
            <span style="margin-left:8px; color:#888; font-size:13px;">
                Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?> (Admin)</strong>
            </span>
        <?php endif; ?>
    </form>

    <?php if ($branchInfo): ?>

        <!-- Tab Switcher -->
        <div class="preview-tabs">
            <a href="?branch_id=<?= $viewId ?>&tab=dashboard"
               class="preview-tab <?= $viewTab === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="?branch_id=<?= $viewId ?>&tab=catalog"
               class="preview-tab <?= $viewTab === 'catalog' ? 'active' : '' ?>">👟 Catalog</a>
            <a href="?branch_id=<?= $viewId ?>&tab=requests"
               class="preview-tab <?= $viewTab === 'requests' ? 'active' : '' ?>">📋 Request History</a>
        </div>

        <!-- Preview Content -->
        <div class="preview-frame-wrapper">
            <div class="preview-label">
                Previewing: <?= htmlspecialchars($branchInfo['branch_name'] ?: $branchInfo['username']) ?>
                — <?= ucfirst($viewTab) ?>
            </div>
            <div><?= $previewHTML ?></div>
        </div>

    <?php else: ?>
        <div class="no-branch-selected">
            <span>🏪</span>
            Select a branch above to preview their view of the system.
        </div>
    <?php endif; ?>
</main>

<script src="../assets/js/main.js"></script>
<script>
// Make the preview completely view-only
document.addEventListener('DOMContentLoaded', function () {
    const preview = document.querySelector('.preview-frame-wrapper');
    if (!preview) return;

    // Block all link clicks inside the preview
    preview.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        const btn  = e.target.closest('button, input[type="submit"], input[type="button"]');

        if (link || btn) {
            e.preventDefault();
            e.stopPropagation();
            showViewOnlyToast();
        }
    }, true);

    // Block all form submissions inside the preview
    preview.addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        showViewOnlyToast();
    }, true);

    // Make it visually clear it's read-only
    preview.style.userSelect = 'none';
    preview.style.pointerEvents = 'auto'; // keep hover styles but intercept clicks above

    function showViewOnlyToast() {
        let toast = document.getElementById('view-only-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'view-only-toast';
            toast.style.cssText = `
                position: fixed;
                bottom: 32px;
                left: 50%;
                transform: translateX(-50%);
                background: #1a1a1a;
                color: #fff;
                padding: 12px 24px;
                border-radius: 8px;
                font-size: 14px;
                z-index: 9999;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                pointer-events: none;
            `;
            document.body.appendChild(toast);
        }
        toast.textContent = '👁️ View only — actions are disabled in admin preview';
        toast.style.opacity = '1';
        clearTimeout(toast._timeout);
        toast._timeout = setTimeout(() => { toast.style.opacity = '0'; }, 2500);
    }
});
</script>
</body>
</html>