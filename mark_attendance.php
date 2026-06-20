<<<<<<< HEAD
<?php
require_once '../../includes/header.php';

// Get Date (Default: Today)
$date = $_GET['date'] ?? date('Y-m-d');

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $attendance = $_POST['attendance'] ?? [];
    
    $pdo->beginTransaction();
    try {
        // Pehle us date ka purana data delete karein (taake update ho sake)
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE date = ?");
        $stmt->execute([$date]);

        // Naya data insert karein
        $insert = $pdo->prepare("INSERT INTO attendance (user_id, date, status) VALUES (?, ?, ?)");
        foreach ($attendance as $userId => $status) {
            $insert->execute([$userId, $date, $status]);
        }
        $pdo->commit();
        $success = "Attendance for " . date('d M Y', strtotime($date)) . " saved successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// 1. Fetch All Active Students
$students = $pdo->query("
    SELECT u.id, u.name, u.registration_no, r.room_no 
    FROM users u 
    LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1) 
    LEFT JOIN rooms r ON ra.room_id = r.id 
    WHERE u.role = 'student' AND u.is_active = 1 
    ORDER BY r.room_no DESC, u.name ASC
")->fetchAll();

// 2. Fetch Existing Attendance for Selected Date
$existing = $pdo->prepare("SELECT user_id, status FROM attendance WHERE date = ?");
$existing->execute([$date]);
$attendanceMap = $existing->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Fetch Approved Leaves (Smart Feature)
$leavesMap = [];
try {
    $leaves = $pdo->prepare("SELECT user_id FROM student_leaves WHERE status = 'approved' AND ? BETWEEN start_date AND end_date");
    $leaves->execute([$date]);
    $leavesMap = $leaves->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Mark Attendance</h3>
        <div class="card-tools">
            <form method="get" class="d-flex align-items-center">
                <label class="me-2">Date:</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= $date ?>" onchange="this.form.submit()">
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger m-3"><?= $error ?></div><?php endif; ?>

        <form method="post">
            <input type="hidden" name="date" value="<?= $date ?>">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Student Details</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): 
                        // Logic: Agar pehle se attendance lagi hai to wo uthao, nahi to 'Present' default rakho.
                        // Agar Leave approved hai, to 'Leave' select karo.
                        $status = $attendanceMap[$s['id']] ?? 'Present';
                        $isOnLeave = in_array($s['id'], $leavesMap);
                        if ($isOnLeave && !isset($attendanceMap[$s['id']])) {
                            $status = 'Leave';
                        }
                    ?>
                    <tr>
                        <td>
                            <span class="fw-bold"><?= htmlspecialchars($s['name']) ?></span>
                            <br><small class="text-muted"><?= htmlspecialchars($s['registration_no']) ?></small>
                            <?php if($s['room_no']): ?>
                                <span class="badge bg-info float-end"><?= $s['room_no'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning float-end">No Room</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Present" id="p_<?= $s['id'] ?>" <?= $status=='Present'?'checked':'' ?>>
                                <label class="btn btn-outline-success btn-sm" for="p_<?= $s['id'] ?>">Present</label>

                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Absent" id="a_<?= $s['id'] ?>" <?= $status=='Absent'?'checked':'' ?>>
                                <label class="btn btn-outline-danger btn-sm" for="a_<?= $s['id'] ?>">Absent</label>

                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Leave" id="l_<?= $s['id'] ?>" <?= $status=='Leave'?'checked':'' ?>>
                                <label class="btn btn-outline-warning btn-sm" for="l_<?= $s['id'] ?>">Leave</label>
                            </div>
                            <?php if($isOnLeave): ?><br><small class="text-danger fw-bold">On Leave</small><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Save Attendance</button>
            </div>
        </form>
    </div>
</div>

=======
<?php
require_once '../../includes/header.php';

// Get Date (Default: Today)
$date = $_GET['date'] ?? date('Y-m-d');

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $attendance = $_POST['attendance'] ?? [];
    
    $pdo->beginTransaction();
    try {
        // Pehle us date ka purana data delete karein (taake update ho sake)
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE date = ?");
        $stmt->execute([$date]);

        // Naya data insert karein
        $insert = $pdo->prepare("INSERT INTO attendance (user_id, date, status) VALUES (?, ?, ?)");
        foreach ($attendance as $userId => $status) {
            $insert->execute([$userId, $date, $status]);
        }
        $pdo->commit();
        $success = "Attendance for " . date('d M Y', strtotime($date)) . " saved successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// 1. Fetch All Active Students
$students = $pdo->query("
    SELECT u.id, u.name, u.registration_no, r.room_no 
    FROM users u 
    LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1) 
    LEFT JOIN rooms r ON ra.room_id = r.id 
    WHERE u.role = 'student' AND u.is_active = 1 
    ORDER BY r.room_no DESC, u.name ASC
")->fetchAll();

// 2. Fetch Existing Attendance for Selected Date
$existing = $pdo->prepare("SELECT user_id, status FROM attendance WHERE date = ?");
$existing->execute([$date]);
$attendanceMap = $existing->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Fetch Approved Leaves (Smart Feature)
$leavesMap = [];
try {
    $leaves = $pdo->prepare("SELECT user_id FROM student_leaves WHERE status = 'approved' AND ? BETWEEN start_date AND end_date");
    $leaves->execute([$date]);
    $leavesMap = $leaves->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Mark Attendance</h3>
        <div class="card-tools">
            <form method="get" class="d-flex align-items-center">
                <label class="me-2">Date:</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= $date ?>" onchange="this.form.submit()">
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger m-3"><?= $error ?></div><?php endif; ?>

        <form method="post">
            <input type="hidden" name="date" value="<?= $date ?>">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Student Details</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): 
                        // Logic: Agar pehle se attendance lagi hai to wo uthao, nahi to 'Present' default rakho.
                        // Agar Leave approved hai, to 'Leave' select karo.
                        $status = $attendanceMap[$s['id']] ?? 'Present';
                        $isOnLeave = in_array($s['id'], $leavesMap);
                        if ($isOnLeave && !isset($attendanceMap[$s['id']])) {
                            $status = 'Leave';
                        }
                    ?>
                    <tr>
                        <td>
                            <span class="fw-bold"><?= htmlspecialchars($s['name']) ?></span>
                            <br><small class="text-muted"><?= htmlspecialchars($s['registration_no']) ?></small>
                            <?php if($s['room_no']): ?>
                                <span class="badge bg-info float-end"><?= $s['room_no'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning float-end">No Room</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Present" id="p_<?= $s['id'] ?>" <?= $status=='Present'?'checked':'' ?>>
                                <label class="btn btn-outline-success btn-sm" for="p_<?= $s['id'] ?>">Present</label>

                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Absent" id="a_<?= $s['id'] ?>" <?= $status=='Absent'?'checked':'' ?>>
                                <label class="btn btn-outline-danger btn-sm" for="a_<?= $s['id'] ?>">Absent</label>

                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Leave" id="l_<?= $s['id'] ?>" <?= $status=='Leave'?'checked':'' ?>>
                                <label class="btn btn-outline-warning btn-sm" for="l_<?= $s['id'] ?>">Leave</label>
                            </div>
                            <?php if($isOnLeave): ?><br><small class="text-danger fw-bold">On Leave</small><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Save Attendance</button>
            </div>
        </form>
    </div>
</div>

>>>>>>> 565f129c970092d2d1b44ff2d6bbfebc50c7c53d
<?php require_once '../../includes/footer.php'; ?>