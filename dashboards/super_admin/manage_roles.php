<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Role Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_role'])) {
    $role_key = sanitize($_POST['role_key']);
    $role_name = sanitize($_POST['role_name']);
    $role_id = (int)$_POST['role_id'];

    if ($role_id > 0) {
        $stmt = $pdo->prepare("UPDATE sys_roles SET role_key = ?, role_name = ? WHERE id = ?");
        $stmt->execute([$role_key, $role_name, $role_id]);
        $msg = "Role updated successfully.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO sys_roles (role_key, role_name) VALUES (?, ?)");
        $stmt->execute([$role_key, $role_name]);
        $msg = "New role added.";
    }
    header("Location: manage_roles.php?success=" . urlencode($msg));
    exit;
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM sys_roles WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: manage_roles.php?success=Deleted");
    exit;
}

$roles = $pdo->query("SELECT * FROM sys_roles ORDER BY id ASC")->fetchAll();
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }
    .role-card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.05); margin-top: 10px; border: none; }
    .header-grad { background: linear-gradient(to right, #2ecc71, #1abc9c); padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; color: white; }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    .underline-input { border: none; border-bottom: 2px solid #eee; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; width: 100%; transition: 0.3s; }
    .underline-input:focus { outline: none; border-bottom-color: #1abc9c; }
    .btn-custom { background: #10603b; color: white; border-radius: 50px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-custom:hover { transform: translateY(-2px); opacity: 0.9; }
</style>

<div class="role-card">
    <div class="header-grad">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="m-0 fw-bold">Manage Roles</h2>
        <a href="<?= BASE_URL ?>" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">Home</a>
    </div>

    <div class="p-5">
        <?php if(isset($_GET['success'])): ?><div class="alert alert-success rounded-4"><?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
        
        <form method="post" id="roleForm" class="row g-4 align-items-end mb-5">
            <input type="hidden" name="role_id" id="role_id" value="0">
            <div class="col-md-4">
                <label class="small fw-bold text-muted">ROLE KEY (System Name)</label>
                <input type="text" name="role_key" id="role_key" class="underline-input" placeholder="e.g. manager" required>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold text-muted">DISPLAY NAME</label>
                <input type="text" name="role_name" id="role_name" class="underline-input" placeholder="e.g. Hostel Manager" required>
            </div>
            <div class="col-md-4">
                <button type="submit" name="save_role" id="submitBtn" class="btn-custom shadow-sm">SAVE ROLE</button>
                <button type="reset" class="btn btn-link text-muted" onclick="resetRoleForm()">Clear</button>
            </div>
        </form>

        <div class="table-responsive rounded-4 border shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th class="ps-4">ID</th><th>Key</th><th>Name</th><th class="text-center">Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach($roles as $r): ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?= $r['id'] ?></td>
                        <td class="fw-bold"><?= $r['role_key'] ?></td>
                        <td><?= $r['role_name'] ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm text-primary border-0" onclick="editRole('<?= $r['id'] ?>', '<?= $r['role_key'] ?>', '<?= $r['role_name'] ?>')"><i class="bi bi-pencil-square"></i></button>
                            <?php if($r['role_key'] !== 'super_admin'): ?>
                                <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Delete this role?')"><i class="bi bi-trash3-fill"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editRole(id, key, name) {
        document.getElementById('role_id').value = id;
        document.getElementById('role_key').value = key;
        document.getElementById('role_name').value = name;
        document.getElementById('submitBtn').innerText = "UPDATE ROLE";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function resetRoleForm() {
        document.getElementById('role_id').value = "0";
        document.getElementById('submitBtn').innerText = "SAVE ROLE";
    }
</script>
<?php require_once '../../includes/footer.php'; ?>