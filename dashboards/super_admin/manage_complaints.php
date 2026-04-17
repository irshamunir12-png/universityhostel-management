<?php
require_once '../../core/session.php'; 
require_once '../../core/functions.php';

// 2. Auto-Create Table (Agar table nahi bana to ye khud bana dega)
$pdo->exec("CREATE TABLE IF NOT EXISTS complaints (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('pending', 'in_progress', 'resolved', 'rejected') DEFAULT 'pending',
  sla_breached TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// --- DATABASE REPAIR ---
try { $pdo->query("SELECT priority FROM complaints LIMIT 1"); } 
catch (Exception $e) { $pdo->exec("ALTER TABLE complaints ADD COLUMN priority ENUM('Low', 'Medium', 'High') DEFAULT 'Low' AFTER description"); }

try { $pdo->query("SELECT category_id FROM complaints LIMIT 1"); } 
catch (Exception $e) { $pdo->exec("ALTER TABLE complaints ADD COLUMN category_id INT DEFAULT NULL AFTER user_id;"); }

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_complaint'])) {
    $id = (int)$_POST['complaint_id'];
    $user_id = (int)$_POST['student_id'];
    $cat_id = (int)$_POST['category_id'];
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description'] ?? 'No description provided');
    $priority = sanitize($_POST['priority']);
    $status = sanitize($_POST['status']);

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE complaints SET user_id = ?, category_id = ?, title = ?, description = ?, priority = ?, status = ? WHERE id = ?");
            $stmt->execute([$user_id, $cat_id, $title, $desc, $priority, $status, $id]);
            $msg = "Complaint updated successfully.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO complaints (user_id, category_id, title, description, priority, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $cat_id, $title, $desc, $priority, $status]);
            $msg = "Complaint submitted successfully.";
        }
        header("Location: manage_complaints.php?success_msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM complaints WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: manage_complaints.php?success_msg=Complaint deleted");
    exit;
}

// Fetch Data
$students = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' AND is_deleted = 0")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT id, category_name FROM complaint_categories ORDER BY category_name")->fetchAll();
$complaints = $pdo->query("SELECT c.*, u.name, u.registration_no, r.room_no, cat.category_name 
    FROM complaints c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1)
    LEFT JOIN rooms r ON ra.room_id = r.id
    LEFT JOIN complaint_categories cat ON c.category_id = cat.id
    ORDER BY c.id DESC")->fetchAll();

$next_id = $pdo->query("SELECT MAX(id) FROM complaints")->fetchColumn() + 1;

require_once '../../includes/header.php';
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .complaint-dashboard { background: #fcfcfc; border-radius: 25px; overflow: hidden; box-shadow: 0 15px 50px rgba(0,0,0,0.08); margin-top: 10px; border: none; }
    .complaint-header { background: linear-gradient(to right, #2ecc71, #1abc9c);
 !important; padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; color: white; }
    
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }

    .header-title { font-weight: 900; font-size: 1.8rem; text-transform: uppercase; margin: 0; letter-spacing: 1px; color: #fff; }
    .btn-home { background: white; color: #198754 !important; font-weight: bold; border-radius: 12px; padding: 8px 25px; text-decoration: none; transition: 0.3s; }
    
    .form-container { padding: 40px; background: #fff; position: relative; border-bottom: 1px solid #f1f1f1; }
    .complaint-id-badge { position: absolute; top: 20px; right: 30px; background: #f1f3f5; color: #666; padding: 5px 15px; border-radius: 8px; font-weight: 800; font-size: 0.8rem; }

    .underline-input { border: none; border-bottom: 2px solid #eaedf0; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; color: #333; transition: 0.3s; }
    .underline-input:focus { box-shadow: none; outline: none; border-bottom-color: #198754; }
    
    .btn-submit { background: #198754; color: white; border: none; border-radius: 50px; padding: 15px 45px; font-weight: 700; transition: 0.3s; box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3); }
    .btn-submit:hover { transform: translateY(-2px); opacity: 0.95; }

    .table-section { padding: 30px 40px 40px; background: #fdfdfd; }
    .search-bar { background: white; border: 1px solid #eee; border-radius: 50px; padding: 12px 25px; width: 100%; max-width: 500px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
    
    .complaint-table { border: none; }
    .complaint-table th { background: #f8f9fa; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; border: none; }
    .complaint-table td { vertical-align: middle; padding: 18px 15px; border-bottom: 1px solid #f1f3f5; }
    .complaint-table tr:hover { background: rgba(32, 201, 151, 0.03); }

    .label-min { font-size: 0.75rem; font-weight: 800; color: #333; text-transform: uppercase; display: block; margin-bottom: -5px; }
    
    .priority-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    .bg-priority-high { background-color: #dc3545; box-shadow: 0 0 8px rgba(220, 53, 69, 0.4); }
    .bg-priority-medium { background-color: #ffc107; }
    .bg-priority-low { background-color: #198754; }
    
    .status-badge { border-radius: 50px; padding: 5px 15px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .status-pending { background: #e9ecef; color: #6c757d; }
    .status-in-progress { background: #e7f1ff; color: #0d6efd; }
    .status-resolved { background: #d1e7dd; color: #198754; }
</style>

<div class="complaint-dashboard">
    <div class="complaint-header">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="header-title">Manage Complaints</h2>
        <a href="<?= BASE_URL ?>" class="btn-home shadow-sm">Home</a>
    </div>

    <div class="form-container">
        <?php if(isset($_GET['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['success_msg']) ?></div><?php endif; ?>

        <!-- Manual Entry Toggle Button -->
        <div class="mb-3" id="addBtnContainer">
            <button class="btn btn-sm btn-outline-success rounded-pill fw-bold px-4" onclick="toggleComplaintForm()"><i class="bi bi-plus-circle me-1"></i> LOG MANUAL COMPLAINT</button>
        </div>

        <form method="post" id="complaintForm" class="row g-4 align-items-end" style="display: none;">
            <input type="hidden" name="complaint_id" id="edit_complaint_id" value="0">
            
            <div class="col-md-2">
                <label class="label-min">Student ID</label>
                <select name="student_id" id="student_select" class="form-select underline-input" required>
                    <option value="">Select Student</option>
                    <?php foreach($students as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['registration_no'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="label-min">Student Name</label>
                <input type="text" id="student_name" class="form-control underline-input" placeholder="Auto-filled" readonly>
            </div>
            <div class="col-md-2">
                <label class="label-min">Complaint Type</label>
                <select name="category_id" class="form-select underline-input" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="label-min">Complaint Title</label>
                <input type="text" name="title" class="form-control underline-input" placeholder="Short subject" required>
            </div>
            <div class="col-md-2">
                <label class="label-min">Priority</label>
                <select name="priority" class="form-select underline-input">
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High 🔥</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="label-min">Status</label>
                <select name="status" class="form-select underline-input">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button type="submit" name="save_complaint" id="submitBtn" class="btn-submit py-2 px-4">SAVE</button>
                <button type="button" class="btn btn-sm btn-link text-muted" onclick="location.reload()">Cancel</button>
            </div>
        </form>
    </div>

    <div class="table-section">
        <input type="text" id="complaintSearch" class="search-bar" placeholder="Search complaints by student, room or type..." onkeyup="filterTable()">
        
        <div class="table-responsive">
            <table class="table complaint-table mb-0" id="complaintTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($complaints as $c): 
                        $p_cls = 'bg-priority-' . strtolower($c['priority']);
                        $s_cls = 'status-' . str_replace('_', '-', $c['status']);
                    ?>
                    <tr>
                        <td class="small"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <div class="text-dark"><?= htmlspecialchars($c['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($c['registration_no']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($c['category_name'] ?? 'Other') ?></td>
                        <td>
                            <span class="priority-dot <?= $p_cls ?>"></span>
                            <span class="small"><?= $c['priority'] ?></span>
                        </td>
                        <td><span class="status-badge <?= $s_cls ?>"><?= strtoupper(str_replace('_', ' ', $c['status'])) ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm text-primary border-0" onclick="editComplaint('<?= $c['id'] ?>', '<?= $c['user_id'] ?>', '<?= $c['category_id'] ?>', '<?= addslashes($c['title']) ?>', '<?= $c['priority'] ?>', '<?= $c['status'] ?>')"><i class="bi bi-pencil-square"></i></button>
                            <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Delete complaint?')"><i class="bi bi-trash3-fill"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const studentData = <?= json_encode($students) ?>;

    // Auto-fill student name
    document.getElementById('student_select').addEventListener('change', function() {
        const student = studentData.find(s => s.id == this.value);
        document.getElementById('student_name').value = student ? student.name : '';
    });

    function toggleComplaintForm() {
        const form = document.getElementById('complaintForm');
        const btn = document.getElementById('addBtnContainer');
        form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    }

    function editComplaint(id, studentId, catId, title, priority, status) {
        document.getElementById('edit_complaint_id').value = id;
        document.getElementById('complaintForm').style.display = 'flex';
        document.getElementById('addBtnContainer').style.display = 'none';

        const studentSelect = document.getElementById('student_select');
        studentSelect.value = studentId;
        studentSelect.dispatchEvent(new Event('change'));

        document.getElementsByName('category_id')[0].value = catId;
        document.getElementsByName('title')[0].value = title;
        document.getElementsByName('priority')[0].value = priority;
        document.getElementsByName('status')[0].value = status;

        document.getElementById('submitBtn').innerText = "UPDATE";
        document.getElementById('cancelBtn').style.display = 'inline-block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function filterTable() {
        let input = document.getElementById('complaintSearch').value.toUpperCase();
        let rows = document.querySelectorAll('#complaintTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toUpperCase().includes(input) ? "" : "none";
        });
    }

    setTimeout(() => { document.querySelectorAll('.auto-hide').forEach(el => el.style.display = 'none'); }, 5000);
</script>

<?php require_once '../../includes/footer.php'; ?>