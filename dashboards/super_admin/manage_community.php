<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle New Campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign'])) {
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $target = (float)$_POST['target_amount'];
    
    $pdo->prepare("INSERT INTO community_campaigns (title, description, target_amount) VALUES (?, ?, ?)")
        ->execute([$title, $desc, $target]);
    $success = "Campaign started successfully!";
}

// Handle Verification of Contribution
if (isset($_GET['verify_id'])) {
    $id = (int)$_GET['verify_id'];
    $pdo->prepare("UPDATE campaign_contributions SET status = 'verified' WHERE id = ?")->execute([$id]);
    echo "<script>window.location.href='manage_community.php';</script>";
}

// Fetch Campaigns with Stats
$campaigns = $pdo->query("
    SELECT c.*, 
    (SELECT IFNULL(SUM(amount),0) FROM campaign_contributions WHERE campaign_id = c.id AND status = 'verified') as collected
    FROM community_campaigns c 
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<div class="row">
    <!-- Create Form -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Start New Fundraiser</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" placeholder="e.g. New Water Cooler" required></div>
                    <div class="mb-3"><label>Target Amount (Rs)</label><input type="number" name="target_amount" class="form-control" required></div>
                    <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <button type="submit" name="create_campaign" class="btn btn-primary w-100">Launch Campaign</button>
                </form>
            </div>
        </div>
    </div>

    <!-- List Campaigns -->
    <div class="col-md-8">
        <?php foreach($campaigns as $c): 
            $percent = ($c['target_amount'] > 0) ? round(($c['collected'] / $c['target_amount']) * 100) : 0;
            
            // Fetch Contributors for this campaign
            $contributors = $pdo->prepare("SELECT cc.*, u.name FROM campaign_contributions cc JOIN users u ON cc.user_id = u.id WHERE cc.campaign_id = ? ORDER BY cc.contributed_at DESC");
            $contributors->execute([$c['id']]);
            $list = $contributors->fetchAll();
        ?>
        <div class="card card-outline card-info mb-3">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title fw-bold"><?= htmlspecialchars($c['title']) ?></h5>
                <span class="badge bg-<?= $c['status']=='active'?'success':'secondary' ?>"><?= ucfirst($c['status']) ?></span>
            </div>
            <div class="card-body">
                <p><?= htmlspecialchars($c['description']) ?></p>
                
                <div class="d-flex justify-content-between small mb-1">
                    <span>Collected: <strong>Rs. <?= number_format($c['collected']) ?></strong></span>
                    <span>Target: <strong>Rs. <?= number_format($c['target_amount']) ?></strong></span>
                </div>
                <div class="progress mb-3" style="height: 15px;">
                    <div class="progress-bar bg-success progress-bar-striped" style="width: <?= $percent ?>%"><?= $percent ?>%</div>
                </div>

                <!-- Contributors List -->
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#list<?= $c['id'] ?>">
                    View Contributors (<?= count($list) ?>)
                </button>
                <div class="collapse mt-2" id="list<?= $c['id'] ?>">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light"><tr><th>Student</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($list as $con): ?>
                            <tr>
                                <td><?= htmlspecialchars($con['name']) ?></td>
                                <td>Rs. <?= number_format($con['amount']) ?></td>
                                <td>
                                    <?php if($con['status'] == 'verified'): ?><span class="badge bg-success">Verified</span>
                                    <?php else: ?><span class="badge bg-warning text-dark">Pending</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if($con['status'] == 'pending'): ?>
                                        <a href="?verify_id=<?= $con['id'] ?>" class="btn btn-xs btn-success" title="Confirm Payment Received"><i class="bi bi-check"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>