
<?php
require_once '../core/db.php';
require_once '../core/functions.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT);
    $identity_no = sanitize($_POST['identity_no'] ?? '');
    $registration_no = sanitize($_POST['registration_no'] ?? '');
    $dob = sanitize($_POST['date_of_birth'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $guardian_name = sanitize($_POST['guardian_name'] ?? '');
    $guardian_contact = sanitize($_POST['guardian_contact'] ?? '');

    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $message = 'Email already registered.';
    } else {
        // Insert into users table
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, identity_no, registration_no, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([$name, $email, $password, 'student', $identity_no, $registration_no]);
        $user_id = $pdo->lastInsertId();

        // Insert into student_profiles table
        $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, date_of_birth, gender, address, phone, guardian_name, guardian_contact) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $dob, $gender, $address, $phone, $guardian_name, $guardian_contact]);

        $message = 'Registration successful!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="container mt-5">
    <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title">Student Registration</h3></div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-info"><?= $message ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Identity No</label>
                        <input type="text" name="identity_no" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Registration No</label>
                        <input type="text" name="registration_no" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Guardian Name</label>
                        <input type="text" name="guardian_name" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Guardian Contact</label>
                        <input type="text" name="guardian_contact" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/adminlte.min.js"></script>
</body>
</html>
