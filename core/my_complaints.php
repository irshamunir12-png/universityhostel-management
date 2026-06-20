<?php
require_once '../includes/header.php';
require_once '../core/functions.php';

// Security Check
if ($_SESSION['role'] !== 'student') {
    echo "<script>window.location.href='../index.php';</script>";
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch Complaint Categories
$categories = $pdo->query("SELECT * FROM complaint_categories WHERE is_deleted = 0 ORDER BY category_name")->fetchAll();

// Handle New Complaint Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $cat_id = (int)$_POST['category_id'];

    if ($title && $desc && $cat_id) {
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, category_id, title, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $cat_id, $title, $desc]);
        $success = "Your complaint has been submitted successfully.";
    } else {
        $error = "Please fill all the fields.";
    }
}

// Fetch My Complaints
$stmt = $pdo->prepare("
    SELECT c.*, cat.category_name 
    FROM complaints c
    LEFT JOIN complaint_categories cat ON c.category_id = cat.id
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$my_complaints = $stmt->fetchAll();
?>

<div class="row">
    <!-- Left Column: New Complaint Form -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Lodge a New Complaint</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject / Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Room light not working" required>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Description</label>
                            <button type="button" class="btn btn-xs btn-outline-info rounded-pill px-2 fw-bold" style="font-size: 0.7rem;" data-bs-toggle="modal" data-bs-target="#globalAIModal">
                                <i class="bi bi-stars"></i> AI HELP
                            </button>
                        </div>
                        <textarea name="description" class="form-control" rows="4" placeholder="Please provide details about the issue." required></textarea>
                    </div>
                    <button type="submit" name="submit_complaint" class="btn btn-primary w-100">Submit Complaint</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: My Complaints List -->
    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header"><h3 class="card-title">My Complaint History</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead><tr><th>Subject</th><th>Category</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if(empty($my_complaints)): ?>
                            <tr><td colspan="4" class="text-center text-muted p-4">You have not submitted any complaints yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($my_complaints as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['title']) ?></strong><p class="small text-muted mb-0"><?= htmlspecialchars($c['description']) ?></p></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($c['category_name'] ?? 'N/A') ?></span></td>
                                <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                <td><?php $badges = ['pending'=>'bg-warning text-dark', 'in_progress'=>'bg-info', 'resolved'=>'bg-success', 'rejected'=>'bg-danger']; $bg = $badges[$c['status']] ?? 'bg-secondary'; ?><span class="badge <?= $bg ?>"><?= ucfirst(str_replace('_', ' ', $c['status'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>