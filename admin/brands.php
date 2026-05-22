<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            $error = "Brand name is required.";
        } else {
            $logoName = null;
            if (!empty($_FILES['logo']['name'])) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logoName = uniqid('brand_') . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], "../assets/images/$logoName");
            }
            $stmt = $conn->prepare("INSERT INTO brands (name, logo) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $logoName);
            $stmt->execute();
            $success = "Brand '$name' added successfully!";
        }
    } elseif ($action === 'delete') {
        $bid = (int)$_POST['brand_id'];
        $conn->prepare("DELETE FROM brands WHERE id=?")->execute([$bid]) ;
        $success = "Brand deleted.";
    }
}

$brands = $conn->query("
    SELECT b.*, COUNT(s.id) as shoe_count,
           COALESCE(SUM(i.quantity),0) as total_stock
    FROM brands b
    LEFT JOIN shoes s ON s.brand_id = b.id
    LEFT JOIN inventory i ON i.shoe_id = s.id
    GROUP BY b.id
    ORDER BY b.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands – ShStorage Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Brands</h1>
            <span class="page-sub">Manage shoe brands</span>
        </div>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="two-col-layout">
        <!-- Add Brand Form -->
        <section class="card form-card">
            <div class="card-header">
                <h2 class="card-title">Add New Brand</h2>
            </div>
            <form method="POST" enctype="multipart/form-data" class="shoe-form">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Brand Name *</label>
                    <input type="text" name="name" class="input-field" placeholder="e.g. Puma" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Brand Logo</label>
                    <input type="file" name="logo" class="input-field" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Brand</button>
                </div>
            </form>
        </section>

        <!-- Brands List -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">All Brands</h2>
            </div>
            <div class="brands-grid">
                <?php while ($brand = $brands->fetch_assoc()): ?>
                <div class="brand-card">
                    <div class="brand-logo-wrap">
                        <?php if ($brand['logo']): ?>
                            <img src="../assets/images/<?= htmlspecialchars($brand['logo']) ?>" alt="<?= htmlspecialchars($brand['name']) ?>">
                        <?php else: ?>
                            <div class="brand-logo-placeholder"><?= strtoupper(substr($brand['name'], 0, 2)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="brand-info">
                        <span class="brand-name"><?= htmlspecialchars($brand['name']) ?></span>
                        <span class="brand-meta"><?= $brand['shoe_count'] ?> models &bull; <?= number_format($brand['total_stock']) ?> units</span>
                    </div>
                    <div class="brand-actions">
                        <a href="inventory.php?brand=<?= $brand['id'] ?>" class="btn btn-sm btn-edit">View Shoes</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this brand and all its shoes?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="brand_id" value="<?= $brand['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>
</main>
</body>
</html>