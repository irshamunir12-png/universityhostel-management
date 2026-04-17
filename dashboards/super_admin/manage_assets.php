<?php
require_once '../../core/session.php'; 
require_once '../../core/functions.php';

// Handle Category Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $cat_name = sanitize($_POST['category_name']);
        if (!empty($cat_name)) {
            $pdo->prepare("INSERT INTO asset_categories (category_name) VALUES (?)")->execute([$cat_name]);
            header("Location: manage_assets.php?success_msg=Category added successfully");
            exit;
        }
    }
    // Handle Asset Actions
    if (isset($_POST['save_asset'])) {
        $id = (int)$_POST['asset_id'];
        $asset_name = sanitize($_POST['asset_name']);
        $asset_tag = sanitize($_POST['asset_tag']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $purchase_price = (isset($_POST['purchase_price']) && $_POST['purchase_price'] !== '') ? $_POST['purchase_price'] : 0;
        $status = $_POST['status'] ?? 'available';
        
        // 1. Check if the Asset Tag is already taken by another item
        $checkTag = $pdo->prepare("SELECT id FROM assets WHERE asset_tag = ? AND id != ?");
        $checkTag->execute([$asset_tag, $id]);

        if ($checkTag->rowCount() > 0) {
            $error = "Conflict: The Asset Tag '$asset_tag' is already assigned to another item. Please use a unique Tag ID.";
        } else {
            try {
                if ($id > 0) {
                    // UPDATE
                    $stmt = $pdo->prepare("UPDATE assets SET asset_name = ?, asset_tag = ?, category_id = ?, purchase_date = ?, purchase_price = ?, status = ? WHERE id = ?");
                    $stmt->execute([$asset_name, $asset_tag, $category_id, $purchase_date, $purchase_price, $status, $id]);
                    $msg = "Asset updated successfully.";
                } else {
                    // INSERT
                    $stmt = $pdo->prepare("INSERT INTO assets (asset_name, asset_tag, category_id, purchase_date, purchase_price) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$asset_name, $asset_tag, $category_id, $purchase_date, $purchase_price]);
                    $msg = "Asset '$asset_name' added successfully.";
                }
                header("Location: manage_assets.php?success_msg=" . urlencode($msg));
                exit;
            } catch (Exception $e) {
                $error = "Database System Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete_asset'])) {
    $id = (int)$_GET['delete_asset'];
    $status = $pdo->query("SELECT status FROM assets WHERE id = $id")->fetchColumn();
    if ($status === 'in_use') {
        $error = "Cannot delete an asset that is currently allocated.";
    } else {
        $pdo->prepare("DELETE FROM assets WHERE id = ?")->execute([$id]);
        header("Location: manage_assets.php?success_msg=Asset deleted");
        exit;
    }
}

// --- Data Fetching ---
$categories = $pdo->query("SELECT * FROM asset_categories ORDER BY category_name")->fetchAll();
$assets = $pdo->query("SELECT a.*, ac.category_name FROM assets a LEFT JOIN asset_categories ac ON a.category_id = ac.id ORDER BY a.created_at DESC")->fetchAll();

// 2. Header ab yahan include karein (Logic khatam hone ke baad)
require_once '../../includes/header.php';
?>
<style>
    .app-content-header, .content-header, .breadcrumb { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .desktop-app-card { background: #ffffff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden; margin-top: 10px; border: none; }
    .app-header-teal { background: linear-gradient(to right, #2ecc71, #1abc9c) !important; padding: 15px 25px; display: flex; align-items: center; justify-content: space-between; color: white; }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    .app-title-center { font-weight: 850; font-size: 1.8rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    .btn-app-home { background: white; color: #20c997 !important; font-weight: bold; border-radius: 15px; padding: 5px 20px; text-decoration: none; transition: 0.3s; width: 120px; text-align: center; }
    .btn-app-home:hover { background: #f8f9fa; transform: scale(1.05); }

    .form-section-app { padding: 30px; background: #fff; }
    .underline-input { border: none !important; border-bottom: 2px solid #eee !important; border-radius: 0 !important; padding: 12px 5px !important; background: transparent !important; transition: 0.3s; font-weight: 600; font-size: 1.2rem; width: 100%; box-shadow: none !important; }
    .underline-input:focus { outline: none; border-bottom-color: #20c997; }

    .btn-save-green { background-color: #198754; color: white; border-radius: 25px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-save-green:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3); }

    .grid-table { border: 1px solid #eee; }
    .grid-table th { background: #fdfbff; color: #555; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; }
    .grid-table td { vertical-align: middle; padding: 18px 15px; border-bottom: 1px solid #f8f9fa; font-size: 1.05rem; }
    
    .stats-label { font-size: 0.9rem; color: #666; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 8px; }
    .nav-pills .nav-link.active { background-color: #20c997 !important; color: white !important; box-shadow: 0 4px 10px rgba(32, 201, 151, 0.3); }
    .nav-pills .nav-link { color: #666; font-weight: 700; }
    
    /* Remove card borders for a floating look */
    .minimal-container { border: none !important; background: transparent !important; box-shadow: none !important; }

    /* Row highlight on hover */
    .grid-table tbody tr:hover {
        background-color: rgba(32, 201, 151, 0.05) !important;
        transition: 0.2s;
    }
</style>

<div class="desktop-app-card">
    <div class="app-header-teal">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="app-title-center">Assets Tracking</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="form-section-app">
        <ul class="nav nav-pills mb-5 justify-content-center bg-light p-2 rounded-pill shadow-sm d-inline-flex" style="display: flex !important; margin: 0 auto;">
            <li class="nav-item"><a class="nav-link rounded-pill px-4" href="manage_inventory.php"><i class="bi bi-boxes"></i> GENERAL STOCK</a></li>
            <li class="nav-item"><a class="nav-link active rounded-pill px-4" href="manage_assets.php"><i class="bi bi-upc-scan"></i> TRACKABLE ASSETS</a></li>
            <li class="nav-item"><a class="nav-link rounded-pill px-4" href="allocate_assets.php"><i class="bi bi-person-check"></i> ALLOCATIONS</a></li>
        </ul>

        <?php if(isset($error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $error ?></div><?php endif; ?>
        <?php if(isset($_GET['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['success_msg']) ?></div><?php endif; ?>
        <?php if(isset($success)): ?><div class="alert alert-success rounded-3 auto-hide"><?= $success ?></div><?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card minimal-container mb-4">
                    <div class="p-4 bg-light border-bottom"><h6 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i> <span id="assetFormTitle">REGISTER NEW TRACKABLE ASSET</span></h6></div>
                    <div class="p-4 bg-white">
                        <form method="post" id="assetForm">
                            <input type="hidden" name="asset_id" id="edit_asset_id" value="0">
                            <div class="row g-4">
                                <div class="col-md-6"><label class="stats-label">ASSET NAME</label><input type="text" name="asset_name" class="form-control underline-input" placeholder="e.g. Office Chair" required></div>
                                <div class="col-md-6"><label class="stats-label">ASSET TAG (UNIQUE ID)</label><input type="text" name="asset_tag" class="form-control underline-input" placeholder="e.g. CHR-001" required></div>
                                <div class="col-md-4">
                                    <label class="stats-label">CATEGORY</label>
                                    <select name="category_id" class="form-select underline-input" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4"><label class="stats-label">PURCHASE DATE</label><input type="date" name="purchase_date" class="form-control underline-input"></div>
                                <div class="col-md-4"><label class="stats-label">PRICE (Rs)</label><input type="number" step="0.01" name="purchase_price" class="form-control underline-input"></div>
                                <div class="col-md-4" id="status_field" style="display:none;">
                                    <label class="stats-label">STATUS</label>
                                    <select name="status" class="form-select underline-input">
                                        <option value="available">AVAILABLE</option>
                                        <option value="in_use">IN USE</option>
                                        <option value="damaged">DAMAGED</option>
                                        <option value="repairing">UNDER REPAIR</option>
                                    </select>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="reset" id="assetResetBtn" onclick="resetAssetForm()" class="btn btn-secondary rounded-pill px-4 shadow-sm me-2" style="display:none;">CANCEL</button>
                                    <button type="submit" name="save_asset" id="assetSubmitBtn" class="btn btn-save-green px-5 shadow-sm">REGISTER ASSET</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card minimal-container mb-4">
                    <div class="p-4 bg-light border-bottom"><h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tag-fill text-secondary me-2"></i> QUICK CATEGORY</h6></div>
                    <div class="p-4 bg-white">
                        <form method="post">
                            <label class="stats-label">NEW CATEGORY NAME</label>
                            <input type="text" name="category_name" class="form-control underline-input mb-3" placeholder="e.g. Laptops" required>
                            <button type="submit" name="add_category" class="btn btn-dark w-100 rounded-pill fw-bold py-2 shadow-sm">ADD</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Row (Dropdowns) -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-3">
                <select id="filterCategory" class="form-select underline-input" onchange="filterTable()">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['category_name']) ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterStatus" class="form-select underline-input" onchange="filterTable()">
                    <option value="">All Statuses</option>
                    <option value="AVAILABLE">AVAILABLE</option>
                    <option value="IN_USE">IN_USE</option>
                    <option value="DAMAGED">DAMAGED</option>
                    <option value="REPAIRING">UNDER REPAIR</option>
                </select>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-sm border" style="max-height: 500px; overflow-y: auto;">
            <table class="table grid-table mb-0">
                <thead class="sticky-top"><tr><th>Asset Details</th><th>Tag ID</th><th>Category</th><th>Status</th><th class="text-center">Action</th></tr></thead>
                <tbody>
                    <?php foreach($assets as $asset): 
                        $status_class = 'bg-secondary';
                        if ($asset['status'] == 'available') $status_class = 'bg-success';
                        if ($asset['status'] == 'in_use') $status_class = 'bg-warning text-dark';
                        if ($asset['status'] == 'damaged') $status_class = 'bg-danger';
                        if ($asset['status'] == 'repairing') $status_class = 'bg-info text-dark';
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($asset['asset_name']) ?></div>
                            <small class="text-muted">Date: <?= $asset['purchase_date'] ? date('d M Y', strtotime($asset['purchase_date'])) : 'N/A' ?></small>
                            <div class="small text-primary fw-bold">Price: Rs. <?= number_format($asset['purchase_price'], 2) ?></div>
                        </td>
                        <td><span class="badge bg-dark px-3"><?= htmlspecialchars($asset['asset_tag']) ?></span></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($asset['category_name'] ?? 'Uncategorized') ?></span></td>
                        <td><span class="badge <?= $status_class ?> px-3"><?= strtoupper($asset['status']) ?></span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm text-primary border-0" onclick="editAsset('<?= $asset['id'] ?>', '<?= addslashes($asset['asset_name']) ?>', '<?= addslashes($asset['asset_tag']) ?>', '<?= $asset['category_id'] ?>', '<?= $asset['purchase_date'] ?>', '<?= $asset['purchase_price'] ?>', '<?= $asset['status'] ?>')"><i class="bi bi-pencil-square"></i></button>
                            <?php if($asset['status'] !== 'in_use'): ?>
                                <a href="?delete_asset=<?= $asset['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Permanently delete this asset?')"><i class="bi bi-trash3-fill"></i></a>
                            <?php else: ?>
                                <i class="bi bi-lock-fill text-muted" title="Allocated assets cannot be deleted"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updateClockApp() {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('live-clock-app').innerText = now.toLocaleDateString('en-US', options);
    }
    setInterval(updateClockApp, 1000);
    updateClockApp();

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => el.style.display = 'none');
    }, 5000);

    function filterTable() {
        let cat = document.getElementById('filterCategory').value.toUpperCase();
        let stat = document.getElementById('filterStatus').value.toUpperCase();
        let rows = document.querySelectorAll('.grid-table tbody tr');
        
        rows.forEach(row => {
            let rowCat = row.cells[2].innerText.toUpperCase();
            let rowStat = row.cells[3].innerText.toUpperCase();
            
            let show = (cat === "" || rowCat.includes(cat)) && (stat === "" || rowStat.includes(stat));
            row.style.display = show ? "" : "none";
        });
    }

    function editAsset(id, name, tag, cat, date, price, status) {
        document.getElementById('edit_asset_id').value = id;
        document.getElementsByName('asset_name')[0].value = name;
        document.getElementsByName('asset_tag')[0].value = tag;
        document.getElementsByName('category_id')[0].value = cat;
        document.getElementsByName('purchase_date')[0].value = date;
        document.getElementsByName('purchase_price')[0].value = price;
        document.getElementsByName('status')[0].value = status;
        document.getElementById('assetFormTitle').innerText = "UPDATE ASSET DETAILS";
        document.getElementById('assetSubmitBtn').innerText = "UPDATE ASSET";
        document.getElementById('status_field').style.display = 'block';
        document.getElementById('assetResetBtn').style.display = 'inline-block';
    }

    function resetAssetForm() {
        document.getElementById('edit_asset_id').value = "0";
        document.getElementById('assetFormTitle').innerText = "REGISTER NEW TRACKABLE ASSET";
        document.getElementById('assetSubmitBtn').innerText = "REGISTER ASSET";
        document.getElementById('status_field').style.display = 'none';
        document.getElementById('assetResetBtn').style.display = 'none';
    }
</script>
<?php require_once '../../includes/footer.php'; ?>