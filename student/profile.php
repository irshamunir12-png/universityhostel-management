<?php
require_once '../core/db.php';
require_once '../core/functions.php';
// You may want to include session.php to check login
require_once '../core/session.php';

// Get logged-in user ID (assuming it's stored in session)
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../login.php');
    exit;
}

// Fetch user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch student profile info
$stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $identity_no = sanitize($_POST['identity_no'] ?? '');
    $registration_no = sanitize($_POST['registration_no'] ?? '');
    $dob = sanitize($_POST['date_of_birth'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $guardian_name = sanitize($_POST['guardian_name'] ?? '');
    $guardian_contact = sanitize($_POST['guardian_contact'] ?? '');

    // Update users table
    $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, identity_no=?, registration_no=? WHERE id=?');
    $stmt->execute([$name, $email, $identity_no, $registration_no, $user_id]);

    // Update or insert student_profiles table
    $stmt = $pdo->prepare('SELECT id FROM student_profiles WHERE user_id = ?');
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare('UPDATE student_profiles SET date_of_birth=?, gender=?, address=?, phone=?, guardian_name=?, guardian_contact=? WHERE user_id=?');
        $stmt->execute([$dob, $gender, $address, $phone, $guardian_name, $guardian_contact, $user_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, date_of_birth, gender, address, phone, guardian_name, guardian_contact) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $dob, $gender, $address, $phone, $guardian_name, $guardian_contact]);
    }
    $message = 'Profile updated successfully!';
    // Refresh data
    header('Location: profile.php?updated=1');
    exit;
}
if (isset($_GET['updated'])) {
    $message = 'Profile updated successfully!';
    // Re-fetch updated data
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="container mt-5">
    <div class="row">
        <!-- Left Column: Edit Profile Form -->
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title">Edit Profile</h3></div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Identity No</label>
                                <input type="text" name="identity_no" class="form-control" value="<?= htmlspecialchars($user['identity_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Registration No</label>
                                <input type="text" name="registration_no" class="form-control" value="<?= htmlspecialchars($user['registration_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Male" <?= (isset($profile['gender']) && $profile['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= (isset($profile['gender']) && $profile['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= (isset($profile['gender']) && $profile['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Address</label>
                                <textarea name="address" class="form-control"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Guardian Name</label>
                                <input type="text" name="guardian_name" class="form-control" value="<?= htmlspecialchars($profile['guardian_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Guardian Contact</label>
                                <input type="text" name="guardian_contact" class="form-control" value="<?= htmlspecialchars($profile['guardian_contact'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Digital ID Card (QR Code) -->
        <div class="col-md-4">
            <div class="card card-success card-outline">
                <div class="card-header text-center">
                    <h5 class="card-title m-0">Digital Student ID</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <!-- QR Code API (Generates QR based on Reg No) -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($user['registration_no'] ?? 'NoData') ?>" 
                             alt="Student QR" class="img-thumbnail p-2" style="width: 160px; height: 160px;">
                    </div>
                    <h4 class="profile-username text-center"><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted text-center"><?= htmlspecialchars($user['registration_no']) ?></p>
                    
                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Role</b> <a class="float-end badge bg-primary">Student</a>
                        </li>
                        <li class="list-group-item">
                            <b>Status</b> <a class="float-end badge bg-success">Active</a>
                        </li>
                    </ul>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-success btn-sm"><i class="bi bi-qr-code"></i> Download ID</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/adminlte.min.js"></script>
</body>
</html>
