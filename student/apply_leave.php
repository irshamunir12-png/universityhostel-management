<?php
require_once '../includes/header.php';
require_once '../core/functions.php';

// --- SELF-HEALING: Register Page in Menu & Grant Access ---
$page_url = 'student/apply_leave.php';
$checkPage = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
$checkPage->execute([$page_url]);
$page_entry = $checkPage->fetch();

if (!$page_entry) {
    // 1. Find Student Portal Category ID (Parent)
    $stmt = $pdo->query("SELECT id FROM sys_pages WHERE page_name = 'Student Portal' LIMIT 1");
    $parent_id = $stmt->fetchColumn() ?: 6; // Default to 6 if not found

    // 2. Add Page to Menu
    $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (?, 'Apply Leave', ?, 'bi bi-calendar-plus', 7)")
        ->execute([$parent_id, $page_url]);
    $new_id = $pdo->lastInsertId();
    
    // 3. Grant Access to Student Role
    $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('student', ?)")
        ->execute([$new_id]);
}

// Handle Leave Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);
    $user_id = $_SESSION['user_id'];

    if (strtotime($start_date) > strtotime($end_date)) {
        $error = "End date cannot be before start date.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO student_leaves (user_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$user_id, $start_date, $end_date, $reason])) {
            $success = "Leave application submitted successfully!";
        } else {
            $error = "Failed to submit leave.";
        }
    }
}

// Fetch My Leaves
$stmt = $pdo->prepare("SELECT * FROM student_leaves WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_leaves = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-5">
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header"><h3 class="card-title fw-bold"><i class="bi bi-calendar-plus me-2"></i>Apply for Leave</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">START DATE</label>
                        <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">END DATE</label>
                        <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">REASON</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Why do you need leave?" required></textarea>
                    </div>
                    <button type="submit" name="apply_leave" class="btn btn-primary w-100 fw-bold">SUBMIT REQUEST</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header"><h3 class="card-title fw-bold">My Leave History</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($my_leaves as $l): ?>
                            <tr>
                                <td class="small">
                                    <span class="d-block fw-bold"><?= date('d M', strtotime($l['start_date'])) ?> to <?= date('d M', strtotime($l['end_date'])) ?></span>
                                    <span class="text-muted"><?= date('Y', strtotime($l['start_date'])) ?></span>
                                </td>
                                <td class="small"><?= htmlspecialchars($l['reason']) ?></td>
                                <td class="text-center">
                                    <?php if($l['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif($l['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
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
</div>
<?php require_once '../includes/footer.php'; ?>