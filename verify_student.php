<?php
// This is a public page, so we only need the DB connection.
require_once 'core/db.php';
require_once 'core/functions.php';

$student = null;
if (isset($_GET['reg_no'])) {
    $reg_no = sanitize($_GET['reg_no']);
    
    $stmt = $pdo->prepare("
        SELECT u.name, u.registration_no, u.email, u.is_active, u.avatar, u.guardian_name, u.emergency_contact, r.room_no, r.building 
        FROM users u 
        LEFT JOIN room_allocations ra ON u.id = ra.user_id AND ra.is_active = 1 
        LEFT JOIN rooms r ON ra.room_id = r.id 
        WHERE u.registration_no = ? AND u.role = 'student'
    ");
    $stmt->execute([$reg_no]);
    $student = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Verification</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; }
        .verification-card { max-width: 500px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card verification-card shadow-sm">
            <div class="card-header text-center bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-patch-check-fill"></i> Student Verification</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($student): ?>
                    <div class="text-center mb-3">
                        <img src="<?= !empty($student['avatar']) ? BASE_URL . $student['avatar'] : 'assets/img/avatar.png' ?>" 
                             class="rounded-circle shadow" width="100" height="100" style="object-fit: cover;" alt="Avatar">
                    </div>
                    <h3 class="text-center mb-3"><?= htmlspecialchars($student['name']) ?></h3>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">Registration No: <strong><?= htmlspecialchars($student['registration_no']) ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">Guardian: <strong><?= htmlspecialchars($student['guardian_name'] ?? 'N/A') ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">Emergency No: <strong><?= htmlspecialchars($student['emergency_contact'] ?? 'N/A') ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">Room: <strong><?= $student['room_no'] ? htmlspecialchars($student['building'] . ' - ' . $student['room_no']) : 'Not Allocated' ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">Status: <?php if ($student['is_active']): ?><span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span><?php else: ?><span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactive</span><?php endif; ?></li>
                    </ul>
                    <div class="alert alert-success text-center mt-4"><i class="bi bi-check-circle-fill"></i> Verified Student</div>
                <?php else: ?>
                    <div class="alert alert-danger text-center"><h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Invalid QR Code</h4><p>No student found with this registration number.</p></div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center text-muted small">
                Powered by Residential Hostel System
            </div>
        </div>
    </div>
</body>
</html>