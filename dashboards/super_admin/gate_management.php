<?php
// 1. Core logic aur Session pehle load karein
require_once '../../core/functions.php';
require_once '../../core/session.php'; 

// Handle Log Deletion (Corrections ke liye)
if (isset($_GET['delete_log'])) {
    $log_id_to_delete = (int)$_GET['delete_log'];
    if ($log_id_to_delete > 0) {
        $pdo->prepare("DELETE FROM gate_log WHERE id = ?")->execute([$log_id_to_delete]);
        $redirect_q = isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '';
        header("Location: gate_management.php?msg=deleted" . $redirect_q);
        exit;
    }
}

// Handle Curfew Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_curfew'])) {
    $new_time = $_POST['new_curfew_time'];
    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'curfew_time'")->execute([$new_time]);
    $success = "Curfew time updated to " . date('h:i A', strtotime($new_time));
    // Refresh settings for immediate display
    $settings['curfew_time'] = $new_time;
}

// 2. Header include karein (Is ke baad HTML shuru ho jati hai)
require_once '../../includes/header.php';

// Fetch Curfew Time from settings
$curfew_time = $settings['curfew_time'] ?? '22:00:00';

// Handle Log Entry (IN/OUT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_entry'])) {
    $user_id = (int)$_POST['user_id'];
    $log_type = $_POST['log_entry']; // 'in' or 'out'

    // Prevent double entry (Check last status)
    $last_log = $pdo->prepare("SELECT log_type FROM gate_log WHERE user_id = ? ORDER BY log_time DESC LIMIT 1");
    $last_log->execute([$user_id]);
    $last_log_type = $last_log->fetchColumn();

    if ($last_log_type === $log_type) {
        $error = "Student is already logged as '$log_type'.";
    } else {
        $is_late = ($log_type === 'in' && date('H:i:s') > $curfew_time) ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO gate_log (user_id, log_type, is_late) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $log_type, $is_late]);
        $success = "Successfully logged as '$log_type'. " . ($is_late ? "<strong class='text-danger'>LATE ENTRY!</strong>" : "");
    }
}

// Data Fetching
// Fetch ALL active students with their LATEST gate status
$students = $pdo->query("
    SELECT u.id, u.name, u.registration_no,
           (SELECT log_type FROM gate_log WHERE user_id = u.id ORDER BY log_time DESC LIMIT 1) as current_status,
           (SELECT log_time FROM gate_log WHERE user_id = u.id ORDER BY log_time DESC LIMIT 1) as last_activity
    FROM users u 
    WHERE u.role = 'student' AND u.is_active = 1 AND u.is_deleted = 0
    ORDER BY u.name ASC
")->fetchAll();

$out_students = $pdo->query("SELECT u.name, u.registration_no, r.room_no, gl.log_time FROM users u JOIN (SELECT user_id, MAX(log_time) as max_t FROM gate_log GROUP BY user_id) latest ON u.id = latest.user_id JOIN gate_log gl ON gl.user_id = latest.user_id AND gl.log_time = latest.max_t LEFT JOIN room_allocations ra ON u.id = ra.user_id AND ra.is_active = 1 LEFT JOIN rooms r ON ra.room_id = r.id WHERE gl.log_type = 'out' AND u.role = 'student' ORDER BY gl.log_time ASC")->fetchAll();
$today_log = $pdo->query("SELECT gl.*, u.name FROM gate_log gl JOIN users u ON gl.user_id = u.id WHERE DATE(gl.log_time) = CURDATE() ORDER BY gl.log_time DESC")->fetchAll();
$late_comers = $pdo->query("SELECT u.name, COUNT(*) as late_count FROM gate_log gl JOIN users u ON gl.user_id = u.id WHERE gl.is_late = 1 AND gl.log_time >= (NOW() - INTERVAL 30 DAY) GROUP BY u.id HAVING late_count > 2 ORDER BY late_count DESC LIMIT 5")->fetchAll();

// Quick Stats for Today
$stats_today = $pdo->query("SELECT 
    SUM(CASE WHEN log_type = 'in' THEN 1 ELSE 0 END) as total_in,
    SUM(CASE WHEN log_type = 'out' THEN 1 ELSE 0 END) as total_out,
    SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) as total_late
    FROM gate_log WHERE DATE(log_time) = CURDATE()")->fetch();
?>

<style>
    /* Clean Dashboard Reset */
    .app-content-header, .content-header, .breadcrumb { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .desktop-app-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-top: 10px;
        border: none;
    }
    .app-header-teal {
        background: linear-gradient(to right, #2ecc71, #1abc9c) !important;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
    }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    
    .app-title-center { font-weight: 850; font-size: 1.8rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    .btn-app-home { background: white; color: #20c997 !important; font-weight: bold; border-radius: 15px; padding: 5px 20px; text-decoration: none; transition: 0.3s; width: 120px; text-align: center; }
    .btn-app-home:hover { transform: scale(1.05); }

    .form-section-app { padding: 30px; background: #fff; }
    .underline-input { border: none; border-bottom: 2px solid #eee; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; font-size: 1.1rem; width: 100%; transition: 0.3s; }
    .underline-input:focus { outline: none; border-bottom-color: #20c997; }

    .btn-save-green { background-color: #198754; color: white; border-radius: 25px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-warning-rounded { background-color: #ffc107; color: #000; border-radius: 25px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-save-green:hover, .btn-warning-rounded:hover { opacity: 0.9; transform: translateY(-2px); }

    .grid-table { border: 1px solid #eee; }
    .grid-table th { background: #fdfbff; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; }
    .grid-table td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f8f9fa; }
    
    .curfew-banner { background: #f8f9fa; color: #333; padding: 15px 25px; border-radius: 15px; font-weight: bold; margin-bottom: 25px; border: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
    .stat-pill { background: #f1f3f5; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; color: #495057; border: 1px solid #e9ecef; }
    .stat-pill.late { background: #fff5f5; color: #fa5252; border-color: #ffe3e3; }

    .mini-input-time { border: none; border-bottom: 2px solid #20c997; background: transparent; font-weight: bold; width: 110px; text-align: center; }
    .btn-update-time { background: #20c997; color: white; border: none; border-radius: 10px; padding: 2px 10px; font-size: 0.75rem; font-weight: bold; }

    .auto-hide { transition: opacity 0.5s ease; }
</style>

<div class="desktop-app-card">
    <!-- Top Header Bar -->
    <div class="app-header-teal">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="app-title-center">Gate Management</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="form-section-app">
        <div class="row">
            <!-- Left Side: Registration Search & Quick Actions -->
            <div class="col-md-5 border-end pe-4">
                <!-- Today's Counters -->
                <div class="d-flex gap-2 mb-4">
                    <div class="stat-pill">IN: <?= (int)$stats_today['total_in'] ?></div>
                    <div class="stat-pill">OUT: <?= (int)$stats_today['total_out'] ?></div>
                    <div class="stat-pill late">LATE: <?= (int)$stats_today['total_late'] ?></div>
                </div>

                <!-- Curfew Settings -->
                <div class="curfew-banner shadow-sm">
                    <div><i class="bi bi-clock-history me-2 text-warning"></i> Curfew: <span class="text-primary"><?= date('h:i A', strtotime($curfew_time)) ?></span></div>
                    <form method="post" class="d-flex gap-2 align-items-center">
                        <input type="time" name="new_curfew_time" class="mini-input-time" value="<?= htmlspecialchars($curfew_time) ?>">
                        <button type="submit" name="update_curfew" class="btn-update-time shadow-sm">SET</button>
                    </form>
                </div>

                <?php if(isset($success)): ?><div class="alert alert-success rounded-3 auto-hide"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $error ?></div><?php endif; ?>

                <div class="mb-4">
                    <label class="text-muted small fw-bold text-uppercase">Quick Search Student</label>
                    <input type="text" id="gateStudentSearch" class="underline-input" placeholder="Start typing name or ID...">
                </div>

                <div class="table-responsive rounded-3 border shadow-sm" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover mb-0" id="masterGateTable">
                        <thead class="sticky-top bg-white">
                            <tr class="small text-muted"><th>STUDENT</th><th class="text-center">ACTION</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach($students as $s): ?>
                        <tr class="align-middle">
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($s['registration_no']) ?></small>
                                <?php if($s['current_status'] === 'out'): ?>
                                    <br><span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.65rem;">CURRENTLY OUTSIDE</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <form method="post" class="d-flex justify-content-center gap-1">
                                    <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="log_entry" value="out" class="btn btn-sm <?= $s['current_status'] === 'out' ? 'btn-outline-secondary opacity-50' : 'btn-warning' ?> rounded-pill px-3 fw-bold shadow-sm" style="min-width: 70px;">OUT</button>
                                    <button type="submit" name="log_entry" value="in" class="btn btn-sm <?= $s['current_status'] !== 'out' ? 'btn-outline-secondary opacity-50' : 'btn-success' ?> rounded-pill px-3 fw-bold shadow-sm" style="min-width: 70px;">IN</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if(!empty($late_comers)): ?>
                <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle mt-4">
                    <h6 class="text-danger fw-bold text-uppercase small mb-3"><i class="bi bi-exclamation-triangle"></i> Habitual Late Comers</h6>
                    <?php foreach($late_comers as $lc): ?>
                        <div class="d-flex justify-content-between align-items-center bg-white p-2 px-3 rounded-pill mb-2 border shadow-sm">
                            <span class="small fw-bold text-dark"><?= htmlspecialchars($lc['name']) ?></span>
                            <span class="badge bg-danger rounded-pill"><?= $lc['late_count'] ?> Times</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Side: Real-time Logs -->
            <div class="col-md-7 ps-4">
                <h6 class="text-muted fw-bold text-uppercase small mb-3">Live Activity Log</h6>
                <div class="table-responsive rounded-3 shadow-sm border mb-4" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover grid-table mb-0">
                        <thead class="sticky-top">
                            <tr><th>Time</th><th>Student</th><th>Action</th><th class="text-center">X</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($today_log)): ?><tr><td colspan="4" class="text-center text-muted p-4">Waiting for activities...</td></tr><?php endif; ?>
                            <?php foreach($today_log as $tl): ?>
                            <tr class="<?= $tl['is_late'] ? 'table-danger' : '' ?>">
                                <td class="fw-bold small"><?= date('h:i A', strtotime($tl['log_time'])) ?></td>
                                <td><?= htmlspecialchars($tl['name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $tl['log_type']=='in'?'success':'warning text-dark' ?>"><?= strtoupper($tl['log_type']) ?></span>
                                    <?php if($tl['is_late']): ?> <span class="badge bg-danger ms-1">LATE</span><?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="?delete_log=<?= $tl['id'] ?>&q=<?= urlencode($q_search) ?>" class="text-danger" onclick="return confirm('Delete this entry?')"><i class="bi bi-trash3"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h6 class="text-primary fw-bold text-uppercase small mb-3">Currently Outside (<?= count($out_students) ?>)</h6>
                <div class="table-responsive rounded-3 shadow-sm border" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-hover grid-table mb-0">
                        <thead class="sticky-top">
                            <tr><th>Student</th><th>Room</th><th>Out Since</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($out_students)): ?><tr><td colspan="3" class="text-center text-muted p-4">All students are currently inside.</td></tr><?php endif; ?>
                            <?php foreach($out_students as $os): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($os['name']) ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($os['room_no'] ?? 'N/A') ?></span></td>
                                <td class="text-muted small fw-bold"><?= date('h:i A', strtotime($os['log_time'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClockApp() {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('live-clock-app').innerText = now.toLocaleDateString('en-US', options);
    }
    setInterval(updateClockApp, 1000);
    updateClockApp();

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            document.querySelectorAll('.auto-hide').forEach(el => el.style.display = 'none');
        }, 5000);

        // Real-time Search Logic
        const searchInput = document.getElementById('gateStudentSearch');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('#masterGateTable tbody tr');
                rows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>
