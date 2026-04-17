<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// 1. Table Creation (Agar maujood na ho)
$pdo->exec("CREATE TABLE IF NOT EXISTS student_leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'in_progress') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 2. Handle Status Actions
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['status']);
    $allowed = ['pending', 'approved', 'rejected', 'in_progress'];
    
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE student_leaves SET status = ? WHERE id = ?")->execute([$status, $id]);
        header("Location: manage_leaves.php?msg=updated");
        exit;
    }
}

// 3. Fetch Data
$leaves = $pdo->query("
    SELECT sl.*, u.name as student_name, u.registration_no 
    FROM student_leaves sl 
    JOIN users u ON sl.user_id = u.id 
    ORDER BY sl.created_at DESC
")->fetchAll();
?>

<style>
    /* Overriding default dashboard styles for a cleaner look */
    .app-main { background-color: #f8f9fa !important; padding-top: 0 !important; }
    
    .breadcrumb-nav { padding: 20px 40px 10px; font-size: 0.85rem; color: #888; }
    .breadcrumb-nav a { color: #6f42c1; text-decoration: none; font-weight: 600; }
    
    .page-header-title { padding: 0 40px 20px; font-weight: 800; color: #2d3436; font-size: 1.8rem; }

    .leave-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        margin: 0 40px 40px;
        overflow: hidden;
        border: none;
    }
    
    .leave-card-header {
        padding: 25px 30px;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .leave-card-title { font-weight: 700; color: #2d3436; margin: 0; font-size: 1.2rem; }

    .table-container { padding: 0 20px 20px; }
    .leave-table { border: none; width: 100%; }
    .leave-table th { 
        background: #fdfdfd; 
        color: #a0a0a0; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
        padding: 15px 20px; 
        border: none;
    }
    .leave-table td { 
        vertical-align: middle; 
        padding: 20px; 
        border-bottom: 1px solid #f8f9fa; 
        color: #444;
    }
    .leave-table tr:hover td { background-color: #fafafa; }

    /* Status Badges */
    .st-badge {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
    }
    .bg-pending { background-color: #fff9db; color: #f59f00; } /* Yellow */
    .bg-approved { background-color: #ebfbee; color: #40c057; } /* Green */
    .bg-rejected { background-color: #fff5f5; color: #fa5252; } /* Red */
    .bg-in-progress { background-color: #e7f5ff; color: #228be6; } /* Blue */

    /* Action Buttons */
    .action-group { display: flex; gap: 8px; justify-content: center; }
    .btn-circle {
        width: 32px; height: 32px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        border: none; transition: 0.3s;
        text-decoration: none;
    }
    .btn-appr { background: #40c057; color: white; }
    .btn-rejt { background: #fa5252; color: white; }
    .btn-prog { background: #228be6; color: white; }
    .btn-circle:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: white; }

    .std-name { font-weight: 700; color: #2d3436; display: block; }
    .std-reg { font-size: 0.75rem; color: #adb5bd; }
</style>

<!-- Breadcrumb & Title -->
<div class="breadcrumb-nav">
    <a href="<?= BASE_URL ?>">Home</a> / Helpdesk & Support / <span class="text-dark">Manage Leaves</span>
</div>
<h2 class="page-header-title">Manage Leaves</h2>

<div class="leave-card">
    <div class="leave-card-header">
        <h5 class="leave-card-title">Leave Applications</h5>
        <div class="window-controls">
            <span class="win-dot dot-r" style="width:10px; height:10px; border-radius:50%; background:#ff5f56; display:inline-block; margin-right:5px;"></span>
            <span class="win-dot dot-y" style="width:10px; height:10px; border-radius:50%; background:#ffbd2e; display:inline-block; margin-right:5px;"></span>
            <span class="win-dot dot-g" style="width:10px; height:10px; border-radius:50%; background:#009a17; display:inline-block;"></span>
        </div>
    </div>

    <div class="table-section">
        <div class="table-responsive">
            <table class="table leave-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Student Name</th>
                        <th>Leave Details</th>
                        <th>Dates (From – To)</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($leaves as $l): 
                        $st = str_replace('_', '-', $l['status']);
                    ?>
                    <tr>
                        <td class="ps-4">
                            <span class="std-name"><?= htmlspecialchars($l['student_name']) ?></span>
                            <span class="std-reg"><?= htmlspecialchars($l['registration_no']) ?></span>
                        </td>
                        <td><div class="small text-muted text-truncate" style="max-width: 200px;"><?= htmlspecialchars($l['reason']) ?></div></td>
                        <td><span class="fw-bold small"><?= date('d M', strtotime($l['start_date'])) ?> – <?= date('d M, Y', strtotime($l['end_date'])) ?></span></td>
                        <td><span class="st-badge bg-<?= $st ?>"><?= str_replace('-', ' ', $l['status']) ?></span></td>
                        <td class="text-center">
                            <div class="action-group">
                                <a href="?id=<?= $l['id'] ?>&status=approved" class="btn-circle btn-appr" title="Approve"><i class="bi bi-check-lg"></i></a>
                                <a href="?id=<?= $l['id'] ?>&status=rejected" class="btn-circle btn-rejt" title="Reject"><i class="bi bi-x-lg"></i></a>
                                <a href="?id=<?= $l['id'] ?>&status=in_progress" class="btn-circle btn-prog" title="In Progress"><i class="bi bi-arrow-repeat"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($leaves)): ?>
                        <tr><td colspan="5" class="text-center p-5 text-muted">No leave applications available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>