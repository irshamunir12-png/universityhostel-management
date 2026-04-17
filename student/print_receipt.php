<?php
require_once '../core/session.php';

// Fetch Fee Details
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT f.*, u.name, u.registration_no, u.email FROM student_fees f JOIN users u ON f.user_id = u.id WHERE f.id = ? AND f.user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $fee = $stmt->fetch();
}

if (!$fee) die("Receipt not found or access denied.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt #<?= $fee['id'] ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        body { background: #f4f6f9; padding: 20px; }
        .receipt-box { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; }
        .table-borderless td { padding: 5px 0; }
        @media print {
            body { background: #fff; }
            .receipt-box { box-shadow: none; border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-box">
    <div class="header">
        <div class="logo">University Hostel System</div>
        <p>Official Fee Receipt</p>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <strong>Student Details:</strong><br>
            <?= htmlspecialchars($fee['name']) ?><br>
            Reg No: <?= htmlspecialchars($fee['registration_no']) ?><br>
            <?= htmlspecialchars($fee['email']) ?>
        </div>
        <div class="col-6 text-end">
            <strong>Receipt #:</strong> <?= $fee['id'] ?><br>
            <strong>Date:</strong> <?= date('d M Y') ?><br>
            <strong>Status:</strong> <span class="badge bg-success">PAID</span>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Description</th>
                <th class="text-end">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($fee['title']) ?></td>
                <td class="text-end"><?= number_format($fee['amount'], 2) ?></td>
            </tr>
            <tr>
                <td class="text-end"><strong>Total Paid</strong></td>
                <td class="text-end"><strong><?= number_format($fee['amount'], 2) ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="text-center mt-5 pt-3 border-top">
        <p class="text-muted small">This is a computer-generated receipt and does not require a signature.</p>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Receipt</button>
    </div>
</div>

</body>
</html>