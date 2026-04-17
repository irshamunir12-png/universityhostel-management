<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';
// Fetch Roles for Dropdown
$roles = $pdo->query("SELECT * FROM sys_roles")->fetchAll();

// Auto-Fix: Ensure 'phone' column exists in users table (required for staff)
try {
    $pdo->query("SELECT phone FROM users LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(25) DEFAULT NULL AFTER email");
}

// Auto-Fix: Ensure student_profiles has unique user_id index for reliable updates
try {
    $pdo->exec("ALTER TABLE student_profiles ADD UNIQUE INDEX (user_id)");
} catch (Exception $e) { /* Index already exists or table not ready */ }

// Handle Save Student (Logic Integrated with Profiles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student'])) {
    $name = sanitize($_POST['name']);
    $reg_no = sanitize($_POST['student_id']);
    $uid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $address = sanitize($_POST['address']);
    $contact = sanitize($_POST['contact']);
    $dob = sanitize($_POST['dob']);
    $gender = sanitize($_POST['gender']);
    $password = password_hash("123456", PASSWORD_DEFAULT);

    $pdo->beginTransaction();
    try {
        if ($uid > 0) {
            // Check duplicate registration_no for other users
            $check = $pdo->prepare("SELECT id FROM users WHERE registration_no = ? AND id != ? AND is_deleted = 0");
            $check->execute([$reg_no, $uid]);
            if ($check->rowCount() > 0) throw new Exception("Registration number already exists.");

            // UPDATE existing student
            $stmt = $pdo->prepare("UPDATE users SET name = ?, registration_no = ? WHERE id = ?");
            $stmt->execute([$name, $reg_no, $uid]);

            $stmt2 = $pdo->prepare("INSERT INTO student_profiles (user_id, phone, gender, date_of_birth, address) 
                                    VALUES (?, ?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE phone=VALUES(phone), gender=VALUES(gender), date_of_birth=VALUES(date_of_birth), address=VALUES(address)");
            $stmt2->execute([$uid, $contact, $gender, $dob, $address]);
            $msg = "Student Updated Successfully!";
        } else {
            $email = strtolower(str_replace(' ', '', $name)) . rand(10, 99) . "@hostel.com";
            // INSERT new student
            $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password, registration_no, is_active) VALUES (?, ?, 'student', ?, ?, 1)");
            $stmt->execute([$name, $email, $password, $reg_no]);
            $new_user_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO student_profiles (user_id, phone, gender, date_of_birth, address) VALUES (?, ?, ?, ?, ?)");
            $stmt2->execute([$new_user_id, $contact, $gender, $dob, $address]);
            $msg = "Student Registered Successfully!";
        }

        $pdo->commit();
        header("Location: manage_users.php?std_msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $student_error = $e->getMessage();
    }
}

// Handle Save Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
    $name = sanitize($_POST['staff_name']);
    $email = sanitize($_POST['staff_email']);
    $role = sanitize($_POST['staff_role']);
    $contact = sanitize($_POST['staff_contact']);
    $uid = isset($_POST['staff_user_id']) ? (int)$_POST['staff_user_id'] : 0;
    $password = password_hash("123456", PASSWORD_DEFAULT); // Default password for staff

    $pdo->beginTransaction();
    try {
        // Check if email already exists for another user
        $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND is_deleted = 0");
        $check_email->execute([$email, $uid]);
        if ($check_email->rowCount() > 0) {
            throw new Exception("Email already exists for another user.");
        }

        if ($uid > 0) {
            // UPDATE existing staff
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $contact, $uid]);
            $msg = "Staff Member Updated Successfully!";
        } else {
            // INSERT new staff
            $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password, phone, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $email, $role, $password, $contact]);
            $msg = "Staff Member Registered Successfully!";
        }
        $pdo->commit();
        header("Location: manage_users.php?stf_msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $staff_error = $e->getMessage();
    }
}
// Handle Password Reset
if (isset($_POST['reset_password'])) {
    $uid = (int)$_POST['user_id'];
    $new_pass = $_POST['new_password'];
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $uid]);
    $success = "Password reset successfully for User ID: $uid";
}

// Handle Soft Delete User
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    $pdo->prepare("UPDATE users SET is_deleted = 1, is_active = 0, deleted_at = NOW() WHERE id = ?")->execute([$id]);
    header("Location: manage_users.php?msg=deleted");
    exit;
}

// Fetch Students with their Profiles
$students = $pdo->query("
    SELECT u.id, u.name, u.registration_no, u.email, sp.address, sp.phone, sp.date_of_birth, sp.gender 
    FROM users u 
    LEFT JOIN student_profiles sp ON u.id = sp.user_id 
    WHERE u.role = 'student' AND u.is_deleted = 0 
    ORDER BY u.id DESC
")->fetchAll();
?>
<?php
// Fetch Staff Members
$staff_members = $pdo->query("
    SELECT u.id, u.name, u.email, u.phone, sr.role_name
    FROM users u
    JOIN sys_roles sr ON u.role = sr.role_key
    WHERE u.role != 'student' AND u.is_deleted = 0 ORDER BY u.name")->fetchAll();
?>

<style>
    /* Hide dashboard breadcrumb bar entirely as requested */
    .app-content-header, .content-header, .breadcrumb { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .desktop-app-wrapper {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        border: none;
        margin-top: 10px;
    }
    .app-header-teal {
        background: linear-gradient(to right, #2ecc71, #1abc9c);
 !important;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        color: white;
    }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }

    .app-header-teal > div, .app-header-teal > a { flex: 1; }
    .app-title-purple {
        color: #f9f9f9; /* Purple */
        font-weight: 800;
        font-size: 1.7rem;
        margin: 0;
        text-align: center;
        flex: 2 !important;
    }
    .btn-app-home {
        background: white;
        color: #20c997 !important;
        font-weight: bold;
        border-radius: 15px;
        padding: 5px 20px;
        text-decoration: none;
        text-align: center;
    }
    
    .form-section-app { padding: 30px; background: #fff; }
    .underline-input {
        border: none;
        border-bottom: 2px solid #ddd;
        border-radius: 0;
        padding: 8px 0;
        background: transparent;
        transition: 0.3s;
    }
    .underline-input:focus {
        box-shadow: none;
        border-bottom-color: #6f42c1;
    }
    /* Error styling for invalid contact numbers */
    .underline-input:invalid {
        border-bottom-color: #dc3545 !important;
        color: #dc3545 !important;
    }
    .underline-input:invalid::placeholder { color: #dc3545; opacity: 0.7; }

    .app-title-purple.small-title {
        font-size: 1.4rem;
    }

    .btn-save-green { background-color: #198754; color: white; border-radius: 20px; padding: 10px 30px; border: none; font-weight: 600; }
    .btn-delete-pink { background-color: #ff8787; color: white; border-radius: 20px; padding: 10px 30px; border: none; font-weight: 600; }
    .btn-new-blue { background-color: #0d47a1; color: white; border-radius: 20px; padding: 10px 30px; border: none; font-weight: 600; }

    .app-table-container { padding: 0 30px 30px; }
    .grid-table { border: 1px solid #eee; }
    .grid-table th { background: #fdfbff; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
    .grid-table td { vertical-align: middle; }
</style>

<div class="desktop-app-wrapper">
    <!-- Top Header Bar -->
    <div class="app-header-teal">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="app-title-purple">MANAGE STUDENT</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <!-- Form Section -->
    <div class="form-section-app">
        <?php if(isset($student_error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $student_error ?></div><?php endif; ?>
        <?php if(isset($_GET['std_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['std_msg']) ?></div><?php endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?><div class="alert alert-info rounded-3 auto-hide">Record moved to trash.</div><?php endif; ?>
        
        <form method="post" id="appStudentForm">
            <input type="hidden" name="user_id" id="edit_user_id" value="0">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Student ID</label>
                    <input type="text" name="student_id" class="form-control underline-input" placeholder="Enter Registration No" required>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Name</label>
                    <input type="text" name="name" class="form-control underline-input" placeholder="Full Student Name" required>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Address</label>
                    <input type="text" name="address" class="form-control underline-input" placeholder="Permanent Home Address">
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Contact</label>
                    <input type="tel" name="contact" class="form-control underline-input" placeholder="Phone (e.g. 03001234567)" pattern="[0-9]{10,15}" required title="Numbers only, 10-15 digits">
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Date of Birth</label>
                    <input type="date" name="dob" class="form-control underline-input">
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Gender</label>
                    <select name="gender" class="form-select underline-input">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Any">Other</option>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-5 d-flex gap-3">
                <button type="submit" name="save_student" id="mainSubmitBtn" class="btn-save-green shadow-sm">ACCEPT & SAVE</button>
                <button type="button" class="btn-delete-pink shadow-sm" onclick="handleAppDelete()">DELETE</button>
                <button type="reset" class="btn-new-blue shadow-sm">+ NEW STUDENT</button>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="app-table-container">
        <div class="table-responsive rounded-3 shadow-sm" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover grid-table mb-0" id="studentAppTable">
                <thead class="sticky-top">
                    <tr>
                        <th class="ps-3">Student ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>DOB</th>
                        <th>Gender</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): ?>
                    <tr onclick="fillAppForm('<?= $s['id'] ?>', '<?= $s['registration_no'] ?>', '<?= $s['name'] ?>', '<?= $s['address'] ?>', '<?= $s['phone'] ?>', '<?= $s['date_of_birth'] ?>', '<?= $s['gender'] ?>')" style="cursor:pointer;">
                        <td class="ps-3 fw-bold"><?= htmlspecialchars($s['registration_no'] ?? 'N/A') ?></td>
                        <td class="text-dark"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="small"><?= htmlspecialchars($s['address'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                        <td><?= $s['date_of_birth'] ? date('d M Y', strtotime($s['date_of_birth'])) : '-' ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $s['gender'] ?></span></td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary border-0" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                <a href="?delete_user=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Delete Student?')" title="Delete"><i class="bi bi-trash3-fill"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Staff Management Section -->
<div class="desktop-app-wrapper mt-5">
    <!-- Top Header Bar for Staff -->
    <div class="app-header-teal">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="app-title-purple small-title">MANAGE STAFF</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <!-- Staff Form Section -->
    <div class="form-section-app">
        <?php if(isset($staff_error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $staff_error ?></div><?php endif; ?>
        <?php if(isset($_GET['stf_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['stf_msg']) ?></div><?php endif; ?>
        
        <form method="post" id="appStaffForm">
            <input type="hidden" name="staff_user_id" id="edit_staff_user_id" value="0">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Name</label>
                    <input type="text" name="staff_name" class="form-control underline-input" placeholder="Full Staff Name" required>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Email</label>
                    <input type="email" name="staff_email" class="form-control underline-input" placeholder="Staff Email" required>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Contact</label>
                    <input type="tel" name="staff_contact" class="form-control underline-input" placeholder="Phone (e.g. 03001234567)" pattern="[0-9]{10,15}" required title="Numbers only, 10-15 digits">
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Role</label>
                    <select name="staff_role" class="form-select underline-input" required>
                        <?php foreach($roles as $r): 
                            if ($r['role_key'] === 'student') continue; // Students are managed separately
                        ?>
                            <option value="<?= $r['role_key'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-5 d-flex gap-3">
                <button type="submit" name="save_staff" id="mainStaffSubmitBtn" class="btn-save-green shadow-sm">ACCEPT & SAVE STAFF</button>
                <button type="button" class="btn-delete-pink shadow-sm" onclick="handleStaffDelete()">DELETE STAFF</button>
                <button type="reset" class="btn-new-blue shadow-sm">+ NEW STAFF</button>
            </div>
        </form>
    </div>

    <!-- Staff Table Section -->
    <div class="app-table-container">
        <div class="table-responsive rounded-3 shadow-sm" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover grid-table mb-0" id="staffAppTable">
                <thead class="sticky-top">
                    <tr>
                        <th class="ps-3">Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($staff_members as $staff): ?>
                    <tr onclick="fillStaffForm('<?= $staff['id'] ?>', '<?= $staff['name'] ?>', '<?= $staff['email'] ?>', '<?= $staff['phone'] ?>', '<?= $staff['role_name'] ?>')" style="cursor:pointer;">
                        <td class="ps-3 fw-bold"><?= htmlspecialchars($staff['name']) ?></td>
                        <td class="text-dark"><?= htmlspecialchars($staff['email']) ?></td>
                        <td class="small"><?= htmlspecialchars($staff['phone'] ?? '-') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($staff['role_name']) ?></span></td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary border-0" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                <a href="?delete_user=<?= $staff['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Delete Staff Member?')" title="Delete"><i class="bi bi-trash3-fill"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Auto-hide alerts after 5 seconds to keep UI clean
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => {
            el.style.display = 'none';
        });
    }, 5000);

    // Desktop Clock
    function updateAppClock() {
        const now = new Date();
        document.getElementById('app-clock').innerText = now.toLocaleString('en-US', { 
            weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' 
        });
    }
    setInterval(updateAppClock, 1000);
    updateAppClock();

    // Row Click Fill Logic
    function fillAppForm(uid, reg, name, addr, contact, dob, gender) {
        const form = document.getElementById('appStudentForm');
        document.getElementById('edit_user_id').value = uid;
        form.student_id.value = reg;
        form.name.value = name;
        form.address.value = addr;
        form.contact.value = contact;
        form.dob.value = dob;
        form.gender.value = gender;
        document.getElementById('mainSubmitBtn').innerText = "UPDATE STUDENT";
    }

    // Staff Row Click Fill Logic
    function fillStaffForm(uid, name, email, contact, role) {
        const form = document.getElementById('appStaffForm');
        document.getElementById('edit_staff_user_id').value = uid;
        form.staff_name.value = name;
        form.staff_email.value = email;
        form.staff_contact.value = contact;
        // Find the option by text content if role_name is used for display
        Array.from(form.staff_role.options).forEach(option => {
            if (option.text === role) {
                option.selected = true;
            }
        });
        document.getElementById('mainStaffSubmitBtn').innerText = "UPDATE STAFF";
    }

    // Reset Staff Form
    document.querySelector('#appStaffForm button[type="reset"]').addEventListener('click', function() {
        document.getElementById('edit_staff_user_id').value = "0";
        document.getElementById('mainStaffSubmitBtn').innerText = "ACCEPT & SAVE STAFF";
    });

    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        document.getElementById('edit_user_id').value = "0";
        document.getElementById('mainSubmitBtn').innerText = "ACCEPT & SAVE";
    });

    function handleAppDelete() {
        alert('Please use the trash icon in the table list to delete a specific student record.');
    }

    function handleStaffDelete() {
        alert('Please use the trash icon in the table list to delete a specific staff record.');
    }
</script>
<?php require_once '../../includes/footer.php'; ?>