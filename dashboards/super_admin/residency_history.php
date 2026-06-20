<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// 1. Search and Date Filters Logic
$q = sanitize($_GET['q'] ?? '');
$start = sanitize($_GET['from'] ?? '');
$end = sanitize($_GET['to'] ?? '');

// 2. Fetch deallocated (is_active = 0) records
$sql = "
    SELECT ra.*, u.name as student_name, u.registration_no, r.room_no, r.building 
    FROM room_allocations ra 
    LEFT JOIN users u ON ra.user_id = u.id 
    LEFT JOIN rooms r ON ra.room_id = r.id 
    WHERE ra.is_active = 0 
";
$params = [];

if($q) {
    $sql .= " AND (u.name LIKE ? OR u.registration_no LIKE ? OR r.room_no LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if($start) {
    $sql .= " AND ra.end_date >= ?";
    $params[] = $start;
}
if($end) {
    $sql .= " AND ra.end_date <= ?";
    $params[] = $end;
}

$sql .= " ORDER BY ra.end_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll();

// Diagnostic: Check total records in DB for debugging
$totalCount = $pdo->query("SELECT COUNT(*) FROM room_allocations WHERE is_active = 0")->fetchColumn();
?>

<style>
    /* Dashboard UI Consistency */
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }
    .history-card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.05); margin-top: 10px; border: none; }
    .header-grad { background: linear-gradient(to right, #2ecc71, #1abc9c); padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; color: white; }
    .title-bold { font-weight: 850; font-size: 1.7rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    .btn-app-home { background: white; color: #20c997 !important; font-weight: bold; border-radius: 15px; padding: 5px 20px; text-decoration: none; transition: 0.3s; }
    .btn-app-home:hover { transform: scale(1.05); }
    
    .underline-input { border: none; border-bottom: 2px solid #eee; border-radius: 0; padding: 10px 0; background: transparent; font-weight: 600; width: 100%; transition: 0.3s; }
    .underline-input:focus { outline: none; border-bottom-color: #1abc9c; }
</style>

<div class="history-card">
    <div class="header-grad">
        <div class="window-controls d-flex gap-2">
            <span style="width:12px; height:12px; border-radius:50%; background:#ff5f56;"></span>
            <span style="width:12px; height:12px; border-radius:50%; background:#ffbd2e;"></span>
            <span style="width:12px; height:12px; border-radius:50%; background:#009a17;"></span>
        </div>
        <h2 class="title-bold">Residency History (Alumni)</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <!-- Filters Section -->
    <div class="p-4 border-bottom bg-light bg-opacity-50">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="small fw-bold text-muted">SEARCH STUDENT / ROOM</label>
                <input type="text" name="q" class="underline-input" placeholder="Name, ID or Room No..." value="<?= htmlspecialchars($q) ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted">EXIT FROM</label>
                <input type="date" name="from" class="underline-input" value="<?= $start ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted">EXIT TO</label>
                <input type="date" name="to" class="underline-input" value="<?= $end ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-dark rounded-pill px-4 flex-grow-1 shadow-sm">FILTER HISTORY</button>
                <a href="residency_history.php" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <div class="p-4">
        <?php if($totalCount > 0): ?>
            <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-2"></i> System found <strong><?= $totalCount ?></strong> deallocated records in database.</div>
        <?php else: ?>
            <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle me-2"></i> No deallocated records (is_active = 0) found in database.</div>
        <?php endif; ?>

        <div class="table-responsive rounded-3 border shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Student Details</th>
                        <th>Past Accommodation</th>
                        <th>Stay Duration</th>
                        <th>Exit Date</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($history)): ?>
                        <tr><td colspan="5" class="text-center p-5 text-muted">No records found in residency history.</td></tr>
                    <?php endif; ?>
                    <?php foreach($history as $h): 
                        $start = strtotime($h['start_date'] ?? '');
                        $end = strtotime($h['end_date'] ?? date('Y-m-d'));
                        $days = ($start && $end) ? round(($end - $start) / (60 * 60 * 24)) : 0;
                    ?>
                    <tr class="align-middle">
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($h['student_name'] ?? 'Unknown Student') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($h['registration_no'] ?? 'N/A') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($h['building'] ?? 'N/A') ?></span>
                            <span class="fw-bold">Room <?= htmlspecialchars($h['room_no'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <div class="small text-muted">Joined: <?= $h['start_date'] ? date('d M Y', strtotime($h['start_date'])) : 'N/A' ?></div>
                            <div class="fw-bold text-primary"><?= round($days) ?> Days Residency</div>
                        </td>
                        <td><span class="fw-bold text-danger"><?= date('d M Y', strtotime($h['end_date'])) ?></span></td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-secondary px-3" style="font-size: 0.65rem;">VACATED</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>