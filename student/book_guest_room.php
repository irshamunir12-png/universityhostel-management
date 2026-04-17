<?php
require_once '../includes/header.php';
require_once '../core/functions.php';

// Handle New Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_stay'])) {
    $name = sanitize($_POST['visitor_name']);
    $cnic = sanitize($_POST['visitor_cnic']);
    $relation = sanitize($_POST['relation']);
    $start = sanitize($_POST['start_date']);
    $end = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);

    if ($start > $end) {
        $error = "Start date cannot be after end date.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO visitor_stay_requests (user_id, visitor_name, visitor_cnic, relation, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $name, $cnic, $relation, $start, $end, $reason]);
        $success = "Guest stay request submitted successfully.";
    }
}

// Handle Cancel Request
if (isset($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    // Only allow cancelling pending requests to prevent deleting history
    $stmt = $pdo->prepare("DELETE FROM visitor_stay_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$id, $_SESSION['user_id']]);
    echo "<script>window.location.href='book_guest_room.php?msg=cancelled';</script>";
    exit;
}

// Fetch My Requests
$requests = $pdo->prepare("SELECT * FROM visitor_stay_requests WHERE user_id = ? ORDER BY created_at DESC");
$requests->execute([$_SESSION['user_id']]);
$myRequests = $requests->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Request Guest Stay</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success small"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?><div class="alert alert-info small">Request cancelled.</div><?php endif; ?>
                
                <form method="post">
                    <div class="mb-3"><label>Visitor Name</label><input type="text" name="visitor_name" class="form-control" required></div>
                    <div class="mb-3"><label>CNIC / ID No</label><input type="text" name="visitor_cnic" class="form-control" required></div>
                    <div class="mb-3">
                        <label>Relation</label>
                        <select name="relation" class="form-select">
                            <option value="Parent">Parent (Father/Mother)</option>
                            <option value="Sibling">Sibling (Brother/Sister)</option>
                            <option value="Relative">Other Relative</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>From</label><input type="date" name="start_date" class="form-control" min="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-6 mb-3"><label>To</label><input type="date" name="end_date" class="form-control" min="<?= date('Y-m-d') ?>" required></div>
                    </div>
                    <div class="mb-3"><label>Reason</label><textarea name="reason" class="form-control" rows="2" required placeholder="e.g. Father visiting for weekend, Brother staying for convocation"></textarea></div>
                    <button type="submit" name="request_stay" class="btn btn-primary w-100">Submit Request</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title">My Guest History</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Visitor</th><th>Dates</th><th>Status</th><th>Remarks</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($myRequests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['visitor_name']) ?> <small class="text-muted">(<?= $r['relation'] ?>)</small></td>
                            <td><?= date('d M', strtotime($r['start_date'])) ?> - <?= date('d M', strtotime($r['end_date'])) ?></td>
                            <td><?php if($r['status'] == 'approved'): ?><span class="badge bg-success">Approved</span><?php elseif($r['status'] == 'rejected'): ?><span class="badge bg-danger">Rejected</span><?php else: ?><span class="badge bg-warning text-dark">Pending</span><?php endif; ?></td>
                            <td><small><?= htmlspecialchars($r['admin_remarks'] ?? '-') ?></small></td>
                            <td>
                                <?php if($r['status'] == 'pending'): ?>
                                    <a href="?cancel=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this request?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>