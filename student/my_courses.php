<?php
require_once '../includes/header.php';
require_once '../core/session.php';

// 1. Login Check
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../login.php');
    exit;
}

// 2. Auto-Create Table for Enrollments
$pdo->exec("CREATE TABLE IF NOT EXISTS student_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE(user_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 3. Handle Enroll / Drop Actions
if (isset($_GET['action']) && isset($_GET['cid'])) {
    $cid = (int)$_GET['cid'];
    
    if ($_GET['action'] === 'enroll') {
        try {
            $stmt = $pdo->prepare("INSERT INTO student_courses (user_id, course_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $cid]);
            $success = "Successfully enrolled in the course!";
        } catch (Exception $e) {
            $error = "You are already enrolled or course does not exist.";
        }
    } elseif ($_GET['action'] === 'drop') {
        $stmt = $pdo->prepare("DELETE FROM student_courses WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $cid]);
        $success = "Course dropped successfully.";
    }
}

// 4. Fetch All Courses with Enrollment Status
$sql = "SELECT c.*, sc.enrolled_at 
        FROM courses c 
        LEFT JOIN student_courses sc ON c.id = sc.course_id AND sc.user_id = ?
        ORDER BY c.course_code ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();
?>

<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">Course Registration</h3>
    </div>
    <div class="card-body">
        <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <?php if(!$courses): ?>
            <div class="alert alert-info">No courses available for registration yet.</div>
        <?php else: ?>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($courses as $c): 
                    $isEnrolled = !empty($c['enrolled_at']);
                ?>
                    <tr class="<?= $isEnrolled ? 'table-success' : '' ?>">
                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($c['course_code']) ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($c['course_name']) ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars($c['description']) ?></small>
                        </td>
                        <td><?= $c['credit_hours'] ?></td>
                        <td>
                            <?php if($isEnrolled): ?>
                                <span class="badge text-bg-success"><i class="bi bi-check-circle"></i> Enrolled</span>
                                <br><small><?= date('d M Y', strtotime($c['enrolled_at'])) ?></small>
                            <?php else: ?>
                                <span class="badge text-bg-warning">Not Registered</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($isEnrolled): ?>
                                <a href="?action=drop&cid=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to drop this course?')">
                                    <i class="bi bi-x-lg"></i> Drop
                                </a>
                            <?php else: ?>
                                <a href="?action=enroll&cid=<?= $c['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg"></i> Enroll
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>