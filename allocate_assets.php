<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Asset Return (Deallocation)
if (isset($_GET['return_asset'])) {
    $alloc_id = (int)$_GET['return_asset'];
    $condition = sanitize($_GET['condition'] ?? 'Returned');

    // Start transaction
    $pdo->beginTransaction();
    try {
        // 1. Mark allocation as inactive
        $stmt = $pdo->prepare("UPDATE asset_allocations SET is_active = 0, return_date = CURDATE(), condition_on_return = ? WHERE id = ?");
        $stmt->execute([$condition, $alloc_id]);

        // 2. Get asset_id from allocation
        $asset_id = $pdo->query("SELECT asset_id FROM asset_allocations WHERE id = $alloc_id")->fetchColumn();

        // 3. Update asset status back to 'available'
        if ($asset_id) {
            $pdo->prepare("UPDATE assets SET status = 'available' WHERE id = ?")->execute([$asset_id]);
        }

        $pdo->commit();
        $success = "Asset has been marked as returned.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to return asset: " . $e->getMessage();
    }
}

// Handle Allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate_asset'])) {
    $asset_id = (int)$_POST['asset_id'];
    $allocated_to_type = $_POST['allocated_to_type'];
    $allocated_to_id = ($allocated_to_type === 'student') ? (int)$_POST['user_id'] : (int)$_POST['room_id'];
    $issue_date = sanitize($_POST['issue_date']);
    $condition_on_issue = sanitize($_POST['condition_on_issue']);
    $notes = sanitize($_POST['notes']);

    if (empty($asset_id) || empty($allocated_to_id)) {
        $error = "Please select an asset and a recipient (student or room).";
    } else {
        // Start transaction
        $pdo->beginTransaction();
        try {
            // 1. Insert new allocation record
            $stmt = $pdo->prepare("INSERT INTO asset_allocations (asset_id, allocated_to_type, allocated_to_id, issue_date, condition_on_issue, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$asset_id, $allocated_to_type, $allocated_to_id, $issue_date, $condition_on_issue, $notes]);

            // 2. Update the asset's status to 'in_use'
            $pdo->prepare("UPDATE assets SET status = 'in_use' WHERE id = ?")->execute([$asset_id]);

            $pdo->commit();
            $success = "Asset allocated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Allocation failed: " . $e->getMessage();
        }
    }
}

// --- Data Fetching for the page ---

// 1. Fetch available assets
$available_assets = $pdo->query("SELECT id, asset_name, asset_tag FROM assets WHERE status = 'available' ORDER BY asset_name")->fetchAll();

// 2. Fetch all active students
$students = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' AND is_active = 1 ORDER BY name")->fetchAll();

// 3. Fetch all rooms
$rooms = $pdo->query("SELECT id, room_no, building, block FROM rooms WHERE status = 'active' ORDER BY room_no")->fetchAll();

// 4. Fetch current active allocations
$allocations = $pdo->query("
    SELECT 
        aa.id, aa.issue_date, aa.condition_on_issue,
        a.asset_name, a.asset_tag,
        aa.allocated_to_type,
        CASE 
            WHEN aa.allocated_to_type = 'student' THEN u.name
            WHEN aa.allocated_to_type = 'room' THEN CONCAT('Room: ', r.room_no)
        END as allocated_to_name,
        CASE 
            WHEN aa.allocated_to_type = 'student' THEN u.registration_no
            WHEN aa.allocated_to_type = 'room' THEN CONCAT(r.building, ' - ', r.block)
        END as allocated_to_detail
    FROM asset_allocations aa
    JOIN assets a ON aa.asset_id = a.id
    LEFT JOIN users u ON aa.allocated_to_id = u.id AND aa.allocated_to_type = 'student'
    LEFT JOIN rooms r ON aa.allocated_to_id = r.id AND aa.allocated_to_type = 'room'
    WHERE aa.is_active = 1
    ORDER BY aa.created_at DESC
")->fetchAll();

?>

<div class="row">
    <!-- Left Column: Allocation Form -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-person-bounding-box"></i> Allocate New Asset</h3></div>
            <div class="card-body">
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="asset_id" class="form-label">Available Asset</label>
                        <select name="asset_id" id="asset_id" class="form-select" required>
                            <option value="">Select an asset...</option>
                            <?php foreach($available_assets as $asset): ?><option value="<?= $asset['id'] ?>"><?= htmlspecialchars($asset['asset_name']) ?> (<?= htmlspecialchars($asset['asset_tag']) ?>)</option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Allocate To:</label>
                        <div class="form-check"><input class="form-check-input" type="radio" name="allocated_to_type" id="to_student" value="student" checked onchange="toggleRecipient()"><label class="form-check-label" for="to_student">Student</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="allocated_to_type" id="to_room" value="room" onchange="toggleRecipient()"><label class="form-check-label" for="to_room">Room</label></div>
                    </div>
                    <div id="student_recipient" class="mb-3">
                        <label for="user_id" class="form-label">Student</label>
                        <select name="user_id" id="user_id" class="form-select"><option value="">Select a student...</option><?php foreach($students as $student): ?><option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['registration_no']) ?>)</option><?php endforeach; ?></select>
                    </div>
                    <div id="room_recipient" class="mb-3" style="display:none;">
                        <label for="room_id" class="form-label">Room</label>
                        <select name="room_id" id="room_id" class="form-select"><option value="">Select a room...</option><?php foreach($rooms as $room): ?><option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['room_no']) ?> (<?= htmlspecialchars($room['building']) ?>)</option><?php endforeach; ?></select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="issue_date" class="form-label">Issue Date</label><input type="date" name="issue_date" id="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-6 mb-3"><label for="condition_on_issue" class="form-label">Condition</label><input type="text" name="condition_on_issue" id="condition_on_issue" class="form-control" value="Good"></div>
                    </div>
                    <div class="mb-3"><label for="notes" class="form-label">Notes</label><textarea name="notes" id="notes" class="form-control" rows="2"></textarea></div>
                    <button type="submit" name="allocate_asset" class="btn btn-primary w-100">Allocate Asset</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Current Allocations -->
    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-list-check"></i> Current Asset Allocations</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead><tr><th>Asset</th><th>Allocated To</th><th>Issue Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if(empty($allocations)): ?><tr><td colspan="4" class="text-center text-muted">No assets are currently allocated.</td></tr><?php endif; ?>
                            <?php foreach($allocations as $alloc): ?>
                                <tr>
                                    <td><span class="fw-bold"><?= htmlspecialchars($alloc['asset_name']) ?></span><br><small class="text-muted"><?= htmlspecialchars($alloc['asset_tag']) ?></small></td>
                                    <td><span class="fw-bold"><?= htmlspecialchars($alloc['allocated_to_name']) ?></span><br><small class="text-muted"><?= htmlspecialchars($alloc['allocated_to_detail']) ?></small></td>
                                    <td><?= date('d M Y', strtotime($alloc['issue_date'])) ?></td>
                                    <td><a href="?return_asset=<?= $alloc['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to mark this asset as returned?')">Return</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRecipient() {
    if (document.getElementById('to_student').checked) {
        document.getElementById('student_recipient').style.display = 'block';
        document.getElementById('room_recipient').style.display = 'none';
        document.getElementById('user_id').required = true;
        document.getElementById('room_id').required = false;
    } else {
        document.getElementById('student_recipient').style.display = 'none';
        document.getElementById('room_recipient').style.display = 'block';
        document.getElementById('user_id').required = false;
        document.getElementById('room_id').required = true;
    }
}
// Initial call to set the correct state on page load
toggleRecipient();
</script>

<?php require_once '../../includes/footer.php'; ?>