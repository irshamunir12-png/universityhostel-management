<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['request_id'];
    $status = $_POST['status']; // 'approved' or 'rejected'
    $remarks = sanitize($_POST['admin_remarks']);
    
    $stmt = $pdo->prepare("UPDATE visitor_stay_requests SET status = ?, admin_remarks = ? WHERE id = ?");
    $stmt->execute([$status, $remarks, $id]);
    $success = "Request has been " . ucfirst($status) . ".";
}

// Filter Logic
$filter = $_GET['status'] ?? 'pending';

// Fetch Requests with Student Info
$sql = "SELECT r.*, u.name as student_name, u.registration_no, rm.room_no, rm.building 
        FROM visitor_stay_requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1)
        LEFT JOIN rooms rm ON ra.room_id = rm.id
        WHERE r.status = ? 
        ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$filter]);
$requests = $stmt->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="bi bi-house-heart"></i> Guest Stay Requests</h3>
        <div class="btn-group btn-group-sm">
            <a href="?status=pending" class="btn btn-outline-primary <?= $filter=='pending'?'active':'' ?>">Pending</a>
            <a href="?status=approved" class="btn btn-outline-success <?= $filter=='approved'?'active':'' ?>">Approved</a>
            <a href="?status=rejected" class="btn btn-outline-danger <?= $filter=='rejected'?'active':'' ?>">Rejected</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Guest Details</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <?php if($filter == 'pending'): ?><th>Action</th><?php else: ?><th>Remarks</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if(count($requests) == 0): ?>
                    <tr><td colspan="5" class="text-center text-muted p-4">No <?= $filter ?> requests found.</td></tr>
                <?php endif; ?>

                <?php foreach($requests as $r): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($r['student_name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($r['registration_no']) ?></small><br>
                        <span class="badge bg-info"><?= $r['room_no'] ? $r['building'].'-'.$r['room_no'] : 'No Room' ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars($r['visitor_name']) ?><br>
                        <small>CNIC: <?= htmlspecialchars($r['visitor_cnic']) ?></small><br>
                        <span class="badge bg-secondary"><?= htmlspecialchars($r['relation']) ?></span>
                    </td>
                    <td>
                        <?= date('d M', strtotime($r['start_date'])) ?> <i class="bi bi-arrow-right"></i> <?= date('d M', strtotime($r['end_date'])) ?>
                    </td>
                    <td><small><?= htmlspecialchars($r['reason']) ?></small></td>
                    
                    <?php if($filter == 'pending'): ?>
                    <td>
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <input type="text" name="admin_remarks" class="form-control form-control-sm" placeholder="Remarks..." required>
                            <button type="submit" name="update_status" value="approved" class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                            <button type="submit" name="update_status" value="rejected" class="btn btn-sm btn-danger" title="Reject"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </td>
                    <?php else: ?>
                    <td><small class="text-muted"><?= htmlspecialchars($r['admin_remarks']) ?></small></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>