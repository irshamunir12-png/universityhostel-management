<?php
require_once '../../core/session.php';
require_once '../../core/functions.php';

// 1. Handle Add/Update User Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = (int)$_POST['user_id'];
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    $reg_no = sanitize($_POST['registration_no']);
    $id_no = sanitize($_POST['identity_no']);
    $is_active = (int)$_POST['is_active'];

    try {
        // Check for duplicate email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND is_deleted = 0");
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            throw new Exception("Email address '$email' is already in use.");
        }

        if ($id > 0) {
            // Update Existing User
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, registration_no = ?, identity_no = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $email, $hash, $role, $reg_no, $id_no, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, registration_no = ?, identity_no = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $reg_no, $id_no, $is_active, $id]);
            }
            $msg = "User updated successfully!";
        } else {
            // Create New User
            if (empty($password)) throw new Exception("Password is required for new users.");
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, registration_no, identity_no, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $role, $reg_no, $id_no, $is_active]);
            $msg = "New user registered successfully!";
        }
        header("Location: manage_users.php?success_msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 2. Handle Delete (Soft Delete)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("UPDATE users SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$id]);
    header("Location: manage_users.php?success_msg=User account deactivated and moved to trash");
    exit;
}

// 3. Fetch Data
$roles = $pdo->query("SELECT * FROM sys_roles ORDER BY role_name")->fetchAll();
$users = $pdo->query("SELECT * FROM users WHERE is_deleted = 0 ORDER BY created_at DESC")->fetchAll();

require_once '../../includes/header.php';
?>

<style>
    .app-content-header, .content-header, .breadcrumb { display: none !important; }
    .app-main { padding-top: 0 !important; }
    .desktop-app-card { background: #ffffff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden; margin-top: 10px; border: none; }
    .app-header-gradient { background: linear-gradient(to right, #2ecc71, #1abc9c); padding: 15px 25px; display: flex; align-items: center; justify-content: space-between; color: white; }
    .app-title-center { font-weight: 850; font-size: 1.8rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    .btn-app-home { background: white; color: #1abc9c !important; font-weight: bold; border-radius: 15px; padding: 5px 20px; text-decoration: none; transition: 0.3s; width: 120px; text-align: center; }
    .form-section-app { padding: 30px; background: #fff; }
    .underline-input { border: none !important; border-bottom: 2px solid #eee !important; border-radius: 0 !important; padding: 12px 5px !important; background: transparent !important; transition: 0.3s; font-weight: 600; width: 100%; box-shadow: none !important; }
    .underline-input:focus { outline: none; border-bottom-color: #1abc9c; }
    .highlight-edit { border-bottom-color: #0d6efd !important; }
    .stats-label { font-size: 0.85rem; color: #888; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 5px; }
    .grid-table th { background: #fdfbff; color: #555; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; }
    .auto-hide { transition: opacity 0.5s ease; }
</style>

<div class="desktop-app-card">
    <div class="app-header-gradient">
        <div class="window-controls d-flex gap-2">
            <span style="width:12px; height:12px; border-radius:50%; background:#ff5f56;"></span>
            <span style="width:12px; height:12px; border-radius:50%; background:#ffbd2e;"></span>
            <span style="width:12px; height:12px; border-radius:50%; background:#009a17;"></span>
        </div>
        <h2 class="app-title-center">Manage Users & Students</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="form-section-app">
        <?php if(isset($error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $error ?></div><?php endif; ?>
        <?php if(isset($_GET['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['success_msg']) ?></div><?php endif; ?>

        <form method="post" id="userForm" class="mb-5">
            <input type="hidden" name="user_id" id="edit_user_id" value="0">
            <div class="row g-4">
                <div class="col-md-3">
                    <label class="stats-label">Full Name</label>
                    <input type="text" name="name" id="u_name" class="form-control underline-input" placeholder="e.g. Ali Ahmed" required>
                </div>
                <div class="col-md-3">
                    <label class="stats-label">Email Address</label>
                    <input type="email" name="email" id="u_email" class="form-control underline-input" placeholder="student@hostel.com" required>
                </div>
                <div class="col-md-3">
                    <label class="stats-label">Password</label>
                    <input type="password" name="password" id="u_password" class="form-control underline-input" placeholder="••••••••">
                    <small class="text-muted" id="passHint" style="display:none;">Leave blank to keep current</small>
                </div>
                <div class="col-md-3">
                    <label class="stats-label">Assign Role</label>
                    <select name="role" id="u_role" class="form-select underline-input" required>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="stats-label">Registration No</label>
                    <input type="text" name="registration_no" id="u_reg" class="form-control underline-input" placeholder="ST-2024-xxx">
                </div>
                <div class="col-md-3">
                    <label class="stats-label">Identity (CNIC)</label>
                    <input type="text" name="identity_no" id="u_idno" class="form-control underline-input" placeholder="35202-xxxxxxx-x">
                </div>
                <div class="col-md-3">
                    <label class="stats-label">Account Status</label>
                    <select name="is_active" id="u_status" class="form-select underline-input">
                        <option value="1">Active / Verified</option>
                        <option value="0">Pending / Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 pt-3">
                    <button type="submit" name="save_user" id="submitBtn" class="btn btn-success rounded-pill px-4 w-100 shadow-sm">SAVE ACCOUNT</button>
                    <button type="submit" name="save_user" id="updateBtn" class="btn btn-primary rounded-pill px-4 w-100 shadow-sm" style="display:none;">UPDATE USER</button>
                    <div class="text-center mt-2" id="cancelContainer" style="display:none;"><a href="javascript:void(0)" onclick="resetUserForm()" class="text-muted small">Cancel Edit</a></div>
                </div>
            </div>
        </form>

        <div class="table-responsive rounded-3 shadow-sm border">
            <table class="table grid-table mb-0">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email & Role</th>
                        <th>Reg / ID</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr onclick='editUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)' style="cursor:pointer;" title="Click to edit">
                        <td><div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div><small class="text-muted">Member since <?= date('M Y', strtotime($u['created_at'])) ?></small></td>
                        <td><div class="text-primary fw-bold"><?= htmlspecialchars($u['email']) ?></div><span class="badge <?= getRoleBadgeColor($u['role']) ?>"><?= strtoupper($u['role']) ?></span></td>
                        <td><div>Reg: <?= htmlspecialchars($u['registration_no'] ?? 'N/A') ?></div><div class="small text-muted">ID: <?= htmlspecialchars($u['identity_no'] ?? 'N/A') ?></div></td>
                        <td><?= $u['is_active'] ? '<span class="text-success"><i class="bi bi-patch-check-fill"></i> Active</span>' : '<span class="text-muted">Inactive</span>' ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm text-primary border-0" title="Edit"><i class="bi bi-pencil-square"></i></button>
                            <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Delete this account?')"><i class="bi bi-trash3-fill"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editUser(u) {
        // Populate hidden ID
        document.getElementById('edit_user_id').value = u.id;
        
        // Populate text fields
        document.getElementById('u_name').value = u.name;
        document.getElementById('u_email').value = u.email;
        document.getElementById('u_reg').value = u.registration_no || "";
        document.getElementById('u_idno').value = u.identity_no || "";
        
        // Populate select dropdowns
        document.getElementById('u_role').value = u.role;
        document.getElementById('u_status').value = u.is_active;
        
        // UI Changes
        document.getElementById('u_password').placeholder = "Leave blank to keep same";
        document.getElementById('passHint').style.display = "block";
        document.getElementById('submitBtn').style.display = "none";
        document.getElementById('updateBtn').style.display = "inline-block";
        document.getElementById('cancelContainer').style.display = "block";
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetUserForm() {
        document.getElementById('userForm').reset();
        document.getElementById('edit_user_id').value = "0";
        document.getElementById('u_password').placeholder = "••••••••";
        document.getElementById('passHint').style.display = "none";
        document.getElementById('submitBtn').style.display = "inline-block";
        document.getElementById('updateBtn').style.display = "none";
        document.getElementById('cancelContainer').style.display = "none";
    }

    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.style.display = 'none', 500);
        });
    }, 5000);
</script>

<?php require_once '../../includes/footer.php'; ?>