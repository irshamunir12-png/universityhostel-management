<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Add/Update Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
    $id = (int)$_POST['item_id'];
    $name = sanitize($_POST['item_name']);
    $cat = sanitize($_POST['category']);
    $qty = (int)$_POST['quantity'];
    $loc = sanitize($_POST['location']);
    $cond = $_POST['item_condition'];

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, category = ?, quantity = ?, location = ?, item_condition = ? WHERE id = ?");
        $stmt->execute([$name, $cat, $qty, $loc, $cond, $id]);
        $success = "Item updated successfully.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO inventory (item_name, category, quantity, location, item_condition) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $cat, $qty, $loc, $cond]);
        $success = "Item added to inventory.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM inventory WHERE id = ?")->execute([(int)$_GET['delete']]);
    echo "<script>window.location.href='manage_inventory.php';</script>";
}

// Fetch Inventory
$items = $pdo->query("SELECT * FROM inventory ORDER BY category, item_name")->fetchAll();
?>
<style>
    /* Hide default dashboard header */
    .app-content-header, .content-header, .breadcrumb { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .desktop-app-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-top: 10px;
        border: none;
    }
    .app-header-teal {
        background: linear-gradient(to right, #2ecc71, #1abc9c); !important;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
    }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    
    .app-title-center { font-weight: 850; font-size: 1.8rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    
    .btn-app-home {
        background: white;
        color: #198653 !important;
        font-weight: bold;
        border-radius: 15px;
        padding: 5px 20px;
        text-decoration: none;
        transition: 0.3s;
        width: 120px;
        text-align: center;
    }
    .btn-app-home:hover { background: #f8f9fa; transform: scale(1.05); }

    .form-section-app { padding: 30px; background: #fff; }
    .underline-input {
        border: none;
        border-bottom: 2px solid #eee;
        border-radius: 0;
        padding: 8px 0;
        background: transparent;
        transition: 0.3s;
        font-weight: 600;
        font-size: 1.15rem;
        width: 100%;
    }
    .underline-input:focus { outline: none; border-bottom-color: #20c997; }

    .btn-save-green { background-color: #198754; color: white; border-radius: 25px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-save-green:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3); }

    .grid-table { border: 1px solid #eee; }
    .grid-table th { background: #fdfbff; color: #555; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; }
    .grid-table td { vertical-align: middle; padding: 18px 15px; border-bottom: 1px solid #f8f9fa; font-size: 1.05rem; }
    
    .stats-label { font-size: 0.9rem; color: #666; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 8px; }
    .nav-pills .nav-link.active { background-color: #20c997 !important; color: white !important; box-shadow: 0 4px 10px rgba(32, 201, 151, 0.3); }
    .nav-pills .nav-link { color: #666; font-weight: 700; }
</style>

<div class="desktop-app-card">
    <div class="app-header-teal">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="app-title-center">Inventory & Assets</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="form-section-app">
        <!-- Navigation Options -->
        <ul class="nav nav-pills mb-5 justify-content-center bg-light p-2 rounded-pill shadow-sm d-inline-flex" style="margin-left: auto; margin-right: auto; display: flex !important;">
            <li class="nav-item"><a class="nav-link active rounded-pill px-4" href="manage_inventory.php"><i class="bi bi-boxes"></i> GENERAL STOCK</a></li>
            <li class="nav-item"><a class="nav-link rounded-pill px-4" href="manage_assets.php"><i class="bi bi-upc-scan"></i> TRACKABLE ASSETS</a></li>
        </ul>

        <?php if(isset($success)): ?><div class="alert alert-success rounded-3"><?= $success ?></div><?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="p-4 bg-light border-bottom"><h6 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i> <span id="formTitle">ADD TO GENERAL STOCK</span></h6></div>
            <div class="p-4 bg-white">
                <form method="post" id="inventoryForm" class="row g-4 align-items-end">
                    <input type="hidden" name="item_id" id="edit_item_id" value="0">
                    <div class="col-md-3"><label class="stats-label">ITEM NAME</label><input type="text" name="item_name" class="form-control underline-input" placeholder="e.g. Ceiling Fan" required></div>
                    <div class="col-md-2"><label class="stats-label">CATEGORY</label><select name="category" class="form-select underline-input"><option value="Furniture">Furniture</option><option value="Electrical">Electrical</option><option value="Plumbing">Plumbing</option><option value="Kitchen">Kitchen</option><option value="Other">Other</option></select></div>
                    <div class="col-md-2"><label class="stats-label">QUANTITY</label><input type="number" name="quantity" class="form-control underline-input" value="1" min="1" required></div>
                    <div class="col-md-2"><label class="stats-label">LOCATION</label><input type="text" name="location" class="form-control underline-input" placeholder="e.g. Store Room"></div>
                    <div class="col-md-2"><label class="stats-label">CONDITION</label><select name="item_condition" class="form-select underline-input"><option value="New">New</option><option value="Good" selected>Good</option><option value="Repair Needed">Repair Needed</option><option value="Damaged">Damaged</option></select></div>
                    <div class="col-md-1"><button type="submit" name="save_item" id="submitBtn" class="btn btn-save-green w-100 shadow-sm"><i class="bi bi-plus-lg"></i></button></div>
                    <div class="col-md-1" id="cancelBtnCol" style="display:none;"><button type="reset" onclick="resetInventoryForm()" class="btn btn-secondary w-100 shadow-sm"><i class="bi bi-x-lg"></i></button></div>
                </form>
            </div>
        </div>

        <div class="table-responsive rounded-3 shadow-sm" style="max-height: 500px; overflow-y: auto;">
            <table class="table grid-table mb-0">
                <thead class="sticky-top"><tr><th>Category</th><th>Item Name</th><th>Qty</th><th>Location</th><th>Condition</th><th class="text-center">Action</th></tr></thead>
                <tbody>
                    <?php foreach($items as $i): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($i['category']) ?></span></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($i['item_name']) ?></td>
                        <td><span class="fw-bold text-primary"><?= $i['quantity'] ?></span></td>
                        <td class="small"><?= htmlspecialchars($i['location']) ?></td>
                        <td>
                            <?php if($i['item_condition'] == 'Good' || $i['item_condition'] == 'New'): ?><span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> <?= $i['item_condition'] ?></span>
                            <?php elseif($i['item_condition'] == 'Repair Needed'): ?><span class="text-warning small fw-bold"><i class="bi bi-tools"></i> <?= $i['item_condition'] ?></span>
                            <?php else: ?><span class="text-danger small fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> <?= $i['item_condition'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm text-primary border-0" onclick="editItem('<?= $i['id'] ?>', '<?= addslashes($i['item_name']) ?>', '<?= $i['category'] ?>', '<?= $i['quantity'] ?>', '<?= addslashes($i['location']) ?>', '<?= $i['item_condition'] ?>')"><i class="bi bi-pencil-square"></i></button>
                            <a href="?delete=<?= $i['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Delete item?')"><i class="bi bi-trash3-fill"></i></a>
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

    function editItem(id, name, cat, qty, loc, cond) {
        document.getElementById('edit_item_id').value = id;
        document.getElementsByName('item_name')[0].value = name;
        document.getElementsByName('category')[0].value = cat;
        document.getElementsByName('quantity')[0].value = qty;
        document.getElementsByName('location')[0].value = loc;
        document.getElementsByName('item_condition')[0].value = cond;
        document.getElementById('formTitle').innerText = "UPDATE ITEM DETAILS";
        document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-lg"></i>';
        document.getElementById('cancelBtnCol').style.display = 'block';
    }

    function resetInventoryForm() {
        document.getElementById('edit_item_id').value = "0";
        document.getElementById('formTitle').innerText = "ADD TO GENERAL STOCK";
        document.getElementById('submitBtn').innerHTML = '<i class="bi bi-plus-lg"></i>';
        document.getElementById('cancelBtnCol').style.display = 'none';
    }
</script>
<?php require_once '../../includes/footer.php'; ?>