<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// --- DATABASE REPAIR: Ensure dispute_reports table exists with correct columns ---
$pdo->exec("CREATE TABLE IF NOT EXISTS `dispute_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporting_user_id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `reason_category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','warning_issued','resolved','closed') DEFAULT 'open',
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure old tables get new columns (incase table existed but was outdated)
try { $pdo->query("SELECT reported_user_id FROM dispute_reports LIMIT 1"); } catch (Exception $e) {
    $pdo->exec("ALTER TABLE dispute_reports ADD COLUMN reporting_user_id INT NOT NULL AFTER id, ADD COLUMN reported_user_id INT NOT NULL AFTER reporting_user_id, ADD COLUMN reason_category VARCHAR(100) AFTER reported_user_id, ADD COLUMN description TEXT AFTER reason_category, ADD COLUMN admin_remarks TEXT AFTER status");
    $pdo->exec("ALTER TABLE dispute_reports MODIFY COLUMN status ENUM('open','warning_issued','resolved','closed') DEFAULT 'open'");
}

// --- DATABASE REPAIR: Ensure student_warnings table exists ---
$pdo->exec("CREATE TABLE IF NOT EXISTS `student_warnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dispute_id` int(11) DEFAULT NULL,
  `warning_text` text NOT NULL,
  `issued_by_id` int(11) NOT NULL,
  `issued_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle Actions (Warning, Resolve, Close)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dispute'])) {
    $dispute_id = (int)$_POST['dispute_id'];
    $action = $_POST['action'];
    $remarks = sanitize($_POST['remarks']);
    $reported_user_id = (int)$_POST['reported_user_id'];

    if ($action === 'issue_warning') {
        $pdo->beginTransaction();
        try {
            // 1. Add to warnings table
            $stmt1 = $pdo->prepare("INSERT INTO student_warnings (user_id, dispute_id, warning_text, issued_by_id) VALUES (?, ?, ?, ?)");
            $stmt1->execute([$reported_user_id, $dispute_id, $remarks, $_SESSION['user_id']]);
            
            // 2. Update dispute status
            $stmt2 = $pdo->prepare("UPDATE dispute_reports SET status = 'warning_issued', admin_remarks = ? WHERE id = ?");
            $stmt2->execute([$remarks, $dispute_id]);
            
            $pdo->commit();
            $success = "Warning issued successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error issuing warning: " . $e->getMessage();
        }
    } elseif ($action === 'resolve') {
        $stmt = $pdo->prepare("UPDATE dispute_reports SET status = 'resolved', admin_remarks = ? WHERE id = ?");
        $stmt->execute([$remarks, $dispute_id]);
        $success = "Dispute marked as resolved.";
    } elseif ($action === 'close') {
        $stmt = $pdo->prepare("UPDATE dispute_reports SET status = 'closed', admin_remarks = ? WHERE id = ?");
        $stmt->execute([$remarks, $dispute_id]);
        $success = "Dispute closed.";
    }
}

// Fetch Disputes
$filter = $_GET['status'] ?? 'open';
$sql = "
    SELECT 
        dr.*, 
        reporter.name as reporter_name, 
        reported.name as reported_name,
        r.room_no, r.building
    FROM dispute_reports dr
    JOIN users reporter ON dr.reporting_user_id = reporter.id
    JOIN users reported ON dr.reported_user_id = reported.id
    LEFT JOIN room_allocations ra ON reported.id = ra.user_id AND ra.is_active = 1
    LEFT JOIN rooms r ON ra.room_id = r.id
    WHERE dr.status = ?
    ORDER BY dr.created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$filter]);
$disputes = $stmt->fetchAll();
?>

<div class="card card-danger card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="bi bi-shield-exclamation"></i> Roommate Dispute Management</h3>
        <div class="btn-group btn-group-sm">
            <a href="?status=open" class="btn btn-outline-danger <?= $filter=='open'?'active':'' ?>">Open</a>
            <a href="?status=warning_issued" class="btn btn-outline-warning <?= $filter=='warning_issued'?'active':'' ?>">Warning Issued</a>
            <a href="?status=resolved" class="btn btn-outline-success <?= $filter=='resolved'?'active':'' ?>">Resolved</a>
            <a href="?status=closed" class="btn btn-outline-secondary <?= $filter=='closed'?'active':'' ?>">Closed</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger m-3"><?= $error ?></div><?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Case Details</th><th>Room</th><th>Issue</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if(empty($disputes)): ?><tr><td colspan="4" class="text-center text-muted p-4">No disputes in this category.</td></tr><?php endif; ?>
                    <?php foreach($disputes as $d): ?>
                    <tr>
                        <td><strong class="text-primary">Reporter:</strong> <?= htmlspecialchars($d['reporter_name']) ?><br><strong class="text-danger">Reported:</strong> <?= htmlspecialchars($d['reported_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($d['building'] . ' - ' . $d['room_no']) ?></span></td>
                        <td><strong><?= htmlspecialchars($d['reason_category']) ?></strong><p class="small mb-0 text-muted" title="<?= htmlspecialchars($d['description']) ?>"><?= htmlspecialchars(substr($d['description'], 0, 100)) ?>...</p></td>
                        <td>
                            <?php if($d['status'] === 'open'): ?>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#warnModal<?= $d['id'] ?>">Issue Warning</button>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal<?= $d['id'] ?>">Resolve</button>
                            <?php elseif($d['status'] === 'warning_issued'): ?>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal<?= $d['id'] ?>">Resolve</button>
                                <a href="manage_room_requests.php" class="btn btn-sm btn-outline-danger">Room Change</a>
                            <?php else: ?><p class="small text-muted mb-0"><strong>Remarks:</strong> <?= htmlspecialchars($d['admin_remarks']) ?></p><?php endif; ?>
                        </td>
                    </tr>

                    <!-- Modals for actions -->
                    <div class="modal fade" id="warnModal<?= $d['id'] ?>"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Issue Warning</h5></div><div class="modal-body"><input type="hidden" name="dispute_id" value="<?= $d['id'] ?>"><input type="hidden" name="reported_user_id" value="<?= $d['reported_user_id'] ?>"><input type="hidden" name="action" value="issue_warning"><p>Issue a formal warning to <strong><?= htmlspecialchars($d['reported_name']) ?></strong>.</p><div class="mb-3"><label>Warning Details / Remarks</label><textarea name="remarks" class="form-control" required></textarea></div></div><div class="modal-footer"><button type="submit" name="update_dispute" class="btn btn-warning">Confirm Warning</button></div></form></div></div>
                    <div class="modal fade" id="resolveModal<?= $d['id'] ?>"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Resolve Dispute</h5></div><div class="modal-body"><input type="hidden" name="dispute_id" value="<?= $d['id'] ?>"><input type="hidden" name="reported_user_id" value="<?= $d['reported_user_id'] ?>"><input type="hidden" name="action" value="resolve"><p>Mark this dispute as resolved.</p><div class="mb-3"><label>Resolution Details / Remarks</label><textarea name="remarks" class="form-control" required></textarea></div></div><div class="modal-footer"><button type="submit" name="update_dispute" class="btn btn-success">Mark as Resolved</button></div></form></div></div>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>