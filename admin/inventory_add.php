<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$brands = $conn->query("SELECT * FROM brands ORDER BY name");
$sizes  = ['6','6.5','7','7.5','8','8.5','9','9.5','10','10.5','11','11.5','12','13'];
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

    // Colorway names submitted
    $cwNames  = $_POST['colorway_name'] ?? [];   // indexed array

    if (!$name || !$brand_id) {
        $error = "Shoe name and brand are required.";
    } elseif (empty(array_filter(array_map('trim', $cwNames)))) {
        $error = "Please add at least one colorway.";
    } else {
        // Handle default shoe image (used as fallback / thumbnail in list)
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid('shoe_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$imageName");
        }

        // Insert shoe
        $stmt = $conn->prepare("INSERT INTO shoes (brand_id, name, model_code, gender, image, price, description, is_trending) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issssdsi", $brand_id, $name, $model, $gender, $imageName, $price, $desc, $trending);
        $stmt->execute();
        $shoe_id = $stmt->insert_id;

        // Insert each colorway + its inventory
        foreach ($cwNames as $idx => $cwName) {
            $cwName = trim($cwName);
            if (!$cwName) continue;

            // Colorway image
            $cwImage = null;
            if (!empty($_FILES['colorway_image']['name'][$idx])) {
                $ext     = pathinfo($_FILES['colorway_image']['name'][$idx], PATHINFO_EXTENSION);
                $cwImage = uniqid('cw_') . '.' . $ext;
                move_uploaded_file($_FILES['colorway_image']['tmp_name'][$idx], "../assets/images/$cwImage");
            }

            $cwStmt = $conn->prepare("INSERT INTO shoe_colorways (shoe_id, name, image, sort_order) VALUES (?,?,?,?)");
            $cwStmt->bind_param("issi", $shoe_id, $cwName, $cwImage, $idx);
            $cwStmt->execute();
            $cw_id = $cwStmt->insert_id;

            // Inventory per size for this colorway
            foreach ($sizes as $size) {
                $qty = (int)($_POST["qty_{$idx}_{$size}"] ?? 0);
                if ($qty > 0) {
                    $si = $conn->prepare("INSERT INTO inventory (shoe_id, colorway_id, size, quantity) VALUES (?,?,?,?)");
                    $si->bind_param("iisi", $shoe_id, $cw_id, $size, $qty);
                    $si->execute();
                }
            }

            // Use first colorway image as shoe default if none uploaded
            if (!$imageName && $cwImage) {
                $imageName = $cwImage;
                $conn->prepare("UPDATE shoes SET image=? WHERE id=?")->bind_param("si", $imageName, $shoe_id) && true;
                $upd = $conn->prepare("UPDATE shoes SET image=? WHERE id=?");
                $upd->bind_param("si", $imageName, $shoe_id);
                $upd->execute();
            }
        }

        $success = "Shoe added successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Shoe – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .colorway-block {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 14px;
            background: var(--bg-card);
            position: relative;
        }
        .colorway-block-header {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 14px;
        }
        .colorway-label {
            font-weight: 600; font-size: .9rem;
            color: var(--text-primary);
        }
        .remove-cw-btn {
            margin-left: auto;
            background: none; border: none;
            color: var(--danger); cursor: pointer;
            font-size: .85rem; padding: 4px 8px;
            border-radius: 4px;
        }
        .remove-cw-btn:hover { background: rgba(220,53,69,.1); }
        .cw-size-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        .cw-size-item { display: flex; flex-direction: column; gap: 4px; }
        .cw-size-label { font-size: .72rem; color: var(--text-muted); }
        .cw-size-input { width: 100%; text-align: center; }
        .add-cw-btn {
            border: 2px dashed var(--border);
            background: none; color: var(--text-muted);
            padding: 12px; border-radius: var(--radius);
            cursor: pointer; width: 100%; font-size: .88rem;
            transition: border-color .2s, color .2s;
        }
        .add-cw-btn:hover { border-color: var(--accent); color: var(--accent); }
    </style>
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <a href="inventory.php" class="back-link">← Back</a>
            <h1 class="page-title">Add New Shoe</h1>
        </div>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="inventory.php">View Inventory</a></div>
    <?php endif; ?>

    <section class="card form-card">
        <form method="POST" enctype="multipart/form-data" class="shoe-form">

            <!-- Basic Info -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Shoe Name *</label>
                    <input type="text" name="name" class="input-field" placeholder="e.g. Chuck 70" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Model Code</label>
                    <input type="text" name="model_code" class="input-field" placeholder="e.g. CON162853C">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Brand *</label>
                    <select name="brand_id" class="input-field" required>
                        <option value="">Select Brand</option>
                        <?php while ($b = $brands->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="input-field" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="unisex">Unisex</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (₱)</label>
                    <input type="number" name="price" class="input-field" step="0.01" placeholder="0.00">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="input-field" rows="3" placeholder="Short description..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label checkbox-label">
                    <input type="checkbox" name="is_trending" value="1">
                    <span>Mark as Trending 🔥</span>
                </label>
            </div>

            <!-- Colorways -->
            <div class="form-group" style="margin-top:8px;">
                <label class="form-label">Colorways &amp; Stock *</label>
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:12px;">
                    Add one block per colorway. Each colorway has its own image and stock per size.
                </p>

                <div id="colorwayList"></div>
                <button type="button" class="add-cw-btn" onclick="addColorway()">+ Add Colorway</button>
            </div>

            <div class="form-actions" style="margin-top:24px;">
                <button type="submit" class="btn btn-primary btn-lg">Add Shoe</button>
                <a href="inventory.php" class="btn btn-ghost btn-lg">Cancel</a>
            </div>
        </form>
    </section>
</main>

<script>
const SIZES = <?= json_encode($sizes) ?>;
let cwCount = 0;

function addColorway() {
    const idx = cwCount++;
    const list = document.getElementById('colorwayList');

    const block = document.createElement('div');
    block.className = 'colorway-block';
    block.id = 'cw_' + idx;

    block.innerHTML = `
        <div class="colorway-block-header">
            <span class="colorway-label">Colorway ${idx + 1}</span>
            <button type="button" class="remove-cw-btn" onclick="removeColorway('cw_${idx}')">✕ Remove</button>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Colorway Name *</label>
                <input type="text" name="colorway_name[${idx}]" class="input-field"
                       placeholder="e.g. Black / White" required>
            </div>
            <div class="form-group">
                <label class="form-label">Colorway Image</label>
                <input type="file" name="colorway_image[${idx}]" class="input-field" accept="image/*"
                       onchange="previewCwImage(this, 'cwprev_${idx}')">
                <img id="cwprev_${idx}" src="#" alt="Preview"
                     style="display:none;width:80px;height:60px;object-fit:cover;border-radius:6px;margin-top:6px;">
            </div>
        </div>
        <label class="form-label">Stock per Size</label>
        <div class="cw-size-grid">
            ${SIZES.map(s => `
                <div class="cw-size-item">
                    <span class="cw-size-label">US ${s}</span>
                    <input type="number" name="qty_${idx}_${s}" class="input-field cw-size-input" min="0" value="0">
                </div>
            `).join('')}
        </div>
    `;

    list.appendChild(block);
    // Re-number labels
    renumberColorways();
}

function removeColorway(id) {
    document.getElementById(id)?.remove();
    renumberColorways();
}

function renumberColorways() {
    document.querySelectorAll('.colorway-block .colorway-label').forEach((el, i) => {
        el.textContent = 'Colorway ' + (i + 1);
    });
}

function previewCwImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// Start with one colorway block
addColorway();
</script>
</body>
</html>