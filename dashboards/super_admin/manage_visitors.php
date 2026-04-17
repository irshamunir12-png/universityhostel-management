<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Check-Out Action
if (isset($_GET['checkout'])) {
    $id = (int)$_GET['checkout'];
    $pdo->prepare("UPDATE visitors SET check_out = NOW() WHERE id = ?")->execute([$id]);
    echo "<script>window.location.href='manage_visitors.php?msg=out';</script>";
    exit;
}

// Handle New Visitor Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visitor'])) {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $student_id = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $relation = sanitize($_POST['relation']);
    $purpose = sanitize($_POST['purpose']);

    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO visitors (visitor_name, visitor_phone, student_id, relation, purpose, check_in) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $phone, $student_id, $relation, $purpose]);
        $success = "Visitor Check-In Recorded!";
    } else {
        $error = "Visitor Name is required.";
    }
}

// Fetch Active Visitors (Not Checked Out)
$active_visitors = $pdo->query("
    SELECT v.*, u.name as student_name, u.registration_no 
    FROM visitors v 
    LEFT JOIN users u ON v.student_id = u.id 
    WHERE v.check_out IS NULL 
    ORDER BY v.check_in DESC
")->fetchAll();

// Fetch Recent History (Checked Out)
$history = $pdo->query("
    SELECT v.*, u.name as student_name 
    FROM visitors v 
    LEFT JOIN users u ON v.student_id = u.id 
    WHERE v.check_out IS NOT NULL 
    ORDER BY v.check_in DESC LIMIT 20
")->fetchAll();

// Fetch Students for Dropdown
$students = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
?>

<div class="row">
    <!-- Left Column: New Entry Form -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">New Visitor Entry</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label>Visitor Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone No</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Visiting Student (Optional)</label>
                        <select name="student_id" class="form-select">
                            <option value="">-- General Visit --</option>
                            <?php foreach($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['registration_no'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted">Leave empty for general visitors (e.g. Delivery, Maintenance).</div>
                    </div>
                    <div class="mb-3">
                        <label>Relation</label>
                        <select name="relation" class="form-select">
                            <option value="Parent">Parent</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Friend">Friend</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Purpose</label>
                        <input type="text" name="purpose" class="form-control" placeholder="e.g. Meeting, Delivery">
                    </div>
                    <button type="submit" name="add_visitor" class="btn btn-primary w-100">Check In</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Active Visitors List -->
    <div class="col-md-8">
        <div class="card card-success card-outline">
            <div class="card-header"><h3 class="card-title">Currently Inside Hostel</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Visitor</th><th>Visiting</th><th>Time In</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($active_visitors as $v): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($v['visitor_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($v['visitor_phone']) ?></small>
                            </td>
                            <td>
                                <?= $v['student_name'] ? htmlspecialchars($v['student_name']) : '<span class="badge bg-secondary">General</span>' ?>
                                <br><small class="text-muted"><?= htmlspecialchars($v['relation'] ?? '') ?></small>
                            </td>
                            <td><?= date('h:i A', strtotime($v['check_in'])) ?></td>
                            <td><a href="?checkout=<?= $v['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Mark as Checked Out?')">Check Out</a></td>
                        </tr>
                        <?php endforeach; if(!$active_visitors): ?><tr><td colspan="4" class="text-center text-muted">No visitors currently inside.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>