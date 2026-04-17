<?php
require_once 'header.php';

// Handle AJAX update
if (isset($_POST['toggle_access'])) {
    $role = $_POST['role_key'];
    $page = (int)$_POST['page_id'];
    $status = $_POST['status'];

    if ($status == 'grant') {
        $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)")->execute([$role, $page]);
    } else {
        $pdo->prepare("DELETE FROM role_access WHERE role_key = ? AND page_id = ?")->execute([$role, $page]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// Fetch all roles and pages
$roles = $pdo->query("SELECT * FROM sys_roles WHERE role_key != 'super_admin'")->fetchAll();
$pages = $pdo->query("SELECT * FROM sys_pages ORDER BY parent_id, sort_order")->fetchAll();

// Fetch current access matrix
$access = [];
$accessData = $pdo->query("SELECT * FROM role_access")->fetchAll();
foreach ($accessData as $row) {
    $access[$row['role_key']][$row['page_id']] = true;
}
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-grid-3x3"></i> Role-Permission Matrix</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>System Pages / Modules</th>
                        <?php foreach($roles as $r): ?>
                            <th class="text-center"><?= ucfirst($r['role_name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pages as $p): ?>
                        <tr>
                            <td>
                                <?= ($p['parent_id'] != 0) ? '— ' : '<strong>' ?>
                                <?= htmlspecialchars($p['page_name']) ?>
                                <?= ($p['parent_id'] != 0) ? '' : '</strong>' ?>
                                <br><small class="text-muted"><?= $p['page_url'] ?></small>
                            </td>
                            <?php foreach($roles as $r): ?>
                                <td class="text-center align-middle">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input access-toggle" type="checkbox" 
                                            data-role="<?= $r['role_key'] ?>" 
                                            data-page="<?= $p['id'] ?>"
                                            <?= isset($access[$r['role_key']][$p['id']]) ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.access-toggle').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const data = new FormData();
        data.append('toggle_access', 1);
        data.append('role_key', this.dataset.role);
        data.append('page_id', this.dataset.page);
        data.append('status', this.checked ? 'grant' : 'revoke');

        fetch(window.location.href, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(res => {
            if(!res.success) alert('Failed to update permission');
        });
    });
});
</script>
<?php require_once 'footer.php'; ?>