<?php
require_once '../core/session.php';
require_once '../core/db.php';

// Check Login
if (!isset($_SESSION['user_id'])) die("Access Denied");

$id = $_GET['id'] ?? 0;

// Fetch Fee Details
$stmt = $pdo->prepare("
    SELECT f.*, u.name, u.registration_no, u.email 
    FROM student_fees f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.id = ? AND f.user_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$fee = $stmt->fetch();

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
        .receipt-box { 
            max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; 
            border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #0d6efd; }
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
        <div class="logo">University Hostel</div>
        <p>Official Fee Receipt</p>
        <small>Date: <?= date('d M Y') ?></small>
    </div>

    <table class="table table-borderless">
        <tr>
            <td><strong>Receipt No:</strong></td>
            <td class="text-end">#<?= str_pad($fee['id'], 5, '0', STR_PAD_LEFT) ?></td>
        </tr>
        <tr>
            <td><strong>Student Name:</strong></td>
            <td class="text-end"><?= htmlspecialchars($fee['name']) ?></td>
        </tr>
        <tr>
            <td><strong>Reg No:</strong></td>
            <td class="text-end"><?= htmlspecialchars($fee['registration_no']) ?></td>
        </tr>
        <tr>
            <td><strong>Fee Title:</strong></td>
            <td class="text-end"><?= htmlspecialchars($fee['title']) ?></td>
        </tr>
        <tr>
            <td><strong>Payment Date:</strong></td>
            <td class="text-end"><?= date('d M Y', strtotime($fee['paid_date'])) ?></td>
        </tr>
        <tr class="border-top mt-2">
            <td class="pt-3"><h4>Total Paid:</h4></td>
            <td class="text-end pt-3"><h4 class="text-success">Rs. <?= number_format($fee['amount']) ?></h4></td>
        </tr>
    </table>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>
    
    <div class="text-center mt-5 text-muted small">
        <p>This is a computer-generated receipt and does not require a signature.</p>
    </div>
</div>

</body>
</html>