<?php
require_once '../../includes/header.php';

// --- Fee Collection Report ---
$fees = [
    'this_month' => 0,
    'last_month' => 0,
    'this_year' => 0,
];
try {
    // This Month
    $fees['this_month'] = $pdo->query("SELECT SUM(amount) FROM student_fees WHERE status = 'paid' AND MONTH(paid_date) = MONTH(CURDATE()) AND YEAR(paid_date) = YEAR(CURDATE())")->fetchColumn();
    
    // Last Month
    $fees['last_month'] = $pdo->query("SELECT SUM(amount) FROM student_fees WHERE status = 'paid' AND MONTH(paid_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(paid_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)")->fetchColumn();

    // This Year
    $fees['this_year'] = $pdo->query("SELECT SUM(amount) FROM student_fees WHERE status = 'paid' AND YEAR(paid_date) = YEAR(CURDATE())")->fetchColumn();
} catch (Exception $e) {
    // In case table doesn't exist
}


// --- Attendance Report ---
$att_report = null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if (isset($_GET['start_date'])) {
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM attendance 
        WHERE date BETWEEN ? AND ? 
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $att_report = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// --- 3. Room Occupancy Stats ---
$roomStats = $pdo->query("
    SELECT 
        r.building, 
        COUNT(r.id) as total_rooms, 
        SUM(r.capacity) as total_beds,
        (SELECT COUNT(*) FROM room_allocations ra JOIN rooms r2 ON ra.room_id = r2.id WHERE r2.building = r.building AND ra.is_active = 1) as occupied_beds
    FROM rooms r 
    GROUP BY r.building
")->fetchAll();

// --- 4. Complaints Stats ---
$complaintStats = $pdo->query("SELECT status, COUNT(*) as count FROM complaints GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// --- 5. Inventory Stats ---
$inventoryStats = $pdo->query("SELECT item_condition, COUNT(*) as count FROM inventory GROUP BY item_condition")->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<style>
    /* Cards transition */
    .card { 
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }
    
    /* Hover Effect: Highlight border and lift (No Dimming) */
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important;
        border: 3px solid var(--bs-primary) !important;
    }
    .small-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.3) !important;
        z-index: 5;
    }
</style>

<!-- Fee Collection Cards -->
<div class="card card-outline card-success mb-4">
    <div class="card-header"><h3 class="card-title"><i class="bi bi-cash-stack"></i> Fee Collection Summary</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="small-box text-bg-success">
                    <div class="inner"><h3>Rs. <?= number_format($fees['this_month'] ?? 0) ?></h3><p>This Month's Collection</p></div>
                    <div class="icon"><i class="bi bi-calendar-month"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box text-bg-warning">
                    <div class="inner"><h3>Rs. <?= number_format($fees['last_month'] ?? 0) ?></h3><p>Last Month's Collection</p></div>
                    <div class="icon"><i class="bi bi-calendar-minus"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box text-bg-primary">
                    <div class="inner"><h3>Rs. <?= number_format($fees['this_year'] ?? 0) ?></h3><p>This Year's Collection</p></div>
                    <div class="icon"><i class="bi bi-calendar-event"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Attendance Report -->
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="bi bi-calendar-check"></i> Attendance Report</h3></div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end bg-light p-3 border rounded mb-4">
            <div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="<?= $start_date ?>"></div>
            <div class="col-md-4"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="<?= $end_date ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Generate Report</button></div>
        </form>

        <?php if ($att_report !== null): ?>
            <h5>Report for <?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?></h5>
            <table class="table table-bordered mt-3">
                <thead class="table-dark"><tr><th>Status</th><th>Total Count</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Present</span></td><td><?= (int)($att_report['Present'] ?? 0) ?></td></tr>
                    <tr><td><span class="badge bg-danger">Absent</span></td><td><?= (int)($att_report['Absent'] ?? 0) ?></td></tr>
                    <tr><td><span class="badge bg-warning">Leave</span></td><td><?= (int)($att_report['Leave'] ?? 0) ?></td></tr>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center text-muted p-3">Select a date range and click "Generate Report" to view attendance data.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Operational Reports Row -->
<div class="row mt-4">
    <!-- Room Occupancy -->
    <div class="col-md-6">
        <div class="card card-outline card-info h-100">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-building"></i> Room Occupancy by Building</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Building</th><th>Total Rooms</th><th>Beds (Occupied/Total)</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($roomStats as $r): 
                            $percent = $r['total_beds'] > 0 ? round(($r['occupied_beds'] / $r['total_beds']) * 100) : 0;
                            $bg = $percent > 90 ? 'bg-danger' : ($percent > 50 ? 'bg-warning' : 'bg-success');
                        ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($r['building']) ?></td>
                            <td><?= $r['total_rooms'] ?></td>
                            <td>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= $r['occupied_beds'] ?> occupied</span>
                                    <span><?= $r['total_beds'] ?> total</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?= $bg ?>" style="width: <?= $percent ?>%"></div>
                                </div>
                            </td>
                            <td><span class="badge <?= $bg ?>"><?= $percent ?>% Full</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Complaints & Inventory -->
    <div class="col-md-6">
        <div class="row">
            <!-- Complaints -->
            <div class="col-12 mb-3">
                <div class="card card-outline card-warning">
                    <div class="card-header"><h3 class="card-title"><i class="bi bi-exclamation-triangle"></i> Complaints Overview</h3></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between text-center">
                            <div class="p-2 border rounded bg-light flex-fill me-2"><h4 class="text-warning mb-0"><?= $complaintStats['pending'] ?? 0 ?></h4><small>Pending</small></div>
                            <div class="p-2 border rounded bg-light flex-fill me-2"><h4 class="text-info mb-0"><?= $complaintStats['in_progress'] ?? 0 ?></h4><small>In Progress</small></div>
                            <div class="p-2 border rounded bg-light flex-fill me-2"><h4 class="text-success mb-0"><?= $complaintStats['resolved'] ?? 0 ?></h4><small>Resolved</small></div>
                            <div class="p-2 border rounded bg-light flex-fill"><h4 class="text-danger mb-0"><?= $complaintStats['rejected'] ?? 0 ?></h4><small>Rejected</small></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="col-12">
                <div class="card card-outline card-secondary">
                    <div class="card-header"><h3 class="card-title"><i class="bi bi-box-seam"></i> Inventory Condition</h3></div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Good / New Condition
                                <span class="badge bg-success rounded-pill"><?= ($inventoryStats['Good'] ?? 0) + ($inventoryStats['New'] ?? 0) ?> Items</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Repair Needed / Damaged
                                <span class="badge bg-danger rounded-pill"><?= ($inventoryStats['Repair Needed'] ?? 0) + ($inventoryStats['Damaged'] ?? 0) ?> Items</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>