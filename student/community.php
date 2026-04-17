<?php
require_once '../includes/header.php';
require_once '../core/session.php';

// Handle Contribution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contribute'])) {
    $camp_id = (int)$_POST['campaign_id'];
    $amount = (float)$_POST['amount'];
    $user_id = $_SESSION['user_id'];

    if ($amount > 0) {
        $pdo->prepare("INSERT INTO campaign_contributions (campaign_id, user_id, amount) VALUES (?, ?, ?)")
            ->execute([$camp_id, $user_id, $amount]);
        $success = "Thank you! Your contribution of Rs. $amount has been recorded. Please pay the amount to the Admin/Warden.";
    }
}

// Fetch Active Campaigns
$campaigns = $pdo->query("
    SELECT c.*, 
    (SELECT IFNULL(SUM(amount),0) FROM campaign_contributions WHERE campaign_id = c.id AND status = 'verified') as collected
    FROM community_campaigns c 
    WHERE c.status = 'active'
    ORDER BY c.created_at DESC
")->fetchAll();

// Fetch My Contributions History
$stmt = $pdo->prepare("
    SELECT cc.*, c.title 
    FROM campaign_contributions cc 
    JOIN community_campaigns c ON cc.campaign_id = c.id 
    WHERE cc.user_id = ? 
    ORDER BY cc.contributed_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$myHistory = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card bg-primary text-white shadow">
            <div class="card-body p-4 text-center">
                <h2 class="fw-bold"><i class="bi bi-people-fill"></i> Hostel Community Fund</h2>
                <p class="lead">Together we make our hostel a better place. Contribute to shared amenities!</p>
            </div>
        </div>
    </div>

    <?php if(isset($success)): ?><div class="col-12"><div class="alert alert-success"><?= $success ?></div></div><?php endif; ?>

    <?php foreach($campaigns as $c): 
        $percent = ($c['target_amount'] > 0) ? round(($c['collected'] / $c['target_amount']) * 100) : 0;
    ?>
    <div class="col-md-6">
        <div class="card card-outline card-success h-100">
            <div class="card-header"><h4 class="card-title fw-bold"><?= htmlspecialchars($c['title']) ?></h4></div>
            <div class="card-body">
                <p class="card-text"><?= htmlspecialchars($c['description']) ?></p>
                
                <h5 class="mt-3">Goal: Rs. <?= number_format($c['target_amount']) ?></h5>
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: <?= $percent ?>%">
                        Raised: Rs. <?= number_format($c['collected']) ?> (<?= $percent ?>%)
                    </div>
                </div>

                <hr>
                <form method="post" class="row g-2 align-items-center">
                    <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                    <div class="col-auto"><label class="col-form-label fw-bold">I want to contribute:</label></div>
                    <div class="col"><input type="number" name="amount" class="form-control" placeholder="Amount (Rs)" min="100" required></div>
                    <div class="col-auto"><button type="submit" name="contribute" class="btn btn-success">Pledge</button></div>
                </form>
                <small class="text-muted d-block mt-2"><i class="bi bi-info-circle"></i> Your name will be added to the contributors list once verified.</small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- My Contributions History -->
    <div class="col-12 mt-4">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-clock-history"></i> My Contributions History</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead><tr><th>Campaign</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($myHistory as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['title']) ?></td>
                            <td>Rs. <?= number_format($h['amount']) ?></td>
                            <td><?= date('d M Y', strtotime($h['contributed_at'])) ?></td>
                            <td>
                                <?php if($h['status'] == 'verified'): ?><span class="badge bg-success">Received</span>
                                <?php else: ?><span class="badge bg-warning text-dark">Pending Payment</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($myHistory)): ?><tr><td colspan="4" class="text-center text-muted">No contributions yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>