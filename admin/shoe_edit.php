<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: inventory.php"); exit(); }

// Fetch shoe
$shoeStmt = $conn->prepare("SELECT s.*, b.name as brand_name FROM shoes s JOIN brands b ON b.id = s.brand_id WHERE s.id = ?");
$shoeStmt->bind_param("i", $id);
$shoeStmt->execute();
$shoe = $shoeStmt->get_result()->fetch_assoc();
if (!$shoe) { header("Location: inventory.php"); exit(); }

$brands = $conn->query("SELECT * FROM brands ORDER BY name");
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        // Handle image upload
        $imageName = $shoe['image'];
        if (!empty($_FILES['image']['name'])) {
            $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid('shoe_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$imageName");
        }

        $stmt = $conn->prepare("UPDATE shoes SET brand_id=?, name=?, model_code=?, gender=?, image=?, price=?, description=?, is_trending=? WHERE id=?");
        $stmt->bind_param("issssdsii", $brand_id, $name, $model, $gender, $imageName, $price, $desc, $trending, $id);
        $stmt->execute();

        $success = "Shoe info updated successfully!";

        // Refresh shoe data
        $shoeStmt->execute();
        $shoe = $shoeStmt->get_result()->fetch_assoc();
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

    <div class="two-col-layout" style="align-items: start;">

        <!-- LEFT: Edit Form -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Shoe Information</h2>
            </div>
            <form method="POST" enctype="multipart/form-data" class="shoe-form">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Shoe Name *</label>
                        <input type="text" name="name" class="input-field"
                               value="<?= htmlspecialchars($shoe['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model Code</label>
                        <input type="text" name="model_code" class="input-field"
                               value="<?= htmlspecialchars($shoe['model_code'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Brand *</label>
                        <select name="brand_id" class="input-field" required>
                            <?php $brands->data_seek(0); while ($b = $brands->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>" <?= $shoe['brand_id'] == $b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
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
                        <input type="number" name="price" class="input-field" step="0.01"
                               value="<?= $shoe['price'] ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="input-field" rows="3"><?= htmlspecialchars($shoe['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Shoe Image</label>
                    <?php if ($shoe['image']): ?>
                        <img src="../assets/images/<?= htmlspecialchars($shoe['image']) ?>"
                             class="img-preview" id="imgPreview"
                             style="width:160px;height:130px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                    <?php else: ?>
                        <img id="imgPreview" src="#" alt="Preview" class="img-preview" style="display:none;">
                    <?php endif; ?>
                    <input type="file" name="image" class="input-field" accept="image/*" onchange="previewImage(this)">
                    <small style="color:var(--text-muted);font-size:.75rem;">Leave empty to keep current image.</small>
                </div>

                <div class="form-group">
                    <label class="form-label checkbox-label">
                        <input type="checkbox" name="is_trending" value="1" <?= $shoe['is_trending'] ? 'checked' : '' ?>>
                        <span>Mark as Trending 🔥</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                    <a href="inventory.php" class="btn btn-ghost btn-lg">Cancel</a>
                </div>

            </form>
        </section>

        <!-- RIGHT: Current Info Summary -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Current Details</h2>
            </div>
            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?= htmlspecialchars($shoe['name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Brand</span>
                    <span class="info-value"><?= htmlspecialchars($shoe['brand_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Model</span>
                    <span class="info-value"><?= htmlspecialchars($shoe['model_code'] ?? '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Gender</span>
                    <span class="info-value"><?= ucfirst($shoe['gender']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Price</span>
                    <span class="info-value">₱<?= number_format($shoe['price'], 2) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trending</span>
                    <span class="info-value"><?= $shoe['is_trending'] ? '🔥 Yes' : 'No' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Description</span>
                    <span class="info-value" style="color:var(--text-secondary);font-size:.85rem;">
                        <?= htmlspecialchars($shoe['description'] ?? '—') ?>
                    </span>
                </div>
            </div>

            <?php if ($shoe['image']): ?>
            <div style="padding:16px 20px;border-top:1px solid var(--border);">
                <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:8px;">Current Image</p>
                <img src="../assets/images/<?= htmlspecialchars($shoe['image']) ?>"
                     style="width:100%;max-height:200px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--border);">
            </div>
            <?php endif; ?>

            <div style="padding:14px 20px;border-top:1px solid var(--border);">
                <a href="inventory_edit.php?id=<?= $shoe['id'] ?>" class="btn btn-edit btn-sm" style="width:100%;justify-content:center;">
                    📦 Edit Stock / Sizes
                </a>
            </div>
        </section>

    </div>
</main>

<script>
function previewImage(input) {
    const preview = document.getElementById('imgPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>