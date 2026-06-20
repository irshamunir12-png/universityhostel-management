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
    $fees['this_month'] = $pdo->query("SELECT SUM(amount) FROM student_fees WHERE LOWER(status) = 'paid' AND MONTH(COALESCE(paid_date, created_at)) = MONTH(CURDATE()) AND YEAR(COALESCE(paid_date, created_at)) = YEAR(CURDATE())")->fetchColumn();
    
    // Last Month
    $fees['last_month'] = $pdo->query("SELECT SUM(amount) FROM student_fees WHERE LOWER(status) = 'paid' AND MONTH(COALESCE(paid_date, created_at)) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(COALESCE(paid_date, created_at)) = YEAR(CURDATE() - INTERVAL 1 MONTH)")->fetchColumn();

    // This Year
    $fees['this_year'] = $pdo->query("SELECT SUM(amount) FROM student_fees WHERE LOWER(status) = 'paid' AND YEAR(COALESCE(paid_date, created_at)) = YEAR(CURDATE())")->fetchColumn();
} catch (Exception $e) {
    // In case table doesn't exist
}

// --- Fee Collection Details (Real Data) ---
$fee_details = $pdo->query("
    SELECT f.*, u.name, u.registration_no 
    FROM student_fees f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.status = 'paid' 
    ORDER BY f.paid_date DESC LIMIT 10
")->fetchAll();


// --- Attendance Report ---
$att_report = null;
$att_details = [];
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

    // Detailed Logs for PDF
    $stmt2 = $pdo->prepare("
        SELECT a.date, u.name, u.registration_no, a.status, r.room_no, r.building 
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN room_allocations ra ON (u.id = ra.user_id AND ra.is_active = 1)
        LEFT JOIN rooms r ON ra.room_id = r.id
        WHERE a.date BETWEEN ? AND ?
        ORDER BY a.date DESC LIMIT 100
    ");
    $stmt2->execute([$start_date, $end_date]);
    $att_details = $stmt2->fetchAll();
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

<!-- PDF Generation Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    .btn-pdf { font-size: 0.7rem; font-weight: 700; border-radius: 50px; padding: 4px 12px; text-transform: uppercase; }
</style>

<!-- Fee Collection Cards -->
<div class="card card-outline card-success mb-4" id="feeReport">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="bi bi-cash-stack"></i> Fee Collection Summary</h3>
        <button onclick="downloadPDF('feeReport', 'Fee_Collection_Report')" class="btn btn-outline-success btn-pdf shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> EXPORT PDF
        </button>
    </div>
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
        
        <!-- Detailed Table for Fees -->
        <div class="mt-4">
            <h6 class="fw-bold text-muted small text-uppercase mb-3">Recent Real-time Collections</h6>
            <table class="table table-sm table-bordered small">
                <thead class="table-light">
                    <tr><th>Student</th><th>Reg No</th><th>Fee Title</th><th>Paid Date</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <?php foreach($fee_details as $fd): ?>
                    <tr>
                        <td><?= htmlspecialchars($fd['name']) ?></td>
                        <td><?= htmlspecialchars($fd['registration_no']) ?></td>
                        <td><?= htmlspecialchars($fd['title']) ?></td>
                        <td><?= date('d M Y', strtotime($fd['paid_date'])) ?></td>
                        <td class="fw-bold text-success">Rs. <?= number_format($fd['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Attendance Report -->
<div class="card card-outline card-primary" id="attendanceReport">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="bi bi-calendar-check"></i> Attendance Report</h3>
        <button onclick="downloadPDF('attendanceReport', 'Attendance_Report')" class="btn btn-outline-primary btn-pdf shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> EXPORT PDF
        </button>
    </div>
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

            <h6 class="fw-bold text-muted small text-uppercase mt-4 mb-3">Attendance Activity Log</h6>
            <table class="table table-sm table-bordered small">
                <thead class="table-light"><tr><th>Date</th><th>Student Name</th><th>Reg No</th><th>Assigned Room</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($att_details as $ad): ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($ad['date'])) ?></td>
                        <td><?= htmlspecialchars($ad['name']) ?></td>
                        <td><?= htmlspecialchars($ad['registration_no']) ?></td>
                        <td><span class="small"><?= $ad['room_no'] ? htmlspecialchars($ad['building'] . '-' . $ad['room_no']) : 'Unallocated' ?></span></td>
                        <td><span class="small fw-bold"><?= $ad['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
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
        <div class="card card-outline card-info h-100" id="occupancyReport">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="bi bi-building"></i> Room Occupancy</h3>
                <button onclick="downloadPDF('occupancyReport', 'Room_Occupancy_Report')" class="btn btn-outline-info btn-pdf shadow-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                </button>
            </div>
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
            <!-- Complaints Visual Chart -->
            <div class="col-12 mb-3">
                <div class="card card-outline card-warning" id="complaintsReport">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="bi bi-pie-chart"></i> Complaints Analytics</h3>
                        <button onclick="downloadPDF('complaintsReport', 'Complaints_Summary')" class="btn btn-outline-warning btn-pdf shadow-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <canvas id="complaintsChart" style="max-height: 180px;"></canvas>
                            </div>
                            <div class="col-md-7">
                                <div class="list-group list-group-flush small">
                                    <div class="list-group-item d-flex justify-content-between"><span><i class="bi bi-circle-fill text-warning me-2"></i>Pending</span> <b><?= $complaintStats['pending'] ?? 0 ?></b></div>
                                    <div class="list-group-item d-flex justify-content-between"><span><i class="bi bi-circle-fill text-info me-2"></i>In Progress</span> <b><?= $complaintStats['in_progress'] ?? 0 ?></b></div>
                                    <div class="list-group-item d-flex justify-content-between"><span><i class="bi bi-circle-fill text-success me-2"></i>Resolved</span> <b><?= $complaintStats['resolved'] ?? 0 ?></b></div>
                                    <div class="list-group-item d-flex justify-content-between"><span><i class="bi bi-circle-fill text-danger me-2"></i>Rejected</span> <b><?= $complaintStats['rejected'] ?? 0 ?></b></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="col-12">
                <div class="card card-outline card-secondary" id="inventoryReport">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="bi bi-box-seam"></i> Inventory Condition</h3>
                        <button onclick="downloadPDF('inventoryReport', 'Inventory_Status_Report')" class="btn btn-outline-secondary btn-pdf shadow-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </button>
                    </div>
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

<script>
/**
 * Professional PDF Export Logic
 */
function downloadPDF(elementId, filename) {
    const originalElement = document.getElementById(elementId);
    
    // Clone the element to avoid flickering on the main UI
    const element = originalElement.cloneNode(true);
    
    // Remove buttons and forms from the PDF view
    element.querySelectorAll('.btn-pdf, form, .card-tools').forEach(el => el.remove());

    // Add a Custom Report Header
    const header = document.createElement('div');
    header.style.textAlign = 'center';
    header.style.marginBottom = '25px';
    header.style.borderBottom = '3px solid #198754';
    header.style.paddingBottom = '10px';
    header.innerHTML = `
        <h1 style="color: #198754; margin: 0; font-family: sans-serif;">RESIDENCE HOSTEL ERP</h1>
        <p style="color: #666; margin: 5px 0;">Official Performance & Activity Report</p>
        <small style="color: #999;">Generated on: ${new Date().toLocaleString()}</small>
    `;
    element.prepend(header);

    const opt = {
        margin:       15,
        filename:     filename + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, letterRendering: true, scrollY: 0 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}

// Initialize Complaints Doughnut Chart
const compCtx = document.getElementById('complaintsChart');
if(compCtx) {
    new Chart(compCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Resolved', 'Rejected'],
            datasets: [{
                data: [<?= $complaintStats['pending'] ?? 0 ?>, <?= $complaintStats['in_progress'] ?? 0 ?>, <?= $complaintStats['resolved'] ?? 0 ?>, <?= $complaintStats['rejected'] ?? 0 ?>],
                backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#dc3545'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            cutout: '75%'
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>