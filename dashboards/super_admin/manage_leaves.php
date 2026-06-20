<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Status Update
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];
    $stmt = $pdo->prepare("UPDATE student_leaves SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: manage_leaves.php?msg=Status updated");
    exit;
}

$leaves = $pdo->query("
    SELECT l.*, u.name, u.registration_no, r.room_no 
    FROM student_leaves l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1)
    LEFT JOIN rooms r ON ra.room_id = r.id
    ORDER BY l.status = 'pending' DESC, l.created_at DESC
")->fetchAll();
?>

<div class="card card-primary card-outline shadow">
    <div class="card-header"><h3 class="card-title fw-bold">Student Leave Requests</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student Details</th>
                        <th>Leave Dates</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($leaves as $l): ?>
                    <tr>
                        <td>
                            <span class="fw-bold d-block"><?= htmlspecialchars($l['name']) ?></span>
                            <small class="text-muted"><?= htmlspecialchars($l['registration_no']) ?> | Room: <?= $l['room_no'] ?: 'N/A' ?></small>
                        </td>
                        <td class="small">
                            <span class="text-primary fw-bold"><?= date('d M Y', strtotime($l['start_date'])) ?></span><br>
                            <span class="text-danger fw-bold"><?= date('d M Y', strtotime($l['end_date'])) ?></span>
                        </td>
                        <td class="small text-wrap" style="max-width: 200px;"><?= htmlspecialchars($l['reason']) ?></td>
                        <td>
                            <?php if($l['status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php elseif($l['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($l['status'] === 'pending'): ?>
                                <div class="btn-group">
                                    <a href="?id=<?= $l['id'] ?>&status=approved" class="btn btn-sm btn-success rounded-pill px-3 me-1">Approve</a>
                                    <a href="?id=<?= $l['id'] ?>&status=rejected" class="btn btn-sm btn-outline-danger rounded-pill px-3">Reject</a>
                                </div>
                            <?php else: ?>
                                <small class="text-muted italic">Processed</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(!$leaves): ?><tr><td colspan="5" class="text-center p-4 text-muted">No leave requests to show.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>