<?php
require_once '../includes/header.php';
require_once '../core/functions.php';

$user_id = $_SESSION['user_id'];

// Handle New Complaint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $title = sanitize($_POST['title']);
    $cat_id = (int)$_POST['category_id'];
    $desc = sanitize($_POST['description']);
    
    if ($title && $desc) {
        // Table auto-create check (Just in case)
        $pdo->exec("CREATE TABLE IF NOT EXISTS complaints (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('pending', 'in_progress', 'resolved', 'rejected') DEFAULT 'pending',
            category_id INT,
            sla_breached TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, title, category_id, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $cat_id, $desc]);
        $success = "Complaint submitted successfully.";
    } else {
        $error = "Please fill in all fields.";
    }
}

// Fetch My Complaints
$complaints = $pdo->prepare("SELECT c.*, cat.category_name FROM complaints c LEFT JOIN complaint_categories cat ON c.category_id = cat.id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$complaints->execute([$user_id]);
$myComplaints = $complaints->fetchAll();

// Fetch Categories for Dropdown
$cats = $pdo->query("SELECT * FROM complaint_categories")->fetchAll();
?>

<div class="row">
    <!-- Form Column -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">New Complaint</h3></div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label>Subject</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Fan not working" required>
                    </div>
                    <div class="mb-3">
                        <label>Category (Service Type)</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach($cats as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?> (Max <?= $cat['sla_hours'] ?>h)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Details</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the issue..." required></textarea>
                    </div>
                    <button type="submit" name="submit_complaint" class="btn btn-primary w-100">Submit Ticket</button>
                </form>
            </div>
        </div>
    </div>

    <!-- List Column -->
    <div class="col-md-8">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title">My History</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($myComplaints as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($c['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($c['description']) ?></small>
                            </td>
                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <?php 
                                    $badges = [
                                        'pending' => 'bg-warning', 
                                        'in_progress' => 'bg-info', 
                                        'resolved' => 'bg-success', 
                                        'rejected' => 'bg-danger'
                                    ];
                                    $bg = $badges[$c['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $bg ?>"><?= ucfirst(str_replace('_', ' ', $c['status'])) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(!$myComplaints): ?>
                            <tr><td colspan="3" class="text-center p-3">No complaints found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>