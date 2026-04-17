<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['leave_id'];
    $status = $_POST['status'];
    $remarks = sanitize($_POST['remarks']);
    
    $pdo->prepare("UPDATE student_leaves SET status = ?, admin_remarks = ? WHERE id = ?")->execute([$status, $remarks, $id]);
    $success = "Leave status updated.";
}

// Fetch Pending Leaves
$leaves = $pdo->query("
    SELECT sl.*, u.name, u.registration_no, r.room_no 
    FROM student_leaves sl 
    JOIN users u ON sl.user_id = u.id 
    LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1)
    LEFT JOIN rooms r ON ra.room_id = r.id
    ORDER BY FIELD(sl.status, 'pending', 'approved', 'rejected'), sl.created_at DESC
")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title">Leave Applications</h3></div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>
        
        <table class="table table-hover">
            <thead><tr><th>Student</th><th>Leave Details</th><th>Dates</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($leaves as $l): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($l['name']) ?></strong>
                        <br><small><?= htmlspecialchars($l['registration_no']) ?></small>
                        <br><span class="badge bg-secondary">Room: <?= $l['room_no'] ?? 'N/A' ?></span>
                    </td>
                    <td>
                        <span class="fw-bold text-primary"><?= htmlspecialchars($l['leave_type']) ?></span>
                        <p class="mb-0 small"><?= htmlspecialchars($l['reason']) ?></p>
                    </td>
                    <td><?= date('d M', strtotime($l['start_date'])) ?> <br>to<br> <?= date('d M', strtotime($l['end_date'])) ?></td>
                    <td>
                        <?php if($l['status'] == 'approved'): ?><span class="badge bg-success">Approved</span>
                        <?php elseif($l['status'] == 'rejected'): ?><span class="badge bg-danger">Rejected</span>
                        <?php else: ?><span class="badge bg-warning text-dark">Pending</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if($l['status'] == 'pending'): ?>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#actionModal<?= $l['id'] ?>">Action</button>
                            
                            <!-- Action Modal -->
                            <div class="modal fade" id="actionModal<?= $l['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" class="modal-content">
                                        <div class="modal-header"><h5 class="modal-title">Approve/Reject Leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <input type="hidden" name="leave_id" value="<?= $l['id'] ?>">
                                            <p><strong>Student:</strong> <?= htmlspecialchars($l['name']) ?></p>
                                            <div class="mb-3">
                                                <label>Action</label>
                                                <select name="status" class="form-select">
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                            </div>
                                            <div class="mb-3"><label>Remarks</label><input type="text" name="remarks" class="form-control" placeholder="e.g. Allowed, Parents called"></div>
                                        </div>
                                        <div class="modal-footer"><button type="submit" name="update_status" class="btn btn-primary">Save</button></div>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <small class="text-muted"><?= htmlspecialchars($l['admin_remarks']) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>