<?php
require_once '../../core/session.php'; 
require_once '../../core/functions.php';

// --- DATABASE REPAIR (Processing se pehle chalna zaroori hai) ---
// 1. Ensure paid_amount column exists
try { $pdo->query("SELECT paid_amount FROM student_fees LIMIT 1"); } 
catch (Exception $e) { $pdo->exec("ALTER TABLE student_fees ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0 AFTER amount"); }

// 2. Ensure status column is flexible (Convert from ENUM to VARCHAR to prevent truncation errors)
try {
    $pdo->exec("ALTER TABLE student_fees MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Unpaid'");
} catch (Exception $e) {}

// 1. Handle Actions
// Add Fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $fee_id = (int)$_POST['fee_id'];
    $user_id = (int)$_POST['student_id'];
    $title = sanitize($_POST['title']);
    $amount = (float)$_POST['amount'];
    $paid_amount = (float)$_POST['paid_amount'];
    $due_date = sanitize($_POST['due_date']);
    $status = sanitize($_POST['status'] ?? 'Unpaid');

    if ($fee_id > 0) {
        $p_date_sql = ($status === 'Paid') ? ", paid_date = IFNULL(paid_date, CURDATE())" : "";
        $stmt = $pdo->prepare("UPDATE student_fees SET user_id = ?, title = ?, amount = ?, paid_amount = ?, due_date = ?, status = ? $p_date_sql WHERE id = ?");
        $stmt->execute([$user_id, $title, $amount, $paid_amount, $due_date, $status, $fee_id]);
        $msg = "Fee record updated successfully.";
    } else {
        $p_date = ($status === 'Paid') ? date('Y-m-d') : null;
        $stmt = $pdo->prepare("INSERT INTO student_fees (user_id, title, amount, paid_amount, due_date, status, paid_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $amount, $paid_amount, $due_date, $status, $p_date]);
        $msg = "New fee assigned successfully.";
    }
    header("Location: manage_fees.php?success_msg=" . urlencode($msg));
    exit;
}

// Handle Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    $fee_id = (int)$_POST['fee_id'];
    $action = $_POST['action'];
    $remarks = sanitize($_POST['remarks'] ?? '');

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE student_fees SET status = 'paid', paid_date = CURDATE(), admin_remarks = ? WHERE id = ?");
        $stmt->execute([$remarks, $fee_id]);
        $success = "Payment verified and marked as Paid.";
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE student_fees SET status = 'rejected', admin_remarks = ? WHERE id = ?");
        $stmt->execute([$remarks, $fee_id]);
        $success = "Payment proof rejected.";
    }
    // No redirect to show success message
}

if (isset($_GET['mark_paid'])) { // Kept for backward compatibility or manual override
    $pdo->prepare("UPDATE student_fees SET status = 'paid', paid_date = CURDATE() WHERE id = ?")->execute([(int)$_GET['mark_paid']]);
}

// Delete Fee
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM student_fees WHERE id = ?")->execute([$id]);
    header("Location: manage_fees.php?success_msg=Fee record deleted");
    exit;
}

// Fetch Data
$all_students = $pdo->query("SELECT id, name, registration_no, phone FROM users WHERE role = 'student' AND is_deleted = 0")->fetchAll(PDO::FETCH_ASSOC);
$fees = $pdo->query("SELECT f.*, u.name, u.registration_no FROM student_fees f JOIN users u ON f.user_id = u.id ORDER BY f.id DESC LIMIT 50")->fetchAll();
$next_id = $pdo->query("SELECT MAX(id) FROM student_fees")->fetchColumn() + 1;

require_once '../../includes/header.php';
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .fee-dashboard { background: #fcfcfc; border-radius: 25px; overflow: hidden; box-shadow: 0 15px 50px rgba(0,0,0,0.08); margin-top: 10px; border: none; }
    .fee-header { background: linear-gradient(to right, #2ecc71, #1abc9c);
 !important; padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; color: white; }
    
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }

    .fee-title { font-weight: 900; font-size: 2rem; color: #fcfcfc; text-transform: uppercase; margin: 0; letter-spacing: 1px; }
    .btn-home { background: white; color: #198754 !important; font-weight: bold; border-radius: 12px; padding: 8px 25px; text-decoration: none; transition: 0.3s; }
    .btn-home:hover { transform: scale(1.05); background: #f8f9fa; }

    .form-container { padding: 40px; background: #fff; position: relative; }
    .fee-id-badge { position: absolute; top: 20px; right: 30px; background: #f1f3f5; color: #666; padding: 5px 15px; border-radius: 8px; font-weight: 800; font-size: 0.8rem; }
    
    .underline-input { border: none; border-bottom: 2px solid #eaedf0; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; color: #333; transition: 0.3s; }
    .underline-input:focus { box-shadow: none; outline: none; border-bottom-color: #6f42c1; }
    .highlight-blue { border-bottom-color: #007bff !important; }

    .btn-add { background: #198754; color: white; border: none; border-radius: 50px; padding: 12px 40px; font-weight: 700; transition: 0.3s; box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3); }
    .btn-add:hover { transform: translateY(-2px); opacity: 0.9; }

    .table-section { padding: 0 40px 40px; }
    .fee-table { border: 1px solid #eee; border-radius: 15px; overflow: hidden; }
    .fee-table th { background: #f8f9fa; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; border: none; }
    .fee-table td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f1f3f5; }
    .fee-table tr:hover { background: rgba(111, 66, 193, 0.02); }

    .label-min { font-size: 0.75rem; font-weight: 800; color: #333; text-transform: uppercase; display: block; margin-bottom: -5px; }
</style>

<div class="fee-dashboard">
    <div class="fee-header">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="fee-title">Fee Management</h2>
        <a href="<?= BASE_URL ?>" class="btn-home shadow-sm">Home</a>
    </div>

    <div class="form-container">
        <?php if(isset($_GET['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['success_msg']) ?></div><?php endif; ?>
        <div class="fee-id-badge" id="fee_id_display">F-<?= str_pad($next_id, 3, '0', STR_PAD_LEFT) ?></div>

        <form method="post" id="feeForm" class="row g-4 align-items-end">
            <input type="hidden" name="fee_id" id="edit_fee_id" value="0">
            
            <div class="col-md-2">
                <label class="label-min">Student ID</label>
                <select name="student_id" id="student_select" class="form-select underline-input" required>
                    <option value="">Select ID</option>
                    <?php foreach($all_students as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['registration_no'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="label-min">Student Name</label>
                <input type="text" id="student_name" class="form-control underline-input" placeholder="Auto-filled" readonly>
            </div>
            <div class="col-md-3">
                <label class="label-min">Fee Title</label>
                <input type="text" name="title" class="form-control underline-input" placeholder="e.g. Hostel Fee" required>
            </div>
            <div class="col-md-2">
                <label class="label-min">Total Fee</label>
                <input type="number" name="amount" id="total_fee" class="form-control underline-input" placeholder="0.00" required>
            </div>
            <div class="col-md-2">
                <label class="label-min">Paid Amount</label>
                <input type="number" name="paid_amount" id="paid_amount" class="form-control underline-input highlight-blue" value="0">
            </div>
            <div class="col-md-2">
                <label class="label-min">Remaining</label>
                <input type="number" id="remaining_amount" class="form-control underline-input" placeholder="0.00" readonly>
            </div>
            <div class="col-md-2">
                <label class="label-min">Due Date</label>
                <input type="date" name="due_date" class="form-control underline-input" required>
            </div>
            <div class="col-md-2">
                <label class="label-min">Status</label>
                <select name="status" id="fee_status" class="form-select underline-input">
                    <option value="Unpaid">Unpaid</option>
                    <option value="Partial">Partial</option>
                    <option value="Paid">Paid</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button type="submit" name="add_fee" id="submitBtn" class="btn-add">ADD</button>
                <button type="button" id="cancelBtn" class="btn btn-sm btn-secondary rounded-pill" style="display:none;" onclick="resetFeeForm()">Cancel</button>
            </div>
        </form>
    </div>

    <div class="table-section">
        <div class="table-responsive fee-table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Fee ID</th>
                        <th>Date</th>
                        <th>Student ID</th>
                        <th>Fee Title</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Remaining</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($fees as $f): 
                        $rem = $f['amount'] - $f['paid_amount'];
                        $st_cls = $f['status'] == 'Paid' ? 'text-success' : ($f['status'] == 'Partial' ? 'text-warning' : 'text-danger');
                        // Formula for highlighting overdue dates
                        $is_overdue = ($f['status'] !== 'Paid' && strtotime($f['due_date']) < strtotime(date('Y-m-d')));
                    ?>
                    <tr>
                        <td>F-<?= str_pad($f['id'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td class="small text-muted"><?= date('d M Y', strtotime($f['created_at'])) ?></td>
                        <td><?= htmlspecialchars($f['registration_no']) ?></td>
                        <td class="text-dark"><?= htmlspecialchars($f['title']) ?></td>
                        <td>Rs. <?= number_format($f['amount']) ?></td>
                        <td class="text-primary">Rs. <?= number_format($f['paid_amount']) ?></td>
                        <td class="text-danger">Rs. <?= number_format($rem) ?></td>
                        <td class="<?= $is_overdue ? 'text-danger fw-bold' : '' ?>">
                            <?= date('d M Y', strtotime($f['due_date'])) ?>
                            <?php if($is_overdue): ?><br><small class="badge bg-danger" style="font-size: 0.6rem;">OVERDUE</small><?php endif; ?>
                        </td>
                        <td><span class="fw-bold <?= $st_cls ?>"><?= strtoupper($f['status']) ?></span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm text-primary border-0" onclick="editFee('<?= $f['id'] ?>', '<?= $f['user_id'] ?>', '<?= addslashes($f['title']) ?>', '<?= $f['amount'] ?>', '<?= $f['paid_amount'] ?>', '<?= $f['due_date'] ?>', '<?= $f['status'] ?>')"><i class="bi bi-pencil-square"></i></button>
                            <a href="?delete=<?= $f['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Delete this record?')"><i class="bi bi-trash3-fill"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const studentData = <?= json_encode($all_students) ?>;

    // Auto-fill Student Info
    document.getElementById('student_select').addEventListener('change', function() {
        const student = studentData.find(s => s.id == this.value);
        if(student) {
            document.getElementById('student_name').value = student.name;
        }
    });

    // Auto-calculate Remaining and Status
    function calculateFee() {
        const total = parseFloat(document.getElementById('total_fee').value) || 0;
        const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
        const remaining = total - paid;
        document.getElementById('remaining_amount').value = remaining.toFixed(2);
        
        const statusSelect = document.getElementById('fee_status');
        if(paid <= 0) statusSelect.value = "Unpaid";
        else if(remaining <= 0) statusSelect.value = "Paid";
        else statusSelect.value = "Partial";
    }

    document.getElementById('total_fee').addEventListener('input', calculateFee);
    document.getElementById('paid_amount').addEventListener('input', calculateFee);

    function editFee(id, studentId, title, amount, paid, date, status) {
        document.getElementById('edit_fee_id').value = id;
        document.getElementById('fee_id_display').innerText = "EDIT F-" + id.toString().padStart(3, '0');
        
        const studentSelect = document.getElementById('student_select');
        studentSelect.value = studentId;
        studentSelect.dispatchEvent(new Event('change')); // Auto-fill name/contact

        document.getElementsByName('title')[0].value = title;
        document.getElementById('total_fee').value = amount;
        document.getElementById('paid_amount').value = paid;
        document.getElementsByName('due_date')[0].value = date;
        document.getElementById('fee_status').value = status;

        calculateFee(); // Update remaining field
        document.getElementById('submitBtn').innerText = "UPDATE";
        document.getElementById('cancelBtn').style.display = 'inline-block';
    }

    function resetFeeForm() {
        window.location.reload();
    }

    setTimeout(() => { document.querySelectorAll('.auto-hide').forEach(el => el.style.display = 'none'); }, 5000);
</script>

<?php require_once '../../includes/footer.php'; ?>