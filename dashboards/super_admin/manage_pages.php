<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Add/Edit Page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    $name = sanitize($_POST['page_name']);
    $url = sanitize($_POST['page_url']);
    $icon = sanitize($_POST['icon_class']);
    $parent = (int)$_POST['parent_id'];
    $sort = (int)$_POST['sort_order'];
    $pid = (int)$_POST['pid'];

    if ($pid > 0) {
        $stmt = $pdo->prepare("UPDATE sys_pages SET parent_id=?, page_name=?, page_url=?, icon_class=?, sort_order=? WHERE id=?");
        $stmt->execute([$parent, $name, $url, $icon, $sort, $pid]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$parent, $name, $url, $icon, $sort]);
    }
    header("Location: manage_pages.php?success=1");
    exit;
}

$pages = $pdo->query("SELECT * FROM sys_pages ORDER BY parent_id, sort_order")->fetchAll();
$parents = $pdo->query("SELECT id, page_name FROM sys_pages WHERE parent_id = 0")->fetchAll();
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }
    .page-card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.05); margin-top: 10px; border: none; }
    .header-grad { background: linear-gradient(to right, #2ecc71, #1abc9c); padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; color: white; }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    .underline-input { border: none; border-bottom: 2px solid #eee; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; font-size: 0.9rem; width: 100%; transition: 0.3s; }
    .underline-input:focus { outline: none; border-bottom-color: #1abc9c; }
    .btn-custom { background: #10603b; color: white; border-radius: 50px; padding: 10px 30px; border: none; font-weight: 700; }
</style>

<div class="page-card">
    <div class="header-grad">
        <div class="window-controls"><div class="win-dot dot-r"></div><div class="win-dot dot-y"></div><div class="win-dot dot-g"></div></div>
        <h2 class="m-0 fw-bold">System Menu Manager</h2>
        <a href="<?= BASE_URL ?>" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">Home</a>
    </div>

    <div class="p-5">
        <form method="post" id="pageForm" class="row g-4 mb-5 border-bottom pb-5">
            <input type="hidden" name="pid" id="pid" value="0">
            <div class="col-md-3"><label class="small fw-bold text-muted">PAGE NAME</label><input type="text" name="page_name" id="page_name" class="underline-input" required></div>
            <div class="col-md-3"><label class="small fw-bold text-muted">PAGE URL</label><input type="text" name="page_url" id="page_url" class="underline-input" required></div>
            <div class="col-md-2"><label class="small fw-bold text-muted">ICON (Bootstrap)</label><input type="text" name="icon_class" id="icon_class" class="underline-input" placeholder="bi bi-link"></div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted">PARENT CATEGORY</label>
                <select name="parent_id" id="parent_id" class="underline-input">
                    <option value="0">None (Is Category)</option>
                    <?php foreach($parents as $p): ?><option value="<?= $p['id'] ?>"><?= $p['page_name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1"><label class="small fw-bold text-muted">SORT</label><input type="number" name="sort_order" id="sort_order" class="underline-input" value="0"></div>
            <div class="col-md-1 pt-4"><button type="submit" name="save_page" id="submitBtn" class="btn-custom shadow-sm"><i class="bi bi-save"></i></button></div>
        </form>

        <div class="table-responsive rounded-4 border">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th class="ps-4">Page/Category Name</th><th>URL</th><th>Icon</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($pages as $p): ?>
                    <tr>
                        <td class="ps-4">
                            <?php if($p['parent_id'] != 0): ?><span class="text-muted">↳</span><?php endif; ?>
                            <span class="<?= ($p['parent_id'] == 0) ? 'fw-bold text-primary' : '' ?>"><?= $p['page_name'] ?></span>
                        </td>
                        <td class="small text-muted"><?= $p['page_url'] ?></td>
                        <td><i class="<?= $p['icon_class'] ?>"></i></td>
                        <td>
                            <button class="btn btn-sm text-primary border-0" onclick='editPage(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'><i class="bi bi-pencil-square"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editPage(p) {
        document.getElementById('pid').value = p.id;
        document.getElementById('page_name').value = p.page_name;
        document.getElementById('page_url').value = p.page_url;
        document.getElementById('icon_class').value = p.icon_class;
        document.getElementById('parent_id').value = p.parent_id;
        document.getElementById('sort_order').value = p.sort_order;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>
<?php require_once '../../includes/footer.php'; ?>