<?php 
require_once 'includes/header.php'; 

// Fetch Counts for Admin
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$roleCount = $pdo->query("SELECT COUNT(*) FROM sys_roles")->fetchColumn();
$pageCount = $pdo->query("SELECT COUNT(*) FROM sys_pages")->fetchColumn();
$roomCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$pendingComplaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'pending'")->fetchColumn();
$openDisputes = 0;
try {
    $openDisputes = $pdo->query("SELECT COUNT(*) FROM dispute_reports WHERE status = 'open'")->fetchColumn();
} catch (Exception $e) {}

$studentsOut = 0;
try {
    $studentsOut = $pdo->query("
        SELECT COUNT(u.id)
        FROM users u
        JOIN ( SELECT user_id, MAX(log_time) as max_log_time FROM gate_log GROUP BY user_id ) latest_log ON u.id = latest_log.user_id
        JOIN gate_log gl ON gl.user_id = latest_log.user_id AND gl.log_time = latest_log.max_log_time
        WHERE gl.log_type = 'out' AND u.role = 'student'
    ")->fetchColumn();
} catch (Exception $e) {}

// NEW: Asset Stats
$totalAssets = 0;
$assetsInUse = 0;
try {
    $totalAssets = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
    $assetsInUse = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'in_use'")->fetchColumn();
} catch (Exception $e) { /* Tables might not exist yet */ }


// Fetch specific data for current user (Example: My Active Permissions)
$myPerms = $pdo->prepare("SELECT COUNT(*) FROM role_access WHERE role_key = ?");
$myPerms->execute([$_SESSION['role']]);
$myPermCount = $myPerms->fetchColumn();

// Fetch Student Room for Dashboard
$myRoom = null;
if ($_SESSION['role'] === 'student') {
    $stmt = $pdo->prepare("SELECT r.room_no, r.building FROM room_allocations ra JOIN rooms r ON ra.room_id = r.id WHERE ra.user_id = ? AND ra.is_active = 1 LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $myRoom = $stmt->fetch();
}

// Fetch latest announcements for all users
$latestAnnouncements = $pdo->query(
    "SELECT a.*, u.name as author_name 
     FROM announcements a 
     LEFT JOIN users u ON a.user_id = u.id 
     WHERE (a.expiry_date IS NULL OR a.expiry_date >= CURDATE()) AND a.is_deleted = 0
     ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 5"
)->fetchAll();

// Stats for Charts (Admin Only)
$totalStudentRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE room_type = 'student'")->fetchColumn();
$occupiedStudentRooms = $pdo->query("SELECT COUNT(DISTINCT room_id) FROM room_allocations WHERE is_active = 1")->fetchColumn();
$vacantStudentRooms = $totalStudentRooms - $occupiedStudentRooms;
$studentCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$staffCount = $userCount - $studentCount;
$officeRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE room_type = 'office'")->fetchColumn();
$staffRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE room_type = 'staff'")->fetchColumn();

// NEW: Washroom Stats
$attachedCount = 0;
$commonCount = 0;
try {
    $attachedCount = $pdo->query("SELECT COUNT(*) FROM rooms WHERE washroom_type = 'attached'")->fetchColumn();
    $commonCount = $pdo->query("SELECT COUNT(*) FROM rooms WHERE washroom_type = 'common'")->fetchColumn();
} catch (Exception $e) { /* Column might not exist yet */ }

// NEW: Data for User Roles Chart
$rolesDistribution = $pdo->query("
    SELECT r.role_name, COUNT(u.id) as count 
    FROM users u 
    JOIN sys_roles r ON u.role = r.role_key 
    GROUP BY u.role, r.role_name
")->fetchAll();
$roleLabels = json_encode(array_column($rolesDistribution, 'role_name'));
$roleData = json_encode(array_column($rolesDistribution, 'count'));


// NEW: Data for Complaints Status Chart
$complaintsStatus = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM complaints 
    GROUP BY status
")->fetchAll();
$complaintLabels = json_encode(array_column($complaintsStatus, 'status'));
$complaintData = json_encode(array_column($complaintsStatus, 'count'));

// NEW: Dynamic Revenue Data (Real from DB)
$currentYear = date('Y');
$monthlyRevenue = array_fill(0, 12, 0); // Initialize 12 months with 0

try {
    $stmt = $pdo->prepare("
        SELECT MONTH(paid_date) as m, SUM(amount) as total 
        FROM student_fees 
        WHERE status = 'paid' AND YEAR(paid_date) = ?
        GROUP BY m
    ");
    $stmt->execute([$currentYear]);
    while ($row = $stmt->fetch()) {
        $monthlyRevenue[$row['m'] - 1] = (float)$row['total']; // Month 1 becomes index 0
    }
} catch (Exception $e) { /* Table might not exist yet */ }

// NEW: Data for "Recovered" (Resolved Complaints Trend)
$monthlyResolved = array_fill(0, 12, 0);
try {
    $stmt = $pdo->prepare("
        SELECT MONTH(updated_at) as m, COUNT(*) as total 
        FROM complaints 
        WHERE status = 'resolved' AND YEAR(updated_at) = ?
        GROUP BY m
    ");
    $stmt->execute([$currentYear]);
    while ($row = $stmt->fetch()) {
        $monthlyResolved[$row['m'] - 1] = (int)$row['total'];
    }
} catch (Exception $e) {}

// NEW: Data for "Visitors Trend"
$monthlyVisitors = array_fill(0, 12, 0);
try {
    $stmt = $pdo->prepare("
        SELECT MONTH(check_in) as m, COUNT(*) as total 
        FROM visitors 
        WHERE YEAR(check_in) = ?
        GROUP BY m
    ");
    $stmt->execute([$currentYear]);
    while ($row = $stmt->fetch()) {
        $monthlyVisitors[$row['m'] - 1] = (int)$row['total'];
    }
} catch (Exception $e) {}

// NEW: Data for Attendance Trend Bar Chart
$monthlyAtt = ['Present' => array_fill(0, 12, 0), 'Absent' => array_fill(0, 12, 0)];
try {
    $stmt = $pdo->prepare("
        SELECT MONTH(date) as m, status, COUNT(*) as total 
        FROM attendance 
        WHERE YEAR(date) = ? AND status IN ('Present', 'Absent')
        GROUP BY m, status
    ");
    $stmt->execute([$currentYear]);
    while ($row = $stmt->fetch()) {
        $monthlyAtt[$row['status']][$row['m'] - 1] = (int)$row['total'];
    }
} catch (Exception $e) {}

// NEW: Student Detailed List (Instead of Charts)
$studentOverview = $pdo->query("
    SELECT 
        u.name, u.registration_no, u.is_active,
        r.room_no, r.building,
        (SELECT COUNT(*) FROM student_fees WHERE user_id = u.id AND status != 'paid') as pending_fees
    FROM users u
    LEFT JOIN room_allocations ra ON u.id = ra.user_id AND ra.is_active = 1
    LEFT JOIN rooms r ON ra.room_id = r.id
    WHERE u.role = 'student'
    ORDER BY u.id DESC LIMIT 10
")->fetchAll();

// Fetch Today's Attendance (Latest 5)
$todaysAttendance = [];
try {
    $todaysAttendance = $pdo->query("
        SELECT a.*, u.name, u.registration_no 
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.date = CURDATE() 
        ORDER BY a.created_at DESC LIMIT 5
    ")->fetchAll();
} catch (Exception $e) { /* Table might not exist yet */ }
?>

<style>
    .modern-dashboard-wrapper {
        background: white;
        border-radius: 25px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.06);
        margin-top: 20px;
    }
    .module-item {
        text-align: center;
        padding: 15px;
        transition: all 0.3s ease;
    }
    .module-item:hover {
        transform: translateY(-8px);
    }
    .module-icon-circle {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        transition: 0.3s;
    }
    .module-label-btn {
        background-color: #6f42c1;
        color: white !important;
        border-radius: 30px;
        padding: 6px 25px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.2);
        border: none;
        width: 100%;
        max-width: 180px;
        transition: 0.3s;
    }
    .module-label-btn:hover {
        background-color: #59359a;
        transform: scale(1.05);
        color: white !important;
    }
    .notice-item {
        border-left: 3px solid var(--hostel-purple);
        background: #fdfbff;
        transition: 0.3s;
    }
    .notice-item:hover { background: #f4f0ff; }
</style>

<div class="container modern-dashboard-wrapper">
    <!-- Welcome Banner -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h3 class="fw-bold text-dark mb-1">Welcome back, <span style="color: var(--hostel-purple);"><?= htmlspecialchars($_SESSION['name']) ?></span>! 👋</h3>
            <p class="text-muted">Here is a quick overview of your hostel's performance today.</p>
        </div>
        <div class="col-md-4 text-md-end d-none d-md-block">
            <div class="d-inline-block p-2 px-3 bg-light rounded-pill border shadow-sm">
                <span class="badge bg-success rounded-circle p-1 me-1"> </span>
                <small class="text-muted fw-bold">System Status: <span class="text-success">Online</span></small>
            </div>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4 justify-content-center">
        <!-- 1. Room Management -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-success"><i class="bi bi-door-open-fill"></i></div>
                <a href="dashboards/super_admin/manage_rooms.php" class="module-label-btn shadow">Room (<?= $roomCount ?>)</a>
            </div>
        </div>
        <!-- 2. Student Details -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-primary"><i class="bi bi-mortarboard-fill"></i></div>
                <a href="dashboards/super_admin/manage_users.php" class="module-label-btn shadow">Student (<?= $studentCount ?>)</a>
            </div>
        </div>
        <!-- 3. Reservation / Allocation -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-warning"><i class="bi bi-calendar-check-fill"></i></div>
                <a href="dashboards/super_admin/allocate_rooms.php" class="module-label-btn shadow">Reservation</a>
            </div>
        </div>
        <!-- 4. User & Staff Management -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-info"><i class="bi bi-person-fill-gear"></i></div>
                <a href="dashboards/super_admin/manage_users.php" class="module-label-btn shadow">User Manage</a>
            </div>
        </div>
        <!-- 5. Key Money / Fee Management -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-danger"><i class="bi bi-key-fill"></i></div>
                <a href="dashboards/super_admin/manage_fees.php" class="module-label-btn shadow">Key Money</a>
            </div>
        </div>
        <!-- 6. Reservation Details / Reports -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-secondary"><i class="bi bi-file-earmark-text-fill"></i></div>
                <a href="dashboards/super_admin/reports.php" class="module-label-btn shadow">Reports</a>
            </div>
        </div>
        <!-- 7. Complaints Management -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <a href="dashboards/super_admin/manage_complaints.php" class="module-label-btn shadow">Complaints (<?= $pendingComplaints ?>)</a>
            </div>
        </div>
        <!-- 8. Assets & Inventory -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-dark"><i class="bi bi-box-seam-fill"></i></div>
                <a href="dashboards/super_admin/manage_assets.php" class="module-label-btn shadow">Assets (<?= $totalAssets ?>)</a>
            </div>
        </div>
        <!-- 9. Gate Log / Security -->
        <div class="col">
            <div class="module-item">
                <div class="module-icon-circle text-success"><i class="bi bi-door-closed-fill"></i></div>
                <a href="dashboards/super_admin/gate_management.php" class="module-label-btn shadow">Outside (<?= $studentsOut ?>)</a>
            </div>
        </div>
    </div>
    <!-- Charts Section -->
    <div class="row mt-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-success me-2"></i> Fee Collection (PKR)</h5>
                </div>
                <div class="card-body p-0"><canvas id="revenueChart" style="min-height: 320px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i> Room Occupancy</h5>
                </div>
                <div class="card-body"><canvas id="occupancyChart" style="min-height: 280px;"></canvas></div>
            </div>

            <!-- Latest Announcements Section to fill space -->
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-megaphone-fill text-warning me-2"></i> Recent Notices</h5>
                    <a href="dashboards/super_admin/manage_announcements.php" class="small text-decoration-none" style="color: var(--hostel-purple);">View All</a>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if(empty($latestAnnouncements)): ?>
                        <p class="text-muted small text-center my-3">No active announcements.</p>
                    <?php else: ?>
                        <?php foreach($latestAnnouncements as $ann): ?>
                            <div class="p-2 mb-2 notice-item rounded-3">
                                <h6 class="mb-0 fw-bold small text-dark"><?= htmlspecialchars($ann['title']) ?></h6>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= date('d M, Y', strtotime($ann['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Recovered, Visitors & Attendance -->
    <div class="row mt-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h6 class="fw-bold mb-0 text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Resolved Issues</h6>
                </div>
                <div class="card-body"><canvas id="recoveredChart" style="min-height: 200px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h6 class="fw-bold mb-0 text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Visitor Traffic</h6>
                </div>
                <div class="card-body"><canvas id="visitorChart" style="min-height: 200px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h6 class="fw-bold mb-0 text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Attendance Trend</h6>
                </div>
                <div class="card-body"><canvas id="attTrendChart" style="min-height: 200px;"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Professional Global Chart Settings
Chart.defaults.font.family = "'Inter', 'Poppins', sans-serif";
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.tooltip.backgroundColor = '#1e293b';
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 8;

document.addEventListener("DOMContentLoaded", function() {
    const getGradient = (ctx, color) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, color + '33'); // 20% opacity
        gradient.addColorStop(1, color + '00'); // 0% opacity
        return gradient;
    };

    // 1. Revenue Chart
    const ctxRev = document.getElementById('revenueChart');
    if(ctxRev) {
        const chart = new Chart(ctxRev, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Fees Collected (PKR)',
                    data: <?= json_encode($monthlyRevenue) ?>,
                    borderColor: '#10b981', 
                    backgroundColor: (context) => getGradient(context.chart.ctx, '#10b981'),
                    fill: true,
                    tension: 0.45,
                    borderWidth: 4,
                    pointRadius: 0, // Hidden points for cleaner look
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#10b981',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [5, 5], color: '#f1f5f9', drawBorder: false },
                        ticks: { padding: 10 }
                    },
                    x: { 
                        grid: { display: false, drawBorder: false },
                        ticks: { padding: 10 }
                    }
                }
            }
        });
    }

    // 2. Occupancy Chart
    const ctxOcc = document.getElementById('occupancyChart');
    if(ctxOcc) {
        new Chart(ctxOcc, {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Vacant'],
                datasets: [{
                    data: [<?= $occupiedStudentRooms ?>, <?= $vacantStudentRooms ?>],
                    backgroundColor: ['#ef4444', '#10b981'],
                    hoverOffset: 10,
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '75%', 
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } } 
            }
        });
    }

    // 3. User Roles Chart
    const ctxRoles = document.getElementById('rolesChart');
    if(ctxRoles) {
        new Chart(ctxRoles, {
            type: 'bar',
            data: {
                labels: <?= $roleLabels ?>,
                datasets: [{
                    label: 'User Count',
                    data: <?= $roleData ?>,
                    backgroundColor: ['#0d6efd', '#6c757d', '#198754', '#ffc107', '#0dcaf0'],
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // 4. Complaints Status Chart
    const ctxComplaints = document.getElementById('complaintsChart');
    if(ctxComplaints) {
        new Chart(ctxComplaints, {
            type: 'doughnut',
            data: {
                labels: <?= $complaintLabels ?>,
                datasets: [{
                    label: 'Count',
                    data: <?= $complaintData ?>,
                    backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#dc3545'],
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 5. Recovered (Resolved Complaints) Chart
    const ctxRec = document.getElementById('recoveredChart');
    if(ctxRec) {
        new Chart(ctxRec, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Resolved',
                    data: <?= json_encode($monthlyResolved) ?>,
                    borderColor: '#06b6d4',
                    backgroundColor: (context) => getGradient(context.chart.ctx, '#06b6d4'),
                    fill: true,
                    tension: 0.45,
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#06b6d4',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
            }
        });
    }

    // 6. Visitor Traffic Chart
    const ctxVis = document.getElementById('visitorChart');
    if(ctxVis) {
        new Chart(ctxVis, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Visitors',
                    data: <?= json_encode($monthlyVisitors) ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: (context) => getGradient(context.chart.ctx, '#8b5cf6'),
                    fill: true,
                    tension: 0.45,
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#8b5cf6',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
            }
        });
    }

    // 7. Attendance Trend Chart (New Bar Chart)
    const ctxAtt = document.getElementById('attTrendChart');
    if(ctxAtt) {
        new Chart(ctxAtt, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Present',
                        data: <?= json_encode($monthlyAtt['Present']) ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        barThickness: 8,
                    },
                    {
                        label: 'Absent',
                        data: <?= json_encode($monthlyAtt['Absent']) ?>,
                        backgroundColor: '#f43f5e',
                        borderRadius: 6,
                        barThickness: 8,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { display: false }, ticks: { display: false } }, 
                    x: { grid: { display: false } } 
                }
            }
        });
    }
});

// Animated Counters Effect
const counters = document.querySelectorAll('.small-box .inner h3');
counters.forEach(counter => {
    const target = +counter.innerText.replace(/,/g, ''); // Get the number
    if(target > 0) {
        const duration = 1000; // Animation time in ms
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.innerText = Math.ceil(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.innerText = target;
            }
        };
        updateCounter();
    }
});

function viewNotice(title, content, date) {
    document.getElementById('nt_title').innerText = title;
    document.getElementById('nt_content').innerText = content;
    document.getElementById('nt_date').innerHTML = '<i class="bi bi-calendar-event me-1"></i> Posted on: ' + date;
    const modal = new bootstrap.Modal(document.getElementById('noticeModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>