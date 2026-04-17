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

$message = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    // Update users table
    $stmt = $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?');
    $stmt->execute([$name, $email, $user_id]);

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
                    <p class="text-muted small">This QR Code requires an internet connection to display.</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($user['registration_no'] ?? 'NoData') ?>" 
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

                <form method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
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
                        <hr class="my-3">
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                        </div>
                         <div class="col-md-12 mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>