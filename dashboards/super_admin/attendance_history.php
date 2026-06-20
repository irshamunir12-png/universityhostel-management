<?php
require_once '../../core/session.php';
require_once '../../core/functions.php';

// Filters Logic
$student_q = sanitize($_GET['student'] ?? '');
$room_id = (int)($_GET['room_id'] ?? 0);
$start_date = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$end_date = sanitize($_GET['end_date'] ?? date('Y-m-d'));

// Build Query for Attendance Logs
$sql = "
    SELECT a.*, u.name, u.registration_no, r.room_no, r.building 
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1)
    LEFT JOIN rooms r ON ra.room_id = r.id
    WHERE a.date BETWEEN ? AND ?
";
$params = [$start_date, $end_date];

if (!empty($student_q)) {
    $sql .= " AND (u.name LIKE ? OR u.registration_no LIKE ?)";
    $params[] = "%$student_q%";
    $params[] = "%$student_q%";
}

if ($room_id > 0) {
    $sql .= " AND r.id = ?";
    $params[] = $room_id;
}

$sql .= " ORDER BY a.date DESC, u.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Fetch rooms for filter dropdown
$rooms = $pdo->query("SELECT id, room_no, building FROM rooms WHERE is_deleted = 0 ORDER BY room_no ASC")->fetchAll();

require_once '../../includes/header.php';
?>

<style>
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }
    .history-card { background: #ffffff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow: hidden; margin-top: 10px; border: none; }
    .history-header { background: linear-gradient(to right, #6f42c1, #a445b2) !important; padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; color: white; }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }
    
    .title-bold { font-weight: 850; font-size: 1.6rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; }
    .btn-app-home { background: white; color: #6f42c1 !important; font-weight: bold; border-radius: 15px; padding: 5px 20px; text-decoration: none; transition: 0.3s; }
    
    .filter-section { padding: 30px; background: #fff; border-bottom: 1px solid #f1f1f1; }
    .underline-input { border: none; border-bottom: 2px solid #eee; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; font-size: 1rem; width: 100%; transition: 0.3s; }
    .underline-input:focus { outline: none; border-bottom-color: #6f42c1; }

    .status-pill { border-radius: 50px; padding: 4px 12px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .bg-present { background: #d1e7dd; color: #198754; }
    .bg-absent { background: #f8d7da; color: #dc3545; }
    .bg-leave { background: #fff3cd; color: #856404; }

    .table-section { padding: 30px; }
    .history-table th { background: #fdfbff; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; border: none; }
    .history-table td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f8f9fa; }
</style>

<div class="history-card">
    <div class="history-header">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="title-bold"><i class="bi bi-clock-history me-2"></i>Attendance Logs</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="filter-section">
        <form method="get" class="row g-4 align-items-end">
            <div class="col-md-3">
                <label class="text-muted small fw-bold text-uppercase">Student Lookup</label>
                <input type="text" name="student" class="underline-input" placeholder="Name or Reg No" value="<?= htmlspecialchars($student_q) ?>">
            </div>
            <div class="col-md-2">
                <label class="text-muted small fw-bold text-uppercase">Room Filter</label>
                <select name="room_id" class="underline-input">
                    <option value="0">All Rooms</option>
                    <?php foreach($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $room_id == $r['id'] ? 'selected' : '' ?>><?= $r['room_no'] ?> (<?= $r['building'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-muted small fw-bold text-uppercase">From</label>
                <input type="date" name="start_date" class="underline-input" value="<?= $start_date ?>">
            </div>
            <div class="col-md-2">
                <label class="text-muted small fw-bold text-uppercase">To</label>
                <input type="date" name="end_date" class="underline-input" value="<?= $end_date ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark rounded-pill px-4 flex-grow-1 shadow-sm">SEARCH</button>
                <a href="attendance_history.php" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <div class="table-section">
        <div class="table-responsive rounded-3 border">
            <table class="table table-hover history-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Log Date</th>
                        <th>Student Name</th>
                        <th>Room Info</th>
                        <th class="text-center">Attendance Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): 
                        $st_class = 'bg-present';
                        if($log['status'] == 'Absent') $st_class = 'bg-absent';
                        if($log['status'] == 'Leave') $st_class = 'bg-leave';
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?= date('d M Y', strtotime($log['date'])) ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($log['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($log['registration_no']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= $log['building'] ?? 'N/A' ?> - <?= $log['room_no'] ?? 'Unallocated' ?></span></td>
                        <td class="text-center">
                            <span class="status-pill <?= $st_class ?>"><?= strtoupper($log['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="4" class="text-center p-5 text-muted">No attendance history matches your search.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>