<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: inventory.php"); exit(); }

$shoeStmt = $conn->prepare("SELECT s.*, b.name as brand_name FROM shoes s JOIN brands b ON b.id = s.brand_id WHERE s.id = ?");
$shoeStmt->bind_param("i", $id);
$shoeStmt->execute();
$shoe = $shoeStmt->get_result()->fetch_assoc();
if (!$shoe) { header("Location: inventory.php"); exit(); }

$brands = $conn->query("SELECT * FROM brands ORDER BY name");
$sizes  = ['6','6.5','7','7.5','8','8.5','9','9.5','10','10.5','11','11.5','12','13'];

// Fetch colorways
$cwResult = $conn->prepare("SELECT * FROM shoe_colorways WHERE shoe_id = ? ORDER BY sort_order, id");
$cwResult->bind_param("i", $id);
$cwResult->execute();
$colorways = $cwResult->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch inventory keyed by [colorway_id][size]
$invResult = $conn->prepare("SELECT colorway_id, size, quantity FROM inventory WHERE shoe_id = ?");
$invResult->bind_param("i", $id);
$invResult->execute();
$invRows = $invResult->get_result()->fetch_all(MYSQLI_ASSOC);
$inventory = [];
foreach ($invRows as $row) {
    $key = $row['colorway_id'] ?? 'none';
    $inventory[$key][$row['size']] = $row['quantity'];
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_info';

    // ---- Update shoe basic info ----
    if ($action === 'update_info') {
        $name     = trim($_POST['name'] ?? '');
        $brand_id = (int)($_POST['brand_id'] ?? 0);
        $gender   = $_POST['gender'] ?? 'unisex';
        $price    = (float)($_POST['price'] ?? 0);
        $trending = isset($_POST['is_trending']) ? 1 : 0;
        $desc     = trim($_POST['description'] ?? '');
        $model    = trim($_POST['model_code'] ?? '');

        if (!$name || !$brand_id) {
            $error = "Shoe name and brand are required.";
        } else {
            $imageName = $shoe['image'];
            if (!empty($_FILES['image']['name'])) {
                $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = uniqid('shoe_') . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$imageName");
            }
            $stmt = $conn->prepare("UPDATE shoes SET brand_id=?, name=?, model_code=?, gender=?, image=?, price=?, description=?, is_trending=? WHERE id=?");
            $stmt->bind_param("issssdsii", $brand_id, $name, $model, $gender, $imageName, $price, $desc, $trending, $id);
            $stmt->execute();
            $success = "Shoe info updated.";
        }
    }

    // ---- Add new colorway ----
    if ($action === 'add_colorway') {
        $cwName = trim($_POST['new_cw_name'] ?? '');
        if (!$cwName) {
            $error = "Colorway name is required.";
        } else {
            $cwImage = null;
            if (!empty($_FILES['new_cw_image']['name'])) {
                $ext     = pathinfo($_FILES['new_cw_image']['name'], PATHINFO_EXTENSION);
                $cwImage = uniqid('cw_') . '.' . $ext;
                move_uploaded_file($_FILES['new_cw_image']['tmp_name'], "../assets/images/$cwImage");
            }
            $sortOrder = count($colorways);
            $cws = $conn->prepare("INSERT INTO shoe_colorways (shoe_id, name, image, sort_order) VALUES (?,?,?,?)");
            $cws->bind_param("issi", $id, $cwName, $cwImage, $sortOrder);
            $cws->execute();
            $success = "Colorway '$cwName' added.";
        }
    }

    // ---- Update colorway name/image ----
    if ($action === 'update_colorway') {
        $cw_id   = (int)($_POST['cw_id'] ?? 0);
        $cwName  = trim($_POST['cw_name'] ?? '');
        if ($cw_id && $cwName) {
            // Fetch current image
            $cur = $conn->prepare("SELECT image FROM shoe_colorways WHERE id=? AND shoe_id=?");
            $cur->bind_param("ii", $cw_id, $id);
            $cur->execute();
            $curRow   = $cur->get_result()->fetch_assoc();
            $cwImage  = $curRow['image'] ?? null;
            if (!empty($_FILES['cw_image']['name'])) {
                $ext     = pathinfo($_FILES['cw_image']['name'], PATHINFO_EXTENSION);
                $cwImage = uniqid('cw_') . '.' . $ext;
                move_uploaded_file($_FILES['cw_image']['tmp_name'], "../assets/images/$cwImage");
            }
            $upd = $conn->prepare("UPDATE shoe_colorways SET name=?, image=? WHERE id=? AND shoe_id=?");
            $upd->bind_param("ssii", $cwName, $cwImage, $cw_id, $id);
            $upd->execute();
            $success = "Colorway updated.";
        }
    }

    // ---- Delete colorway ----
    if ($action === 'delete_colorway') {
        $cw_id = (int)($_POST['cw_id'] ?? 0);
        if ($cw_id) {
            $del = $conn->prepare("DELETE FROM shoe_colorways WHERE id=? AND shoe_id=?");
            $del->bind_param("ii", $cw_id, $id);
            $del->execute();
            $success = "Colorway deleted.";
        }
    }

    // ---- Update inventory for a colorway ----
    if ($action === 'update_stock') {
        $cw_id = (int)($_POST['cw_id'] ?? 0);
        foreach ($sizes as $size) {
            $qty = (int)($_POST["qty_$size"] ?? 0);
            // Check existing
            $chk = $conn->prepare("SELECT id FROM inventory WHERE shoe_id=? AND colorway_id=? AND size=?");
            $chk->bind_param("iis", $id, $cw_id, $size);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $upd = $conn->prepare("UPDATE inventory SET quantity=? WHERE shoe_id=? AND colorway_id=? AND size=?");
                $upd->bind_param("iiis", $qty, $id, $cw_id, $size);
                $upd->execute();
            } elseif ($qty > 0) {
                $ins = $conn->prepare("INSERT INTO inventory (shoe_id, colorway_id, size, quantity) VALUES (?,?,?,?)");
                $ins->bind_param("iisi", $id, $cw_id, $size, $qty);
                $ins->execute();
            }
        }
        $success = "Stock updated.";
    }

    // Refresh data after any action
    $shoeStmt->execute();
    $shoe = $shoeStmt->get_result()->fetch_assoc();
    $cwResult->execute();
    $colorways = $cwResult->get_result()->fetch_all(MYSQLI_ASSOC);
    $invResult->execute();
    $invRows = $invResult->get_result()->fetch_all(MYSQLI_ASSOC);
    $inventory = [];
    foreach ($invRows as $row) {
        $key = $row['colorway_id'] ?? 'none';
        $inventory[$key][$row['size']] = $row['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Shoe – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .cw-panel {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 16px;
            overflow: hidden;
        }
        .cw-panel-header {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            background: var(--bg-sidebar);
            cursor: pointer;
            user-select: none;
        }
        .cw-panel-header img {
            width: 48px; height: 38px; object-fit: cover;
            border-radius: 4px; flex-shrink: 0;
            background: var(--bg-card);
        }
        .cw-panel-name { font-weight: 600; flex: 1; }
        .cw-panel-stock { font-size: .8rem; color: var(--text-muted); }
        .cw-panel-body { padding: 16px; display: none; }
        .cw-panel-body.open { display: block; }
        .cw-size-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px; margin-top: 10px;
        }
        .cw-size-item { display: flex; flex-direction: column; gap: 4px; }
        .cw-size-label { font-size: .72rem; color: var(--text-muted); }
        .add-cw-form {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 16px;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <a href="inventory.php" class="back-link">← Back to Inventory</a>
            <h1 class="page-title">Edit Shoe</h1>
            <span class="page-sub"><?= htmlspecialchars($shoe['name']) ?></span>
        </div>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="two-col-layout" style="align-items:start;">

        <!-- LEFT: Shoe Info -->
        <section class="card">
            <div class="card-header"><h2 class="card-title">Shoe Information</h2></div>
            <form method="POST" enctype="multipart/form-data" class="shoe-form">
                <input type="hidden" name="action" value="update_info">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Shoe Name *</label>
                        <input type="text" name="name" class="input-field" value="<?= htmlspecialchars($shoe['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model Code</label>
                        <input type="text" name="model_code" class="input-field" value="<?= htmlspecialchars($shoe['model_code'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Brand *</label>
                        <select name="brand_id" class="input-field" required>
                            <?php $brands->data_seek(0); while ($b = $brands->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>" <?= $shoe['brand_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <select name="gender" class="input-field" required>
                            <option value="male"   <?= $shoe['gender']==='male'   ? 'selected':'' ?>>Male</option>
                            <option value="female" <?= $shoe['gender']==='female' ? 'selected':'' ?>>Female</option>
                            <option value="unisex" <?= $shoe['gender']==='unisex' ? 'selected':'' ?>>Unisex</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" name="price" class="input-field" step="0.01" value="<?= $shoe['price'] ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="input-field" rows="3"><?= htmlspecialchars($shoe['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Shoe Image <small style="color:var(--text-muted)">(shown in inventory list)</small></label>
                    <?php if ($shoe['image']): ?>
                        <img src="../assets/images/<?= htmlspecialchars($shoe['image']) ?>" class="img-preview" id="imgPreview"
                             style="width:100px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block;">
                    <?php else: ?>
                        <img id="imgPreview" src="#" alt="Preview" class="img-preview" style="display:none;">
                    <?php endif; ?>
                    <input type="file" name="image" class="input-field" accept="image/*" onchange="previewImage(this,'imgPreview')">
                    <small style="color:var(--text-muted);font-size:.75rem;">Leave empty to keep current image.</small>
                </div>

                <div class="form-group">
                    <label class="form-label checkbox-label">
                        <input type="checkbox" name="is_trending" value="1" <?= $shoe['is_trending'] ? 'checked' : '' ?>>
                        <span>Mark as Trending 🔥</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Info</button>
                    <a href="inventory.php" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>

        <!-- RIGHT: Colorways & Stock -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Colorways &amp; Stock</h2>
            </div>

            <?php if (empty($colorways)): ?>
                <p style="padding:16px;color:var(--text-muted);font-size:.88rem;">No colorways yet. Add one below.</p>
            <?php endif; ?>

            <?php foreach ($colorways as $cw):
                $cwInv    = $inventory[$cw['id']] ?? [];
                $cwTotal  = array_sum($cwInv);
            ?>
            <div class="cw-panel">
                <div class="cw-panel-header" onclick="togglePanel('cwbody_<?= $cw['id'] ?>')">
                    <?php if ($cw['image']): ?>
                        <img src="../assets/images/<?= htmlspecialchars($cw['image']) ?>" alt="">
                    <?php else: ?>
                        <div style="width:48px;height:38px;background:var(--bg-card);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">👟</div>
                    <?php endif; ?>
                    <span class="cw-panel-name"><?= htmlspecialchars($cw['name']) ?></span>
                    <span class="cw-panel-stock"><?= $cwTotal ?> units</span>
                    <span style="color:var(--text-muted);font-size:.8rem;">▼</span>
                </div>
                <div class="cw-panel-body" id="cwbody_<?= $cw['id'] ?>">

                    <!-- Edit colorway name/image -->
                    <form method="POST" enctype="multipart/form-data" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="update_colorway">
                        <input type="hidden" name="cw_id" value="<?= $cw['id'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Colorway Name</label>
                                <input type="text" name="cw_name" class="input-field" value="<?= htmlspecialchars($cw['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Image</label>
                                <?php if ($cw['image']): ?>
                                    <img src="../assets/images/<?= htmlspecialchars($cw['image']) ?>"
                                         id="cwprev_<?= $cw['id'] ?>"
                                         style="display:block;width:60px;height:48px;object-fit:cover;border-radius:4px;margin-bottom:4px;">
                                <?php else: ?>
                                    <img id="cwprev_<?= $cw['id'] ?>" src="#" style="display:none;width:60px;height:48px;object-fit:cover;border-radius:4px;margin-bottom:4px;">
                                <?php endif; ?>
                                <input type="file" name="cw_image" class="input-field" accept="image/*"
                                       onchange="previewImage(this,'cwprev_<?= $cw['id'] ?>')">
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button type="submit" class="btn btn-edit btn-sm">Save Name/Image</button>
                            <button type="submit" form="delform_<?= $cw['id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this colorway and all its stock?')">🗑️ Delete</button>
                        </div>
                    </form>
                    <form id="delform_<?= $cw['id'] ?>" method="POST">
                        <input type="hidden" name="action" value="delete_colorway">
                        <input type="hidden" name="cw_id" value="<?= $cw['id'] ?>">
                    </form>

                    <!-- Stock per size -->
                    <form method="POST">
                        <input type="hidden" name="action" value="update_stock">
                        <input type="hidden" name="cw_id" value="<?= $cw['id'] ?>">
                        <label class="form-label">Stock per Size</label>
                        <div class="cw-size-grid">
                            <?php foreach ($sizes as $size): ?>
                            <div class="cw-size-item">
                                <span class="cw-size-label">US <?= $size ?></span>
                                <input type="number" name="qty_<?= $size ?>" class="input-field cw-size-input"
                                       min="0" value="<?= $cwInv[$size] ?? 0 ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:12px;">
                            <button type="submit" class="btn btn-primary btn-sm">Save Stock</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Add new colorway -->
            <div class="add-cw-form" style="margin-top:8px;">
                <p style="font-size:.85rem;font-weight:600;margin-bottom:12px;color:var(--text-primary);">+ Add New Colorway</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_colorway">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Colorway Name *</label>
                            <input type="text" name="new_cw_name" class="input-field" placeholder="e.g. Parchment / Black">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Image</label>
                            <input type="file" name="new_cw_image" class="input-field" accept="image/*">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Add Colorway</button>
                </form>
            </div>
        </section>

    </div>
</main>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
function togglePanel(id) {
    const el = document.getElementById(id);
    el.classList.toggle('open');
}
</script>
</body>
</html>