<?php
require_once '../includes/header.php';
require_once '../core/functions.php';

// Handle New Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $type = sanitize($_POST['leave_type']);
    $start = sanitize($_POST['start_date']);
    $end = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);

    if ($start > $end) {
        $error = "Start date cannot be after end date.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO student_leaves (user_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $type, $start, $end, $reason]);
        $success = "Leave application submitted successfully.";
    }
}

// Fetch History
$leaves = $pdo->prepare("SELECT * FROM student_leaves WHERE user_id = ? ORDER BY created_at DESC");
$leaves->execute([$_SESSION['user_id']]);
$myLeaves = $leaves->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Apply for Leave / Gate Pass</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success small"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="Night Out">Night Out</option>
                            <option value="Home Visit">Home Visit (Weekend)</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">From</label>
                            <input type="date" name="start_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">To</label>
                            <input type="date" name="end_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason / Destination</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Going to home in Lahore..." required></textarea>
                    </div>
                    <button type="submit" name="apply_leave" class="btn btn-primary w-100">Submit Application</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title">My Applications</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Type</th><th>Dates</th><th>Status</th><th>Remarks</th></tr></thead>
                    <tbody>
                        <?php foreach($myLeaves as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['leave_type']) ?></td>
                            <td>
                                <?= date('d M', strtotime($l['start_date'])) ?> - <?= date('d M', strtotime($l['end_date'])) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($l['reason']) ?></small>
                            </td>
                            <td>
                                <?php if($l['status'] == 'approved'): ?><span class="badge bg-success">Approved</span>
                                <?php elseif($l['status'] == 'rejected'): ?><span class="badge bg-danger">Rejected</span>
                                <?php else: ?><span class="badge bg-warning text-dark">Pending</span><?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($l['admin_remarks'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>