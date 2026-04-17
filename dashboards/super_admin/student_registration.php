<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Reuse Student Registration Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_student'])) {
    // logic reused from manage_users.php
    $name = sanitize($_POST['name']);
    $reg_no = sanitize($_POST['reg_no']);
    $email = strtolower(str_replace(' ', '', $name)) . rand(10, 99) . "@hostel.com";
    $password = password_hash("123456", PASSWORD_DEFAULT);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password, registration_no, is_active) VALUES (?, ?, 'student', ?, ?, 1)");
        $stmt->execute([$name, $email, $password, $reg_no]);
        $uid = $pdo->lastInsertId();
        
        $pdo->prepare("INSERT INTO student_profiles (user_id, phone, gender, address) VALUES (?, ?, ?, ?)")
            ->execute([$uid, sanitize($_POST['phone']), sanitize($_POST['gender']), sanitize($_POST['address'])]);
        
        $pdo->commit();
        $success = "Student <strong>$name</strong> registered successfully!";
    } catch (Exception $e) { $pdo->rollBack(); $error = "Registration failed: " . $e->getMessage(); }
}
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }
    .reg-card { background: #fff; border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.08); max-width: 900px; margin: 20px auto; overflow: hidden; border: none; }
    .header-grad { background: linear-gradient(to right, #2ecc71, #1abc9c); padding: 25px; color: white; display: flex; justify-content: space-between; align-items: center; }
    .underline-input { border: none; border-bottom: 2px solid #eee; border-radius: 0; padding: 12px 5px; background: transparent; font-weight: 600; width: 100%; transition: 0.3s; }
    .underline-input:focus { outline: none; border-bottom-color: #10603b; }
    .label-min { font-size: 0.75rem; font-weight: 800; color: #888; text-transform: uppercase; margin-bottom: 5px; display: block; }
    .btn-reg { background: #10603b; color: white; border-radius: 50px; padding: 15px 50px; border: none; font-weight: 800; letter-spacing: 1px; transition: 0.3s; }
    .btn-reg:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(16, 96, 59, 0.2); }
</style>

<div class="reg-card">
    <div class="header-grad">
        <div class="window-controls d-flex gap-2">
            <div class="win-dot dot-r" style="width:12px; height:12px; border-radius:50%; background:#ff5f56;"></div>
            <div class="win-dot dot-y" style="width:12px; height:12px; border-radius:50%; background:#ffbd2e;"></div>
            <div class="win-dot dot-g" style="width:12px; height:12px; border-radius:50%; background:#009a17;"></div>
        </div>
        <h3 class="m-0 fw-bold">New Student Admission</h3>
        <a href="<?= BASE_URL ?>" class="btn btn-sm btn-light rounded-pill px-3">Dashboard</a>
    </div>

    <div class="p-5">
        <?php if(isset($success)): ?><div class="alert alert-success rounded-4"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger rounded-4"><?= $error ?></div><?php endif; ?>

        <form method="post" class="row g-5">
            <div class="col-md-6">
                <label class="label-min">Full Name</label>
                <input type="text" name="name" class="underline-input" placeholder="Enter student full name" required>
            </div>
            <div class="col-md-6">
                <label class="label-min">Registration Number</label>
                <input type="text" name="reg_no" class="underline-input" placeholder="e.g. 2024-UNIV-001" required>
            </div>
            <div class="col-md-6">
                <label class="label-min">Contact Number</label>
                <input type="text" name="phone" class="underline-input" placeholder="03XXXXXXXXX">
            </div>
            <div class="col-md-6">
                <label class="label-min">Gender</label>
                <select name="gender" class="underline-input">
                    <option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
                </select>
            </div>
            <div class="col-12">
                <label class="label-min">Permanent Address</label>
                <textarea name="address" class="underline-input" rows="2" placeholder="Full home address..."></textarea>
            </div>
            <div class="col-12 text-center pt-4">
                <button type="submit" name="reg_student" class="btn-reg shadow">REGISTER & ENROLL</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>