<?php
require_once '../includes/header.php';
require_once '../core/functions.php';

$user_id = $_SESSION['user_id'];

// 1. Find my room
$stmt = $pdo->prepare("SELECT room_id FROM room_allocations WHERE user_id = ? AND is_active = 1");
$stmt->execute([$user_id]);
$my_room_id = $stmt->fetchColumn();

// 2. Find my roommates
$roommates = [];
if ($my_room_id) {
    $stmt = $pdo->prepare("SELECT u.id, u.name FROM room_allocations ra JOIN users u ON ra.user_id = u.id WHERE ra.room_id = ? AND ra.user_id != ? AND ra.is_active = 1");
    $stmt->execute([$my_room_id, $user_id]);
    $roommates = $stmt->fetchAll();
}

// Handle New Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $reported_user_id = (int)$_POST['reported_user_id'];
    $reason = sanitize($_POST['reason_category']);
    $desc = sanitize($_POST['description']);

    // Check if there's an open dispute already
    $check = $pdo->prepare("SELECT id FROM dispute_reports WHERE reporting_user_id = ? AND reported_user_id = ? AND status = 'open'");
    $check->execute([$user_id, $reported_user_id]);
    if ($check->fetch()) {
        $error = "You already have an open report against this roommate.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO dispute_reports (reporting_user_id, reported_user_id, reason_category, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $reported_user_id, $reason, $desc]);
        $success = "Dispute reported successfully. The warden will look into it.";
    }
}

// Fetch My Reports History
$history = $pdo->prepare("
    SELECT dr.*, u.name as reported_name 
    FROM dispute_reports dr 
    JOIN users u ON dr.reported_user_id = u.id 
    WHERE dr.reporting_user_id = ? 
    ORDER BY dr.created_at DESC
");
$history->execute([$user_id]);
$myReports = $history->fetchAll();
?>

<div class="row">
    <div class="col-md-5">
        <div class="card card-danger card-outline">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-flag-fill"></i> Report a Roommate Issue</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <?php if (empty($roommates)): ?>
                    <div class="alert alert-info">You have no roommates to report.</div>
                <?php else: ?>
                    <form method="post">
                        <div class="mb-3"><label class="form-label">Select Roommate</label><select name="reported_user_id" class="form-select" required><?php foreach($roommates as $rm): ?><option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3"><label class="form-label">Issue Category</label><select name="reason_category" class="form-select" required><option value="Cleanliness">Cleanliness & Hygiene</option><option value="Noise / Disturbance">Noise / Disturbance</option><option value="Behavioral Issue">Behavioral Issue</option><option value="Property Damage">Property Damage</option><option value="Other">Other</option></select></div>
                        <div class="mb-3"><label class="form-label">Describe the Issue</label><textarea name="description" class="form-control" rows="4" placeholder="Please provide specific details, dates, and times if possible." required></textarea></div>
                        <button type="submit" name="submit_report" class="btn btn-danger w-100">Submit Confidential Report</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title">My Report History</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Date</th><th>Reported</th><th>Reason</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($myReports as $r): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                            <td><?= htmlspecialchars($r['reported_name']) ?></td>
                            <td><?= htmlspecialchars($r['reason_category']) ?></td>
                            <td><span class="badge bg-<?= $r['status']=='open'?'warning':($r['status']=='resolved'?'success':'info') ?>"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($myReports)): ?><tr><td colspan="4" class="text-center text-muted">No reports filed.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>