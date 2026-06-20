<?php
require_once '../../core/session.php';
require_once '../../core/functions.php';

// 1. Table Creation (Agar table na bani ho)
$pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    expiry_date DATE DEFAULT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// --- DATABASE REPAIR (Fixes the Undefined Key Error) ---
try {
    $pdo->query("SELECT expiry_date FROM announcements LIMIT 1");
} catch (Exception $e) {
    // If expiry_date is missing, check if it was named expires_at
    try {
        $pdo->query("SELECT expires_at FROM announcements LIMIT 1");
        $pdo->exec("ALTER TABLE announcements CHANGE expires_at expiry_date DATE DEFAULT NULL;");
    } catch (Exception $e2) {
        $pdo->exec("ALTER TABLE announcements ADD COLUMN expiry_date DATE DEFAULT NULL AFTER content;");
    }
}

// Ensure deleted_at exists for Trash feature
try {
    $pdo->query("SELECT deleted_at FROM announcements LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE announcements ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER is_deleted");
}

// 2. Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
    $id = (int)$_POST['ann_id'];
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $user_id = $_SESSION['user_id'];

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, expiry_date = ? WHERE id = ?");
        $stmt->execute([$title, $content, $expiry, $id]);
        $msg = "Announcement updated!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (user_id, title, content, expiry_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $content, $expiry]);
        $msg = "Announcement posted successfully!";
    }
    header("Location: manage_announcements.php?success_msg=" . urlencode($msg));
    exit;
}

if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE announcements SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: manage_announcements.php?success_msg=Deleted successfully");
    exit;
}

// 3. Fetch Data
$announcements = $pdo->query("SELECT * FROM announcements WHERE is_deleted = 0 ORDER BY created_at DESC")->fetchAll();

require_once '../../includes/header.php';
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .ann-dashboard { background: #fcfcfc; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 50px rgba(0,0,0,0.08); margin-top: 10px; border: none; }
    .ann-header {         background: linear-gradient(to right, #2ecc71, #1abc9c);
 !important; padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; color: white; }
    
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }

    .header-title { font-weight: 800; font-size: 1.6rem; text-transform: uppercase; margin: 0; letter-spacing: 1px; color: #fff; flex-grow: 1; text-align: center; }
    .btn-home { background: white; color: #10603b !important; font-weight: bold; border-radius: 12px; padding: 8px 25px; text-decoration: none; transition: 0.3s; }

    .form-container { padding: 40px; background: #fff; border-bottom: 1px solid #f1f1f1; }
    .label-min { font-size: 0.75rem; font-weight: 800; color: #333; text-transform: uppercase; display: block; margin-bottom: 5px; }
    
    .underline-input { border: none; border-bottom: 2px solid #eaedf0; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; color: #333; transition: 0.3s; width: 100%; }
    .underline-input:focus { box-shadow: none; outline: none; border-bottom-color: #10603b; }
    
    .btn-post { background: #198754; color: white; border: none; border-radius: 50px; padding: 12px 40px; font-weight: 700; transition: 0.3s; box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3); }
    .btn-post:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(25, 135, 84, 0.4); }
    
    .preview-link { color: #6f42c1; text-decoration: none; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: 0.3s; }
    .preview-link:hover { text-decoration: underline; color: #10603b; }

    .table-section { padding: 30px 40px; }
    .section-subtitle { font-weight: 800; color: #888; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
    
    .ann-table { border: none; }
    .ann-table th { background: #f8f9fa; color: #888; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; border: none; }
    .ann-table td { vertical-align: middle; padding: 18px 15px; border-bottom: 1px solid #f1f3f5; transition: 0.2s; }
    .ann-table tr:hover td { background: rgba(16, 96, 59, 0.02); }

    .status-badge { border-radius: 50px; padding: 4px 12px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .bg-active { background: #d1e7dd; color: #198754; }
    .bg-expired { background: #f8d7da; color: #dc3545; }
</style>

<div class="ann-dashboard">
    <div class="ann-header">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="header-title"><i class="bi bi-bell-fill me-2"></i>Manage Announcements</h2>
        <a href="<?= BASE_URL ?>" class="btn-home shadow-sm">Home</a>
    </div>

    <div class="form-container">
        <?php if(isset($_GET['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['success_msg']) ?></div><?php endif; ?>

        <form method="post" id="annForm">
            <input type="hidden" name="ann_id" id="ann_id" value="0">
            <div class="row g-4 align-items-start">
                <div class="col-md-5">
                    <label class="label-min">Announcement Title</label>
                    <input type="text" name="title" id="ann_title" class="underline-input" placeholder="Enter announcement title..." required>
                </div>
                <div class="col-md-4">
                    <label class="label-min">Expiry Date (Optional)</label>
                    <input type="date" name="expiry_date" id="ann_date" class="underline-input">
                </div>
                <div class="col-md-3 text-end pt-3">
                    <span class="preview-link" data-bs-toggle="modal" data-bs-target="#previewModal" onclick="updatePreview()">
                        <i class="bi bi-eye me-1"></i> Preview Layout
                    </span>
                </div>
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="label-min mb-0">Description / Content</label>
                        <button type="button" class="btn btn-xs btn-outline-info rounded-pill px-2 fw-bold" style="font-size: 0.65rem;" data-bs-toggle="modal" data-bs-target="#globalAIModal">
                            <i class="bi bi-stars"></i> USE AI WRITER
                        </button>
                    </div>
                    <textarea name="content" id="ann_content" class="underline-input" rows="3" placeholder="Write the details here..." required></textarea>
                </div>
                <div class="col-md-12 text-center mt-4">
                    <button type="submit" name="save_announcement" id="submitBtn" class="btn-post">POST ANNOUNCEMENT</button>
                    <button type="button" id="cancelBtn" class="btn btn-link text-muted" style="display:none;" onclick="location.reload()">Cancel Edit</button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-section">
        <h6 class="section-subtitle">Recent Announcements</h6>
        <div class="table-responsive">
            <table class="table ann-table mb-0" id="annTable">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Title</th>
                        <th>Created Date</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($announcements as $a): 
                        // Safer check for expiry_date
                        $exp_val = $a['expiry_date'] ?? null;
                        $is_expired = ($exp_val && strtotime($exp_val) < strtotime(date('Y-m-d')));
                    ?>
                    <tr>
                        <td class="text-muted fw-bold">#<?= str_pad($a['id'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="text-dark fw-bold"><?= htmlspecialchars($a['title']) ?></div>
                            <small class="text-muted"><?= substr(htmlspecialchars($a['content']), 0, 50) ?>...</small>
                        </td>
                        <td class="small"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                        <td>
                            <?php if($is_expired): ?>
                                <span class="status-badge bg-expired">Expired</span>
                            <?php else: ?>
                                <span class="status-badge bg-active">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm text-primary border-0" onclick="editAnn('<?= $a['id'] ?>', '<?= addslashes($a['title']) ?>', '<?= addslashes($a['content']) ?>', '<?= $a['expiry_date'] ?? '' ?>')"><i class="bi bi-pencil-square"></i></button>
                            <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Are you sure you want to delete this?')"><i class="bi bi-trash3-fill"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($announcements)): ?>
                        <tr><td colspan="5" class="text-center p-5 text-muted">No announcements found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-display me-2 text-primary"></i>Live Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-4 rounded-4" style="background: #f8f9fa; border-left: 5px solid #10603b;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h4 id="pre_title" class="fw-bold text-dark mb-0">Title Placeholder</h4>
                        <span class="badge bg-active status-badge">Active</span>
                    </div>
                    <small class="text-muted d-block mb-3"><i class="bi bi-calendar-event me-1"></i> Posted on: <?= date('d M, Y') ?></small>
                    <p id="pre_content" class="text-secondary" style="line-height: 1.6;">Your announcement content will appear here...</p>
                    <div id="pre_expiry" class="mt-3 small fw-bold text-danger"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function updatePreview() {
        const title = document.getElementById('ann_title').value || 'Title Placeholder';
        const content = document.getElementById('ann_content').value || 'Your announcement content will appear here...';
        const expiry = document.getElementById('ann_date').value;

        document.getElementById('pre_title').innerText = title;
        document.getElementById('pre_content').innerText = content;
        
        const expiryDiv = document.getElementById('pre_expiry');
        if(expiry) {
            expiryDiv.innerHTML = `<i class="bi bi-clock-history me-1"></i> Expires on: ${expiry}`;
        } else {
            expiryDiv.innerText = '';
        }
    }

    function editAnn(id, title, content, expiry) {
        document.getElementById('ann_id').value = id;
        document.getElementById('ann_title').value = title;
        document.getElementById('ann_content').value = content;
        document.getElementById('ann_date').value = expiry;
        
        document.getElementById('submitBtn').innerText = "UPDATE ANNOUNCEMENT";
        document.getElementById('cancelBtn').style.display = 'inline-block';
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.style.display = 'none', 500);
        });
    }, 5000);
</script>

<?php require_once '../../includes/footer.php'; ?>