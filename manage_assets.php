<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Category Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $cat_name = sanitize($_POST['category_name']);
        if (!empty($cat_name)) {
            $pdo->prepare("INSERT INTO asset_categories (category_name) VALUES (?)")->execute([$cat_name]);
            $success = "Category '$cat_name' added.";
        }
    }
    // Handle Asset Actions
    if (isset($_POST['add_asset'])) {
        $asset_name = sanitize($_POST['asset_name']);
        $asset_tag = sanitize($_POST['asset_tag']);
        $category_id = (int)$_POST['category_id'];
        $purchase_date = sanitize($_POST['purchase_date']);
        $purchase_price = sanitize($_POST['purchase_price']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO assets (asset_name, asset_tag, category_id, purchase_date, purchase_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$asset_name, $asset_tag, $category_id, $purchase_date, $purchase_price]);
            $success = "Asset '$asset_name' added successfully.";
        } catch (Exception $e) {
            $error = "Failed to add asset. The asset tag might already exist. (" . $e->getMessage() . ")";
        }
    }
}

// Handle Delete
if (isset($_GET['delete_asset'])) {
    $id = (int)$_GET['delete_asset'];
    // We can only delete an asset if it's not 'in_use'
    $status = $pdo->query("SELECT status FROM assets WHERE id = $id")->fetchColumn();
    if ($status === 'in_use') {
        $error = "Cannot delete an asset that is currently allocated.";
    } else {
        $pdo->prepare("DELETE FROM assets WHERE id = ?")->execute([$id]);
        $success = "Asset deleted.";
    }
}

// --- Data Fetching ---
$categories = $pdo->query("SELECT * FROM asset_categories ORDER BY category_name")->fetchAll();
$assets = $pdo->query("
    SELECT a.*, ac.category_name 
    FROM assets a 
    LEFT JOIN asset_categories ac ON a.category_id = ac.id
    ORDER BY a.created_at DESC
")->fetchAll();

?>

<div class="row">
    <!-- Left Column: Add Forms -->
    <div class="col-md-4">
        <!-- Add Asset Form -->
        <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-plus-circle"></i> Add New Asset</h3></div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3"><label class="form-label">Asset Name</label><input type="text" name="asset_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Asset Tag (Unique ID)</label><input type="text" name="asset_tag" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Price</label><input type="number" step="0.01" name="purchase_price" class="form-control"></div>
                    </div>
                    <button type="submit" name="add_asset" class="btn btn-primary mt-3 w-100">Add Asset</button>
                </form>
            </div>
        </div>

        <!-- Add Category Form -->
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-tags"></i> Manage Categories</h3></div>
            <div class="card-body">
                <form method="post" class="d-flex gap-2 mb-3">
                    <input type="text" name="category_name" class="form-control" placeholder="New category name..." required>
                    <button type="submit" name="add_category" class="btn btn-secondary">Add</button>
                </form>
                <ul class="list-group"><?php foreach($categories as $cat): ?><li class="list-group-item"><?= htmlspecialchars($cat['category_name']) ?></li><?php endforeach; ?></ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Asset List -->
    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-boxes"></i> Master Asset List</h3></div>
            <div class="card-body p-0">
                <?php if(isset($error)): ?><div class="alert alert-danger m-3"><?= $error ?></div><?php endif; ?>
                <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Asset</th><th>Tag</th><th>Category</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($assets as $asset): ?>
                                <tr>
                                    <td><?= htmlspecialchars($asset['asset_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($asset['asset_tag']) ?></span></td>
                                    <td><?= htmlspecialchars($asset['category_name']) ?></td>
                                    <td>
                                        <?php 
                                            $status_class = 'text-bg-secondary';
                                            if ($asset['status'] == 'available') $status_class = 'text-bg-success';
                                            if ($asset['status'] == 'in_use') $status_class = 'text-bg-warning';
                                            if ($asset['status'] == 'damaged') $status_class = 'text-bg-danger';
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= ucfirst($asset['status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if($asset['status'] !== 'in_use'): ?><a href="?delete_asset=<?= $asset['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>