<?php
require_once '../includes/header.php';

// Security Check
if ($_SESSION['role'] !== 'student') {
    echo "<script>window.location.href='../index.php';</script>";
    exit;
}

// Fetch My Fees
$stmt = $pdo->prepare("SELECT * FROM student_fees WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$fees = $stmt->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-wallet2"></i> My Fee Status</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($fees as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['title']) ?></td>
                    <td><?= date('d M Y', strtotime($f['due_date'])) ?></td>
                    <td><strong>Rs. <?= number_format($f['amount']) ?></strong></td>
                    <td>
                        <?php if($f['status'] == 'paid'): ?>
                            <span class="badge bg-success">PAID</span>
                            <br><small class="text-muted"><?= date('d M Y', strtotime($f['paid_date'])) ?></small>
                        <?php else: ?>
                            <span class="badge bg-danger">UNPAID</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($f['status'] == 'paid'): ?>
                            <a href="print_receipt.php?id=<?= $f['id'] ?>" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="bi bi-printer"></i> Receipt
                            </a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-primary" disabled>Pay at Bank</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($fees)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No fee records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>