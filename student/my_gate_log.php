<?php
require_once '../includes/header.php';
require_once '../core/session.php';

$user_id = $_SESSION['user_id'];

// Fetch My Gate Log History
$myLog = $pdo->prepare("SELECT * FROM gate_log WHERE user_id = ? ORDER BY log_time DESC LIMIT 100");
$myLog->execute([$user_id]);
$history = $myLog->fetchAll();

// Fetch Curfew Time
$curfew_time = $settings['curfew_time'] ?? '22:00:00';
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-clock-history"></i> My Entry/Exit Log</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-secondary">
            The official curfew time for returning to the hostel is <strong><?= date('h:i A', strtotime($curfew_time)) ?></strong>. Late entries are recorded.
        </div>

        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($history)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No gate log history found.</td></tr>
                <?php endif; ?>
                <?php foreach($history as $log): ?>
                <tr class="<?= $log['is_late'] ? 'table-danger' : '' ?>">
                    <td><?= date('D, d M Y', strtotime($log['log_time'])) ?></td>
                    <td><?= date('h:i:s A', strtotime($log['log_time'])) ?></td>
                    <td><?php if($log['log_type'] == 'in'): ?><span class="badge bg-success">Checked In</span><?php else: ?><span class="badge bg-warning text-dark">Checked Out</span><?php endif; ?></td>
                    <td><?php if($log['is_late']): ?><span class="badge bg-danger">LATE ENTRY</span><?php else: ?><span class="text-success small">On Time</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>