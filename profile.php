<?php
require_once 'includes/header.php'; // Use main header

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Fetch user & profile data
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

$profileStmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
$profileStmt->execute([$user_id]);
$profile = $profileStmt->fetch();

// Fetch warnings for student
$warnings = [];
if ($user['role'] === 'student') {
    try {
        $stmt = $pdo->prepare("
            SELECT sw.*, u.name as issuer_name 
            FROM student_warnings sw 
            LEFT JOIN users u ON sw.issued_by_id = u.id 
            WHERE sw.user_id = ? 
            ORDER BY sw.issued_at DESC
        ");
        $stmt->execute([$user_id]);
        $warnings = $stmt->fetchAll();
    } catch (Exception $e) {}
}

$message = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    // Handle Avatar Upload
    $avatarPath = $user['avatar']; // Keep old avatar by default
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $uploadDir = 'assets/uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $newFilename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newFilename);
            $avatarPath = $uploadDir . $newFilename;
            $_SESSION['avatar'] = BASE_URL . $avatarPath; // Update Session immediately
        }
    }

    // Update users table
    $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, avatar=? WHERE id=?');
    $stmt->execute([$name, $email, $avatarPath, $user_id]);

    // Update or insert student_profiles table
    if ($profile) {
        $stmt = $pdo->prepare('UPDATE student_profiles SET phone=?, address=? WHERE user_id=?');
        $stmt->execute([$phone, $address, $user_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, phone, address) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $phone, $address]);
    }
    
    // Refresh session name
    $_SESSION['name'] = $name;

    header('Location: profile.php?msg=updated');
    exit;
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (password_verify($current_pass, $user['password'])) {
        if (!empty($new_pass) && $new_pass === $confirm_pass) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
            header('Location: profile.php?msg=pw_changed');
            exit;
        } else {
            $error = "New passwords do not match or are empty.";
        }
    } else {
        $error = "Incorrect current password.";
    }
}

// Display messages from GET params
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') $message = 'Profile updated successfully!';
    if ($_GET['msg'] == 'pw_changed') $message = 'Password changed successfully!';
}
?>

<div class="row">
    <div class="col-md-4">
        <!-- Digital ID Card -->
        <div class="card card-success card-outline">
            <div class="card-header text-center"><h5 class="card-title m-0">Digital Student ID</h5></div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <img src="<?= !empty($user['avatar']) ? BASE_URL . $user['avatar'] : BASE_URL . 'assets/img/avatar.png' ?>" 
                         class="profile-user-img img-fluid img-circle" alt="User profile picture" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <div class="mb-3">
                    <p class="text-muted small">Scan this code to verify student identity.</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode(BASE_URL . 'verify_student.php?reg_no=' . ($user['registration_no'] ?? '')) ?>" 
                         alt="Student QR Code" class="img-thumbnail p-2" style="width: 160px; height: 160px;">
                </div>
                <h4 class="profile-username text-center"><?= htmlspecialchars($user['name']) ?></h4>
                <p class="text-muted text-center"><?= htmlspecialchars($user['registration_no'] ?? 'N/A') ?></p>
                
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Role</b> <span class="float-end badge bg-primary"><?= ucfirst($_SESSION['role']) ?></span>
                    </li>
                    <li class="list-group-item">
                        <b>Status</b> <span class="float-end badge bg-success">Active</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Password Change Card -->
        <div class="card card-warning card-outline">
            <div class="card-header"><h3 class="card-title">Change Password</h3></div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Profile Edit Form -->
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">My Profile Details</h3></div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="e.g. Ali Khan" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="e.g. ali.khan@example.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Registration No</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['registration_no'] ?? '') ?>" disabled>
                            <small class="text-muted">Cannot be changed.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Identity No (CNIC)</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['identity_no'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Profile Picture</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                        <hr class="my-3">
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="e.g. 0300-1234567">
                        </div>
                         <div class="col-md-12 mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control" placeholder="e.g. House #123, Street 4, G-10/2, Islamabad"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>

        <!-- Warnings Card (Student Only) -->
        <?php if ($user['role'] === 'student' && !empty($warnings)): ?>
        <div class="card card-danger card-outline mt-4">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-exclamation-triangle-fill"></i> Disciplinary Warnings</h3></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach($warnings as $w): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-danger">Warning Issued</h6>
                            <small><?= date('d M Y', strtotime($w['issued_at'])) ?></small>
                        </div>
                        <p class="mb-1"><?= htmlspecialchars($w['warning_text']) ?></p>
                        <small class="text-muted">Issued by: <?= htmlspecialchars($w['issuer_name'] ?? 'Admin') ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>