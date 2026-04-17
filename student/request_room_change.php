<?php
require_once '../includes/header.php';
require_once '../core/functions.php';

// Fetch Current Room
$stmt = $pdo->prepare("SELECT r.id, r.room_no, r.building FROM room_allocations ra JOIN rooms r ON ra.room_id = r.id WHERE ra.user_id = ? AND ra.is_active = 1");
$stmt->execute([$_SESSION['user_id']]);
$currentRoom = $stmt->fetch();

// Handle New Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    if (!$currentRoom) {
        $error = "You don't have a room assigned currently.";
    } else {
        $reason = sanitize($_POST['reason_category']);
        $desc = sanitize($_POST['description']);
        
        // Check if pending request exists
        $check = $pdo->prepare("SELECT id FROM room_change_requests WHERE user_id = ? AND status = 'pending'");
        $check->execute([$_SESSION['user_id']]);
        if ($check->rowCount() > 0) {
            $error = "You already have a pending request.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO room_change_requests (user_id, current_room_id, reason_category, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $currentRoom['id'], $reason, $desc]);
            $success = "Request submitted to Warden.";
        }
    }
}

// Fetch History
$history = $pdo->prepare("SELECT * FROM room_change_requests WHERE user_id = ? ORDER BY created_at DESC");
$history->execute([$_SESSION['user_id']]);
$requests = $history->fetchAll();
?>

<div class="row">
    <div class="col-md-5">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Request Room Change</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <?php if($currentRoom): ?>
                    <div class="alert alert-info">
                        Current Room: <strong><?= htmlspecialchars($currentRoom['building'] . ' - ' . $currentRoom['room_no']) ?></strong>
                    </div>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Reason for Change</label>
                            <select name="reason_category" class="form-select" required>
                                <option value="Roommate Issue">Roommate Issue</option>
                                <option value="Medical Issue">Medical Issue</option>
                                <option value="Noise / Disturbance">Noise / Disturbance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Detailed Explanation</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Please explain why you need to shift..." required></textarea>
                        </div>
                        <button type="submit" name="submit_request" class="btn btn-primary w-100">Submit Request</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">You must be allocated a room first to request a change.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title">Request History</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Date</th><th>Reason</th><th>Status</th><th>Remarks</th></tr></thead>
                    <tbody>
                        <?php foreach($requests as $r): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                            <td><?= htmlspecialchars($r['reason_category']) ?></td>
                            <td><span class="badge bg-<?= $r['status']=='approved'?'success':($r['status']=='rejected'?'danger':'warning') ?>"><?= ucfirst($r['status']) ?></span></td>
                            <td><small><?= htmlspecialchars($r['admin_remarks'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>