<?php
require_once '../config/auth_check.php';
require_once '../config/db.php';
requireBranch();

// Filters
$brandFilter  = (int)($_GET['brand'] ?? 0);
$genderFilter = $_GET['gender'] ?? '';
$search       = trim($_GET['search'] ?? '');

// Build query
$where  = [];
$params = [];
$types  = '';

if ($brandFilter) {
    $where[]  = 's.brand_id = ?';
    $params[] = $brandFilter;
    $types   .= 'i';
}
if ($genderFilter && in_array($genderFilter, ['male', 'female', 'unisex'])) {
    $where[]  = 's.gender = ?';
    $params[] = $genderFilter;
    $types   .= 's';
}
if ($search) {
    $where[]  = '(s.name LIKE ? OR s.model_code LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT s.id, s.name, s.model_code, s.gender, s.price, s.image, s.is_trending,
           b.id as brand_id, b.name as brand_name,
           COALESCE(SUM(i.quantity), 0) as total_stock
    FROM shoes s
    JOIN brands b ON s.brand_id = b.id
    LEFT JOIN inventory i ON i.shoe_id = s.id
    $whereSQL
    GROUP BY s.id
    ORDER BY b.name, s.is_trending DESC, s.name
";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Group shoes by brand; attach colorways + inventory per shoe
$byBrand = [];
while ($row = $result->fetch_assoc()) {
    // Fetch colorways for this shoe
    $cwStmt = $conn->prepare("SELECT id, name, image FROM shoe_colorways WHERE shoe_id = ? ORDER BY sort_order, id");
    $cwStmt->bind_param("i", $row['id']);
    $cwStmt->execute();
    $cwRows = $cwStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // For each colorway, fetch its inventory
    $colorwaysData = [];
    foreach ($cwRows as $cw) {
        $invStmt = $conn->prepare("SELECT size, quantity FROM inventory WHERE shoe_id = ? AND colorway_id = ? ORDER BY CAST(size AS DECIMAL)");
        $invStmt->bind_param("ii", $row['id'], $cw['id']);
        $invStmt->execute();
        $sizes = $invStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $cw['sizes'] = $sizes;
        $cw['total'] = array_sum(array_column($sizes, 'quantity'));
        $colorwaysData[] = $cw;
    }

    // Fallback: shoes with no colorways use legacy inventory (colorway_id IS NULL)
    if (empty($colorwaysData)) {
        $invStmt = $conn->prepare("SELECT size, quantity FROM inventory WHERE shoe_id = ? AND colorway_id IS NULL ORDER BY CAST(size AS DECIMAL)");
        $invStmt->bind_param("i", $row['id']);
        $invStmt->execute();
        $sizes = $invStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $colorwaysData[] = [
            'id'    => null,
            'name'  => '',
            'image' => $row['image'],
            'sizes' => $sizes,
            'total' => array_sum(array_column($sizes, 'quantity')),
        ];
    }

    $row['colorways'] = $colorwaysData;
    $bName = $row['brand_name'];
    if (!isset($byBrand[$bName])) $byBrand[$bName] = [];
    $byBrand[$bName][] = $row;
}

$brands = $conn->query("SELECT * FROM brands ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Catalog – ShStorage Branch</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/branch.css">
    <style>
        .brand-section { margin-bottom: 36px; }
        .brand-section-header {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 16px; padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
        }
        .brand-section-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem; letter-spacing: .06em;
            color: var(--text-primary);
        }
        .brand-section-count {
            font-size: .75rem; color: var(--text-muted);
            background: var(--bg-card); border: 1px solid var(--border);
            padding: 3px 10px; border-radius: 20px;
        }
        .brand-accent-bar {
            width: 4px; height: 28px;
            background: var(--accent);
            border-radius: 2px; flex-shrink: 0;
        }

        /* Colorway swatches */
        .colorway-swatches {
            display: flex; gap: 6px; flex-wrap: wrap;
            margin: 8px 0 4px;
        }
        .colorway-swatch {
            width: 32px; height: 24px;
            border-radius: 4px; cursor: pointer;
            border: 2px solid transparent;
            overflow: hidden;
            transition: border-color .15s, transform .15s;
            background: var(--bg-sidebar);
            flex-shrink: 0;
        }
        .colorway-swatch img {
            width: 100%; height: 100%; object-fit: cover;
            display: block;
        }
        .colorway-swatch.active {
            border-color: var(--accent);
            transform: scale(1.1);
        }
        .colorway-swatch-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem;
        }
        .colorway-name-label {
            font-size: .72rem; color: var(--text-muted);
            min-height: 16px; margin-bottom: 2px;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar_branch.php'; ?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Shoe Catalog</h1>
            <span class="page-sub">Browse available stock</span>
        </div>
        <div class="topbar-right">
            <a href="request.php" class="btn btn-primary" style="position:relative;">
                📋 My Request
                <span id="cartCount" class="nav-badge" style="display:none;">0</span>
            </a>
        </div>
    </header>

    <!-- Filters -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="input-field filter-search"
               placeholder="Search shoes..." value="<?= htmlspecialchars($search) ?>">

        <select name="brand" class="input-field filter-select">
            <option value="">All Brands</option>
            <?php while ($b = $brands->fetch_assoc()): ?>
            <option value="<?= $b['id'] ?>" <?= $brandFilter == $b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?>
            </option>
            <?php endwhile; ?>
        </select>

        <select name="gender" class="input-field filter-select">
            <option value="">All Genders</option>
            <option value="male"   <?= $genderFilter === 'male'   ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= $genderFilter === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="unisex" <?= $genderFilter === 'unisex' ? 'selected' : '' ?>>Unisex</option>
        </select>

        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="catalog.php" class="btn btn-ghost">Reset</a>
    </form>

    <!-- Shoes grouped by Brand -->
    <?php if (empty($byBrand)): ?>
        <p class="empty-row" style="margin-top:32px;">No shoes found matching your filters.</p>
    <?php else:
        foreach ($byBrand as $brandName => $shoes):
            $inStockCount = count(array_filter($shoes, fn($s) => $s['total_stock'] > 0));
    ?>
    <div class="brand-section">
        <div class="brand-section-header">
            <div class="brand-accent-bar"></div>
            <span class="brand-section-name"><?= htmlspecialchars($brandName) ?></span>
            <span class="brand-section-count"><?= count($shoes) ?> model<?= count($shoes) != 1 ? 's' : '' ?></span>
            <?php if ($inStockCount < count($shoes)): ?>
                <span class="brand-section-count" style="color:var(--accent-yellow);border-color:rgba(255,193,7,.3);">
                    <?= count($shoes) - $inStockCount ?> out of stock
                </span>
            <?php endif; ?>
        </div>

        <div class="catalog-grid">
            <?php foreach ($shoes as $shoe):
                $colorways    = $shoe['colorways'];
                $firstCw      = $colorways[0];
                $hasColorways = count($colorways) > 1 || !empty($firstCw['name']);
                $shoeDataJson = htmlspecialchars(json_encode([
                    'id'        => $shoe['id'],
                    'name'      => $shoe['name'],
                    'brand'     => $shoe['brand_name'],
                    'colorways' => $colorways,
                ]), ENT_QUOTES);
            ?>
            <div class="catalog-card <?= $shoe['total_stock'] == 0 ? 'out-of-stock' : '' ?>"
                 id="card_<?= $shoe['id'] ?>">
                <div class="catalog-img">
                    <img id="cardimg_<?= $shoe['id'] ?>"
                         src="<?= $firstCw['image'] ? '../assets/images/' . htmlspecialchars($firstCw['image']) : '' ?>"
                         alt="<?= htmlspecialchars($shoe['name']) ?>"
                         <?= !$firstCw['image'] ? 'style="display:none;"' : '' ?>>
                    <?php if (!$firstCw['image']): ?>
                        <div class="img-placeholder"></div>
                    <?php endif; ?>
                    <?php if ($shoe['is_trending']): ?>
                        <span class="trending-badge">🔥 Trending</span>
                    <?php endif; ?>
                    <span class="gender-tag"><?= ucfirst($shoe['gender']) ?></span>
                    <?php if ($shoe['total_stock'] == 0): ?>
                        <span class="out-badge">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <div class="catalog-info">
                    <span class="catalog-name"><?= htmlspecialchars($shoe['name']) ?></span>
                    <?php if ($shoe['model_code']): ?>
                        <span class="catalog-code"><?= htmlspecialchars($shoe['model_code']) ?></span>
                    <?php endif; ?>
                    <span class="catalog-price">₱<?= number_format($shoe['price'], 2) ?></span>

                    <?php if ($hasColorways): ?>
                    <!-- Colorway swatches -->
                    <span class="colorway-name-label" id="cwlabel_<?= $shoe['id'] ?>"><?= htmlspecialchars($firstCw['name']) ?></span>
                    <div class="colorway-swatches" id="swatches_<?= $shoe['id'] ?>">
                        <?php foreach ($colorways as $cwIdx => $cw): ?>
                        <div class="colorway-swatch <?= $cwIdx === 0 ? 'active' : '' ?>"
                             title="<?= htmlspecialchars($cw['name']) ?>"
                             onclick="selectColorway(<?= $shoe['id'] ?>, <?= $cwIdx ?>, this)">
                            <?php if ($cw['image']): ?>
                                <img src="../assets/images/<?= htmlspecialchars($cw['image']) ?>" alt="<?= htmlspecialchars($cw['name']) ?>">
                            <?php else: ?>
                                <div class="colorway-swatch-placeholder">👟</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <span class="catalog-stock <?= $shoe['total_stock'] > 0 && $shoe['total_stock'] <= 5 ? 'low' : '' ?>"
                          id="cardstock_<?= $shoe['id'] ?>">
                        <?= $shoe['total_stock'] == 0
                            ? '❌ Out of Stock'
                            : '📦 ' . $shoe['total_stock'] . ' units available' ?>
                    </span>
                </div>
                <div class="catalog-action">
                    <?php if ($shoe['total_stock'] > 0): ?>
                    <button type="button" class="btn btn-primary btn-sm add-to-request-btn"
                        data-shoe="<?= $shoeDataJson ?>">
                        + Add to Request
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-ghost btn-sm" disabled>Out of Stock</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</main>

<!-- Size Picker Modal -->
<div class="modal-overlay" id="sizePickerModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="sizePickerTitle">Select Size</h3>
            <button type="button" class="modal-close" onclick="closeSizePicker()">✕</button>
        </div>
        <div class="modal-body">
            <!-- Colorway selector inside modal (shown only when >1 colorway) -->
            <div id="modalColorwayRow" style="display:none;margin-bottom:14px;">
                <label class="form-label" style="margin-bottom:6px;">Colorway</label>
                <div id="modalColorwayPicker" class="colorway-swatches" style="gap:8px;"></div>
                <span id="modalColorwayLabel" style="font-size:.8rem;color:var(--text-muted);margin-top:4px;display:block;"></span>
            </div>
            <div class="size-picker-grid" id="sizePicker"></div>
            <div class="form-group" style="margin-top:16px;">
                <label class="form-label">Quantity</label>
                <input type="number" id="sizePickerQty" class="input-field" value="1" min="1" style="max-width:100px;">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="sizePickerConfirm">Add to Request</button>
            <button type="button" class="btn btn-ghost" onclick="closeSizePicker()">Cancel</button>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/request.js"></script>
<script>
// Track selected colorway index per shoe card
const selectedCwIndex = {};

// Swatch click on the card (switches card image + label)
function selectColorway(shoeId, cwIdx, swatchEl) {
    selectedCwIndex[shoeId] = cwIdx;

    // Update active swatch
    document.querySelectorAll('#swatches_' + shoeId + ' .colorway-swatch').forEach(function(s) {
        s.classList.remove('active');
    });
    swatchEl.classList.add('active');

    // Update card image + label
    const btn    = document.querySelector('#card_' + shoeId + ' .add-to-request-btn');
    if (!btn) return;
    const shoe   = JSON.parse(btn.dataset.shoe);
    const cw     = shoe.colorways[cwIdx];

    const img    = document.getElementById('cardimg_' + shoeId);
    if (img && cw.image) {
        img.src = '../assets/images/' + cw.image;
        img.style.display = '';
    }
    const label  = document.getElementById('cwlabel_' + shoeId);
    if (label) label.textContent = cw.name;

    // Update stock display
    const total   = cw.sizes.reduce(function(s, r) { return s + parseInt(r.quantity); }, 0);
    const stockEl = document.getElementById('cardstock_' + shoeId);
    if (stockEl) {
        stockEl.textContent = total === 0 ? '❌ Out of Stock' : '📦 ' + total + ' units available';
    }
}

// Wire up "Add to Request" buttons
document.querySelectorAll('.add-to-request-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const shoe   = JSON.parse(this.dataset.shoe);
        const cwIdx  = selectedCwIndex[shoe.id] ?? 0;
        openSizePickerWithColorways(shoe, cwIdx);
    });
});

// Extended openSizePicker that handles colorways
function openSizePickerWithColorways(shoe, initialCwIdx) {
    const modal  = document.getElementById('sizePickerModal');
    const title  = document.getElementById('sizePickerTitle');
    const picker = document.getElementById('sizePicker');
    const qtyIn  = document.getElementById('sizePickerQty');
    const cwRow  = document.getElementById('modalColorwayRow');
    const cwPick = document.getElementById('modalColorwayPicker');
    const cwLbl  = document.getElementById('modalColorwayLabel');
    if (!modal || !picker) return;

    title.textContent = shoe.name;
    let activeCwIdx  = initialCwIdx;

    function renderSizes(cwIdx) {
        const cw = shoe.colorways[cwIdx];
        picker.innerHTML = '';
        let selectedSize = null;

        cw.sizes.forEach(function(s) {
            const btn    = document.createElement('button');
            btn.type     = 'button';
            btn.className = 'size-pick-btn' + (parseInt(s.quantity) === 0 ? ' out' : '');
            btn.textContent = 'US ' + s.size;
            btn.disabled = parseInt(s.quantity) === 0;
            btn.addEventListener('click', function() {
                picker.querySelectorAll('.size-pick-btn').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                selectedSize = s;
            });
            picker.appendChild(btn);
        });

        // Confirm button
        document.getElementById('sizePickerConfirm').onclick = function() {
            if (!selectedSize) { alert('Please select a size.'); return; }
            const qty = parseInt(qtyIn ? qtyIn.value : 1) || 1;
            const cw  = shoe.colorways[activeCwIdx];
            addToCart(
                shoe.id,
                shoe.name,
                shoe.brand,
                selectedSize.size,
                qty,
                parseInt(selectedSize.quantity),
                cw.id,
                cw.name
            );
            modal.classList.remove('active');
        };
    }

    // Colorway picker in modal
    cwPick.innerHTML = '';
    if (shoe.colorways.length > 1 || shoe.colorways[0].name) {
        cwRow.style.display = shoe.colorways.length > 1 ? '' : 'none';
        shoe.colorways.forEach(function(cw, idx) {
            const sw       = document.createElement('div');
            sw.className   = 'colorway-swatch' + (idx === activeCwIdx ? ' active' : '');
            sw.title       = cw.name;
            sw.style.width  = '40px';
            sw.style.height = '32px';
            if (cw.image) {
                sw.innerHTML = '<img src="../assets/images/' + cw.image + '" alt="' + cw.name + '">';
            } else {
                sw.innerHTML = '<div class="colorway-swatch-placeholder">👟</div>';
            }
            sw.addEventListener('click', function() {
                cwPick.querySelectorAll('.colorway-swatch').forEach(function(s) { s.classList.remove('active'); });
                sw.classList.add('active');
                activeCwIdx = idx;
                cwLbl.textContent = cw.name;
                renderSizes(idx);
            });
            cwPick.appendChild(sw);
        });
        cwLbl.textContent = shoe.colorways[activeCwIdx].name;
    } else {
        cwRow.style.display = 'none';
    }

    renderSizes(activeCwIdx);
    modal.classList.add('active');
}

function closeSizePicker() {
    const modal = document.getElementById('sizePickerModal');
    if (modal) modal.classList.remove('active');
}
</script>
</body>
</html>