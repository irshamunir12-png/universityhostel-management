<?php
require_once '../../core/session.php'; 
require_once '../../core/functions.php';

// --- DATABASE REPAIR: Ensure courses table exists and has all columns ---
$pdo->exec("CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL UNIQUE,
  `course_name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `category_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure old tables get new columns (incase table existed but was outdated)
try { $pdo->query("SELECT instructor FROM courses LIMIT 1"); } catch (Exception $e) {
    $pdo->exec("ALTER TABLE courses ADD COLUMN instructor VARCHAR(100) DEFAULT NULL, ADD COLUMN duration VARCHAR(50) DEFAULT NULL, ADD COLUMN fee DECIMAL(10,2) DEFAULT 0, ADD COLUMN category_id INT DEFAULT NULL");
}

// --- DATABASE REPAIR: Ensure departments table exists for the dropdown ---
$pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL UNIQUE,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Seed departments if empty so the UI doesn't look broken
$pdo->exec("INSERT IGNORE INTO departments (department_name) VALUES 
('Computer Science'), ('Business Administration'), ('Electrical Engineering'), ('Mechanical Engineering')");

// Auto-Fix: Ensure 'created_at' exists in users table for statistics
try {
    $pdo->query("SELECT created_at FROM users LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

// Handle Add/Update Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $id = (int)$_POST['course_id'];
    $code = sanitize($_POST['course_code']);
    $name = sanitize($_POST['course_name']);
    $instructor = sanitize($_POST['instructor']);
    $duration = sanitize($_POST['duration']);
    $category_id = (int)($_POST['category_id'] ?? 0);

    try {
        // Check for duplicate course code (excluding the current course if editing)
        $check = $pdo->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ? AND is_deleted = 0");
        $check->execute([$code, $id]);
        if ($check->fetch()) {
            throw new Exception("The Course ID '$code' is already assigned to another course.");
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ?, instructor = ?, duration = ?, category_id = ? WHERE id = ?");
            $stmt->execute([$code, $name, $instructor, $duration, $category_id ?: null, $id]);
            $msg = "Course updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, instructor, duration, category_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $instructor, $duration, $category_id ?: null]);
            $msg = "New course registered successfully!";
        }
        header("Location: manage_courses.php?success_msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete Course
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("UPDATE courses SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$id]);
    header("Location: manage_courses.php?success_msg=Course deleted");
    exit;
}

// Fetch Data
$departments = $pdo->query("SELECT * FROM departments WHERE is_deleted = 0 ORDER BY department_name")->fetchAll();
$courses = $pdo->query("SELECT c.*, d.department_name as category_name FROM courses c LEFT JOIN departments d ON c.category_id = d.id WHERE c.is_deleted = 0 ORDER BY c.course_code ASC")->fetchAll();

// Fetch Real-time Enrollment Data (Students registered per month)
$enrollmentStats = array_fill(0, 12, 0);
$trendQuery = $pdo->query("SELECT MONTH(created_at) as m, COUNT(*) as count FROM users WHERE role = 'student' AND YEAR(created_at) = YEAR(CURDATE()) GROUP BY m")->fetchAll();
foreach($trendQuery as $row) {
    $enrollmentStats[$row['m'] - 1] = (int)$row['count'];
}


require_once '../../includes/header.php';
?>

<style>
    .app-content-header, .content-header, .breadcrumb { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .desktop-app-card { background: #ffffff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden; margin-top: 10px; border: none; }
    .app-header-gradient { background: linear-gradient(to right, #2ecc71, #1abc9c); padding: 15px 25px; display: flex; align-items: center; justify-content: space-between; color: white; }
    
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    
    .app-title-center { font-weight: 850; font-size: 1.8rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    .btn-app-home { background: white; color: #007bff !important; font-weight: bold; border-radius: 15px; padding: 5px 20px; text-decoration: none; transition: 0.3s; width: 120px; text-align: center; }
    .btn-app-home:hover { background: #f8f9fa; transform: scale(1.05); }

    .form-section-app { padding: 30px; background: #fff; }
    .underline-input { border: none !important; border-bottom: 2px solid #eee !important; border-radius: 0 !important; padding: 12px 5px !important; background: transparent !important; transition: 0.3s; font-weight: 600; font-size: 1.1rem; width: 100%; box-shadow: none !important; }
    .underline-input:focus { outline: none; border-bottom-color: #007bff; }

    .btn-save-green { background-color: #198754; color: white; border-radius: 25px; padding: 10px 35px; border: none; font-weight: 700; transition: 0.3s; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.2); }
    .btn-update-blue { background-color: #0d6efd; color: white; border-radius: 25px; padding: 10px 35px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-delete-pink { background-color: #ff8787; color: white; border-radius: 25px; padding: 10px 35px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-new-dark { background-color: #212529; color: white; border-radius: 25px; padding: 10px 35px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-save-green:hover, .btn-update-blue:hover, .btn-delete-pink:hover, .btn-new-dark:hover { opacity: 0.9; transform: translateY(-2px); }

    .grid-table { border: 1px solid #eee; }
    .grid-table th { background: #fdfbff; color: #555; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; }
    .grid-table td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f8f9fa; font-size: 1.05rem; }
    .grid-table tbody tr:hover { background-color: rgba(0, 123, 255, 0.03) !important; transition: 0.2s; }
    
    .stats-label { font-size: 0.85rem; color: #888; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 5px; }
    .chart-container { background: #fcfcfc; border-radius: 15px; padding: 20px; border: 1px solid #f0f0f0; }
    .auto-hide { transition: opacity 0.5s ease; }
</style>

<div class="desktop-app-card">
    <div class="app-header-gradient">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="app-title-center">Manage Courses</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="form-section-app">
        <?php if(isset($error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $error ?></div><?php endif; ?>
        <?php if(isset($_GET['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['success_msg']) ?></div><?php endif; ?>

        <div class="row mb-5">
            <!-- Form Column -->
            <div class="col-md-8">
                <form method="post" id="courseForm">
                    <input type="hidden" name="course_id" id="edit_course_id" value="0">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="stats-label">Course ID</label>
                            <input type="text" name="course_code" class="form-control underline-input" placeholder="e.g. CS-101" required>
                        </div>
                        <div class="col-md-4">
                            <label class="stats-label">Department / Category</label>
                            <select name="category_id" class="form-select underline-input">
                                <option value="0">Select Department</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="stats-label">Course Name</label>
                            <input type="text" name="course_name" class="form-control underline-input" placeholder="e.g. Advanced Web Development" required>
                        </div>
                        <div class="col-md-6">
                            <label class="stats-label">Instructor Name</label>
                            <input type="text" name="instructor" class="form-control underline-input" placeholder="e.g. Prof. Ahmed Ali">
                        </div>
                        <div class="col-md-3">
                            <label class="stats-label">Duration</label>
                            <input type="text" name="duration" class="form-control underline-input" placeholder="e.g. 6 Months">
                        </div>
                        <div class="col-12 pt-3">
                            <button type="submit" name="save_course" id="submitBtn" class="btn btn-save-green shadow-sm me-2">SAVE COURSE</button>
                            <button type="button" id="updateBtn" class="btn btn-update-blue shadow-sm me-2" style="display:none;" onclick="document.getElementById('courseForm').submit()">UPDATE</button>
                            <button type="button" class="btn btn-delete-pink shadow-sm me-2" onclick="handleDelete()">DELETE</button>
                            <button type="reset" class="btn btn-new-dark shadow-sm" onclick="resetForm()">+ NEW COURSE</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Chart Column -->
            <div class="col-md-4">
                <div class="chart-container shadow-sm h-100">
                    <label class="stats-label mb-3">Enrollment Trends</label>
                    <canvas id="enrollmentChart" style="max-height: 200px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-responsive rounded-3 shadow-sm border">
            <table class="table grid-table mb-0" id="courseTable">
                <thead class="sticky-top">
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Instructor</th>
                        <th>Duration</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($courses as $c): ?>
                    <tr onclick="editCourse('<?= $c['id'] ?>', '<?= addslashes($c['course_code']) ?>', '<?= addslashes($c['course_name']) ?>', '<?= addslashes($c['instructor']) ?>', '<?= addslashes($c['duration']) ?>', '<?= $c['category_id'] ?>')" style="cursor:pointer;">
                        <td class="fw-bold text-primary"><?= htmlspecialchars($c['course_code']) ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($c['course_name']) ?></td>
                        <td><?= htmlspecialchars($c['instructor'] ?? 'N/A') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['duration'] ?? 'N/A') ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm text-primary border-0"><i class="bi bi-pencil-square"></i></button>
                            <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Delete course?')"><i class="bi bi-trash3-fill"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Live Clock
    function updateClockApp() {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('live-clock-app').innerText = now.toLocaleDateString('en-US', options);
    }
    setInterval(updateClockApp, 1000);
    updateClockApp();

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => el.style.display = 'none');
    }, 5000);

    // Optimized Chart Logic with Gradients
    const ctx = document.getElementById('enrollmentChart');
    const chartCtx = ctx.getContext('2d');
    
    const gradient = chartCtx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(46, 204, 113, 0.4)');
    gradient.addColorStop(1, 'rgba(46, 204, 113, 0.0)');

    new Chart(chartCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'New Enrollments',
                data: <?= json_encode($enrollmentStats) ?>,
                borderColor: '#2ecc71',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#2ecc71',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, display: false }, x: { grid: { display: false } } }
        }
    });

    function editCourse(id, code, name, instr, dur, catId) {
        document.getElementById('edit_course_id').value = id;
        document.getElementsByName('course_code')[0].value = code;
        document.getElementsByName('course_name')[0].value = name;
        document.getElementsByName('instructor')[0].value = instr;
        document.getElementsByName('duration')[0].value = dur;
        document.getElementsByName('category_id')[0].value = catId || "0";
        
        document.getElementById('submitBtn').style.display = 'none';
        document.getElementById('updateBtn').style.display = 'inline-block';
    }

    function resetForm() {
        document.getElementById('edit_course_id').value = "0";
        document.getElementById('submitBtn').style.display = 'inline-block';
        document.getElementById('updateBtn').style.display = 'none';
    }

    function handleDelete() {
        const id = document.getElementById('edit_course_id').value;
        if(id !== "0") {
            if(confirm('Are you sure you want to delete this course?')) {
                window.location.href = '?delete=' + id;
            }
        } else {
            alert('Please select a course from the table first.');
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>