<?php
require_once '../includes/header.php';

// Security Check
if ($_SESSION['role'] !== 'student') {
    echo "<script>window.location.href='../index.php';</script>";
    exit;
}

// Handle Receipt Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_receipt'])) {
    $fee_id = (int)$_POST['fee_id'];
    $receipt_file = $_FILES['receipt_file'];

    // File validation
    if ($receipt_file['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        if (in_array($receipt_file['type'], $allowed_types) && $receipt_file['size'] < 5000000) { // 5MB limit
            $upload_dir = __DIR__ . '/../../uploads/receipts/';
            $filename = time() . '_' . uniqid() . '_' . basename($receipt_file['name']);
            $destination = $upload_dir . $filename;

            if (move_uploaded_file($receipt_file['tmp_name'], $destination)) {
                // Update DB
                $stmt = $pdo->prepare("UPDATE student_fees SET payment_receipt = ?, status = 'pending_verification' WHERE id = ? AND user_id = ?");
                $stmt->execute([$filename, $fee_id, $_SESSION['user_id']]);
                $success = "Receipt uploaded successfully! Awaiting verification.";
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type or size too large. Only JPG, PNG, PDF under 5MB are allowed.";
        }
    } else {
        $error = "File upload error. Please try again.";
    }
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
        <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger m-3"><?= $error ?></div><?php endif; ?>

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
                            <span class="badge bg-success">Verified & Paid</span>
                            <br><small class="text-muted"><?= date('d M Y', strtotime($f['paid_date'])) ?></small>
                        <?php elseif($f['status'] == 'pending_verification'): ?>
                            <span class="badge bg-info">Awaiting Verification</span>
                        <?php elseif($f['status'] == 'rejected'): ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Due</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($f['status'] == 'paid'): ?>
                            <a href="<?= BASE_URL ?>uploads/receipts/<?= htmlspecialchars($f['payment_receipt']) ?>" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="bi bi-printer"></i> Receipt
                            </a>
                        <?php elseif($f['status'] == 'due' || $f['status'] == 'rejected'): ?>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal<?= $f['id'] ?>">
                                <i class="bi bi-upload"></i> Upload Receipt
                            </button>
                            <?php if($f['status'] == 'rejected' && !empty($f['admin_remarks'])): ?>
                                <div class="text-danger small mt-1" title="Admin Remarks"><i class="bi bi-info-circle"></i> <?= htmlspecialchars($f['admin_remarks']) ?></div>
                            <?php endif; ?>
                        <?php elseif($f['status'] == 'pending_verification'): ?>
                             <a href="<?= BASE_URL ?>uploads/receipts/<?= htmlspecialchars($f['payment_receipt']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i> View Uploaded
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($fees)): ?>
                    <tr><td colspan="5" class="text-center text-muted p-4">No fee records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Modals -->
<?php foreach($fees as $f): ?>
    <?php if($f['status'] == 'due' || $f['status'] == 'rejected'): ?>
    <div class="modal fade" id="uploadModal<?= $f['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Upload Payment Receipt</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="fee_id" value="<?= $f['id'] ?>">
                    <p>Please upload a clear image or PDF of your payment receipt for "<strong><?= htmlspecialchars($f['title']) ?></strong>".</p>
                    <div class="mb-3"><input type="file" name="receipt_file" class="form-control" required accept="image/jpeg,image/png,application/pdf"></div>
                </div>
                <div class="modal-footer"><button type="submit" name="upload_receipt" class="btn btn-primary">Upload & Submit</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php require_once '../includes/footer.php'; ?>