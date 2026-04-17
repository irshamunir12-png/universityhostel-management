<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Asset Return
if (isset($_GET['return_asset'])) {
    $alloc_id = (int)$_GET['return_asset'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE asset_allocations SET is_active = 0, return_date = CURDATE(), condition_on_return = 'Returned' WHERE id = ?");
        $stmt->execute([$alloc_id]);
        $asset_id = $pdo->query("SELECT asset_id FROM asset_allocations WHERE id = $alloc_id")->fetchColumn();
        if ($asset_id) $pdo->prepare("UPDATE assets SET status = 'available' WHERE id = ?")->execute([$asset_id]);
        $pdo->commit();
        $success = "Asset returned successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate_asset'])) {
    $asset_id = (int)$_POST['asset_id'];
    $allocated_to_type = $_POST['allocated_to_type'];
    $allocated_to_id = ($allocated_to_type === 'student') ? (int)$_POST['user_id'] : (int)$_POST['room_id'];
    $issue_date = sanitize($_POST['issue_date']);
    
    if ($asset_id && $allocated_to_id) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO asset_allocations (asset_id, allocated_to_type, allocated_to_id, issue_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$asset_id, $allocated_to_type, $allocated_to_id, $issue_date]);
            $pdo->prepare("UPDATE assets SET status = 'in_use' WHERE id = ?")->execute([$asset_id]);
            $pdo->commit();
            $success = "Asset allocated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Allocation failed.";
        }
    }
}

// Fetch Data
$available_assets = $pdo->query("SELECT id, asset_name, asset_tag FROM assets WHERE status = 'available' ORDER BY asset_name")->fetchAll();
$students = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' AND is_active = 1 ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT id, room_no, building FROM rooms WHERE status = 'active' ORDER BY room_no")->fetchAll();
$allocations = $pdo->query("
    SELECT aa.*, a.asset_name, a.asset_tag,
        CASE WHEN aa.allocated_to_type = 'student' THEN u.name ELSE CONCAT('Room: ', r.room_no) END as recipient_name
    FROM asset_allocations aa
    JOIN assets a ON aa.asset_id = a.id
    LEFT JOIN users u ON aa.allocated_to_id = u.id
    LEFT JOIN rooms r ON aa.allocated_to_id = r.id
    WHERE aa.is_active = 1 ORDER BY aa.created_at DESC
")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-box-seam"></i> Inventory & Assets Management</h3>
    </div>
    <div class="card-body">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link" href="manage_inventory.php"><i class="bi bi-boxes"></i> General Stock</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_assets.php"><i class="bi bi-upc-scan"></i> Trackable Assets</a></li>
            <li class="nav-item"><a class="nav-link active" href="allocate_assets.php"><i class="bi bi-person-check"></i> Asset Allocation</a></li>
        </ul>

        <div class="row">
            <div class="col-md-4">
                <div class="card card-info card-outline">
                    <div class="card-header"><h3 class="card-title">Issue Asset</h3></div>
                    <div class="card-body">
                        <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                        <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label>Select Asset</label>
                                <select name="asset_id" class="form-select" required>
                                    <option value="">-- Choose Asset --</option>
                                    <?php foreach($available_assets as $a): ?>
                                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['asset_name']) ?> (<?= $a['asset_tag'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Allocate To</label>
                                <div class="d-flex gap-3 mb-2">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="allocated_to_type" id="t_stu" value="student" checked onclick="document.getElementById('div_stu').style.display='block';document.getElementById('div_room').style.display='none';"><label class="form-check-label" for="t_stu">Student</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="allocated_to_type" id="t_room" value="room" onclick="document.getElementById('div_stu').style.display='none';document.getElementById('div_room').style.display='block';"><label class="form-check-label" for="t_room">Room</label></div>
                                </div>
                                <div id="div_stu">
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Select Student --</option>
                                        <?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['registration_no'] ?>)</option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="div_room" style="display:none;">
                                    <select name="room_id" class="form-select">
                                        <option value="">-- Select Room --</option>
                                        <?php foreach($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['room_no']) ?> (<?= $r['building'] ?>)</option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3"><label>Issue Date</label><input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <button type="submit" name="allocate_asset" class="btn btn-primary w-100">Allocate</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-secondary card-outline">
                    <div class="card-header"><h3 class="card-title">Currently Issued Assets</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead><tr><th>Asset</th><th>Issued To</th><th>Date</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($allocations as $alloc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($alloc['asset_name']) ?> <small>(<?= $alloc['asset_tag'] ?>)</small></td>
                                        <td><?= htmlspecialchars($alloc['recipient_name']) ?></td>
                                        <td><?= date('d M Y', strtotime($alloc['issue_date'])) ?></td>
                                        <td><a href="?return_asset=<?= $alloc['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Return this asset?')">Return</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>