<?php
require_once '../../core/session.php'; // Pehle DB aur Session load karein
require_once '../../core/functions.php';

// Get Date (Default: Today)
$date = $_GET['date'] ?? date('Y-m-d');

// --- EXCEL EXPORT LOGIC (Must be at the very top before any HTML) ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    if (ob_get_length()) ob_end_clean();
    $filename = "Attendance_Report_" . $date . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Registration No', 'Room No', 'Attendance Status']);
    
    $all_students = $pdo->query("SELECT u.id, u.name, u.registration_no, r.room_no FROM users u LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1) LEFT JOIN rooms r ON ra.room_id = r.id WHERE u.role = 'student' AND u.is_deleted = 0 AND u.is_active = 1 ORDER BY u.name ASC")->fetchAll();
    $existing_att = $pdo->prepare("SELECT user_id, status FROM attendance WHERE date = ?");
    $existing_att->execute([$date]);
    $att_map = $existing_att->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($all_students as $s) {
        $status = $att_map[$s['id']] ?? 'Present';
        fputcsv($output, [
            $s['name'], 
            $s['registration_no'], 
            $s['room_no'] ?? 'Unallocated', 
            $status
        ]);
    }
    fclose($output);
    exit;
}

// --- Handle Attendance Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance = $_POST['attendance'] ?? [];
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM attendance WHERE date = ?")->execute([$date]);
        $ins = $pdo->prepare("INSERT INTO attendance (user_id, date, status) VALUES (?, ?, ?)");
        foreach ($attendance as $uid => $st) { $ins->execute([$uid, $date, $st]); }
        $pdo->commit();
        header("Location: mark_attendance.php?date=$date&success=1");
        exit;
    } catch (Exception $e) { $pdo->rollBack(); $error = $e->getMessage(); }
}

// Data Fetching for UI
$students = $pdo->query("SELECT u.id, u.name, u.registration_no, r.room_no FROM users u LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1) LEFT JOIN rooms r ON ra.room_id = r.id WHERE u.role = 'student' AND u.is_active = 1 ORDER BY u.name ASC")->fetchAll();
$existing = $pdo->prepare("SELECT user_id, status FROM attendance WHERE date = ?");
$existing->execute([$date]);
$attendanceMap = $existing->fetchAll(PDO::FETCH_KEY_PAIR);

$stats = ['Present' => 0, 'Absent' => 0, 'Leave' => 0];
foreach ($attendanceMap as $s) { if (isset($stats[$s])) $stats[$s]++; }

// Ab yahan header include karein jab sara logic khatam ho chuka ho
require_once '../../includes/header.php';
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }
    .desktop-app-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-top: 10px;
        border: none;
    }
    .app-header-teal { background: linear-gradient(to right, #2ecc71, #1abc9c);
 !important; padding: 15px 25px; display: flex; align-items: center; justify-content: space-between; color: white; }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    .app-title-center { font-weight: 850; font-size: 1.8rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    .btn-app-home { background: white; color: #20c997 !important; font-weight: bold; border-radius: 15px; padding: 5px 20px; text-decoration: none; transition: 0.3s; width: 120px; text-align: center; }
    .form-section-app { padding: 30px; background: #fff; }
    .underline-input { border: none; border-bottom: 2px solid #eee; border-radius: 0; padding: 8px 0; background: transparent; font-weight: 700; font-size: 1.2rem; text-align: center; width: 100%; } .underline-input:focus { outline: none; border-bottom-color: #20c997; }
    .btn-save-green { background-color: #198754; color: white; border-radius: 25px; padding: 12px 50px; border: none; font-weight: 700; } .btn-delete-pink { background-color: #ff8787; color: white; border-radius: 25px; padding: 12px 50px; border: none; font-weight: 700; } .btn-new-blue { background-color: #0d47a1; color: white; border-radius: 25px; padding: 12px 50px; border: none; font-weight: 700; }
    .grid-table { border: 1px solid #eee; } .grid-table th { background: #fdfbff; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; } .grid-table td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f8f9fa; } .stats-label { font-size: 0.7rem; color: #aaa; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 2px; }
</style>

<div class="desktop-app-card">
    <div class="app-header-teal"><div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div><h2 class="app-title-center">Manage Student</h2><a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a></div>

    <div class="form-section-app">
        <?php if(isset($_GET['success'])): ?><div class="alert alert-success rounded-3">Attendance Saved Successfully!</div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger rounded-3"><?= $error ?></div><?php endif; ?>

        <form method="post" id="attendanceForm">
            <input type="hidden" name="date" value="<?= $date ?>">
            
            <div class="row g-4 text-center mb-5 align-items-end">
                <div class="col-md-2">
                    <span class="stats-label">Total Students</span>
                    <input type="text" class="underline-input text-primary" value="<?= count($students) ?>" readonly>
                </div>
                <div class="col-md-2">
                    <span class="stats-label text-success">Present</span>
                    <input type="text" class="underline-input text-success" value="<?= $stats['Present'] ?>" readonly>
                </div>
                <div class="col-md-2">
                    <span class="stats-label text-danger">Absent</span>
                    <input type="text" class="underline-input text-danger" value="<?= $stats['Absent'] ?>" readonly>
                </div>
                <div class="col-md-2">
                    <span class="stats-label text-warning">On Leave</span>
                    <input type="text" class="underline-input text-warning" value="<?= $stats['Leave'] ?>" readonly>
                </div>
                <div class="col-md-2">
                    <span class="stats-label">Date</span>
                    <input type="date" class="form-control underline-input p-0" style="font-size: 0.9rem;" value="<?= $date ?>" onchange="window.location.href='?date='+this.value">
                </div>
                <div class="col-md-2">
                    <a href="attendance_history.php" class="btn btn-sm btn-outline-primary rounded-pill w-100 mb-2 fw-bold" style="border-width: 2px;"><i class="bi bi-clock-history"></i> VIEW LOGS</a>
                    <button type="button" class="btn btn-outline-success btn-sm fw-bold rounded-pill w-100" onclick="markAll('Present')">MARK ALL PRESENT</button>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-4 mb-5">
                <button type="submit" name="save_attendance" class="btn-save-green shadow-sm">ACCEPT & SAVE</button>
                <button type="button" class="btn-delete-pink shadow-sm" onclick="window.location.reload()">DELETE</button>
                <a href="manage_users.php" class="btn-new-blue shadow-sm text-decoration-none">+ NEW STUDENT</a>
                <a href="?date=<?= $date ?>&export=excel" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm">EXCEL</a>
            </div>

            <div class="table-responsive rounded-3 shadow-sm" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover grid-table mb-0" id="attendanceTable"> 
                <thead class="sticky-top">
                    <tr><th class="ps-4">Student Details</th><th class="text-center" style="width: 350px;">Attendance Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): 
                        $status = $attendanceMap[$s['id']] ?? 'Present';
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; font-weight: bold;"><?= strtoupper(substr($s['name'], 0, 1)) ?></div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['registration_no']) ?></small>
                                    <?php if($s['room_no']): ?><span class="badge bg-light text-dark border ms-1">Room <?= $s['room_no'] ?></span><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Present" id="p_<?= $s['id'] ?>" <?= $status=='Present'?'checked':'' ?>>
                                <label class="btn btn-outline-success btn-sm" for="p_<?= $s['id'] ?>">Present</label>
                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Absent" id="a_<?= $s['id'] ?>" <?= $status=='Absent'?'checked':'' ?>>
                                <label class="btn btn-outline-danger btn-sm" for="a_<?= $s['id'] ?>">Absent</label>
                                <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" value="Leave" id="l_<?= $s['id'] ?>" <?= $status=='Leave'?'checked':'' ?>>
                                <label class="btn btn-outline-warning btn-sm" for="l_<?= $s['id'] ?>">Leave</label>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </form>
    </div>
</div>

<script>
    function updateAppClock() { const now = new Date(); document.getElementById('app-clock').innerText = now.toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }); }
    setInterval(updateAppClock, 1000); updateAppClock();

    function markAll(status) { document.querySelectorAll(`input[value="${status}"]`).forEach(r => r.checked = true); }
    document.getElementById('studentSearch').addEventListener('keyup', function() { let filter = this.value.toLowerCase(); document.querySelectorAll('#attendanceTable tbody tr').forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; }); });
</script>
<?php require_once '../../includes/footer.php'; ?>