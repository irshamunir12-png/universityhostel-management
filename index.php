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
        SELECT MONTH(COALESCE(paid_date, created_at)) as m, SUM(amount) as total 
        FROM student_fees 
        WHERE LOWER(status) = 'paid' AND YEAR(COALESCE(paid_date, created_at)) = ?
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

// NEW: Data for Leaves Status Chart
$leavesStatus = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM student_leaves 
    GROUP BY status
")->fetchAll();
$leaveLabels = json_encode(array_column($leavesStatus, 'status'));
$leaveData = json_encode(array_column($leavesStatus, 'count'));

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

// --- STUDENT SPECIFIC DATA ---
if ($_SESSION['role'] === 'student') {
    // 1. Detailed Room Info
    $stmt = $pdo->prepare("
        SELECT r.*, ra.bed_no, ra.start_date 
        FROM room_allocations ra 
        JOIN rooms r ON ra.room_id = r.id 
        WHERE ra.user_id = ? AND ra.is_active = 1 LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $myRoomInfo = $stmt->fetch();

    // 2. Paid Fees History
    $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE user_id = ? AND status = 'paid' ORDER BY paid_date DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $paidFeesHistory = $stmt->fetchAll();

    // 3. Attendance Today
    $stmt = $pdo->prepare("SELECT status FROM attendance WHERE user_id = ? AND date = CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $todayAtt = $stmt->fetchColumn();

    // 4. Pending Fees Count for Status Badge
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_fees WHERE user_id = ? AND status != 'paid'");
    $stmt->execute([$_SESSION['user_id']]);
    $pendingFeesCount = $stmt->fetchColumn();
}
?>

<style>
    .modern-dashboard-wrapper {
        background: #fcfcfc;
        border-radius: 25px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.06);
        margin-top: 20px;
    }
    .chart-card-premium {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03) !important;
        border: 1px solid #f1f1f1 !important;
        transition: 0.3s;
    }
    .chart-card-premium:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.07) !important; }
    
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

    /* Scanning Effect CSS */
    .scan-card {
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.2s ease-in-out;
    }
    .scan-card:active { transform: scale(0.96); }
    .scan-card::after {
        content: "";
        position: absolute;
        top: -100%;
        left: 0;
        width: 100%;
        height: 15px;
        background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.8), transparent);
        z-index: 10;
        pointer-events: none;
    }
    .scan-card:hover::after {
        animation: scanLine 1.5s infinite linear;
    }
    @keyframes scanLine {
        0% { top: -10%; }
        100% { top: 110%; }
    }
</style>

<div class="container modern-dashboard-wrapper">
    <?php if ($_SESSION['role'] === 'super_admin'): ?>
    <!-- ================= ADMIN DASHBOARD START ================= -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h3 class="fw-bold text-dark mb-1">Welcome back, <span style="color: var(--hostel-purple);"><?= htmlspecialchars($_SESSION['name']) ?></span>! 👋</h3>
            <p class="text-muted">Here is a quick overview of your hostel's performance today.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-dark rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#globalAIModal">
                <i class="bi bi-robot me-2"></i> AI Assistant
            </button>
            <div class="d-inline-block p-2 px-3 bg-light rounded-pill border shadow-sm ms-2 d-none d-md-inline-block">
                <span class="badge bg-success rounded-circle p-1 me-1"> </span>
                <small class="text-muted fw-bold">Online</small>
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
            <div class="card border-0 chart-card-premium overflow-hidden h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow text-success me-2"></i> Operational Revenue Analytics</h5>
                    <small class="text-muted">Yearly Fee Collection (PKR)</small>
                </div>
                <div class="card-body p-4"><canvas id="revenueChart" style="min-height: 280px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 chart-card-premium h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i> Room Occupancy</h5>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <div style="width: 100%; height: 200px;"><canvas id="occupancyChart"></canvas></div>
                    <div class="mt-3 text-center w-100">
                        <span class="badge bg-success-subtle text-success p-2 px-3 rounded-pill fw-bold">Beds Available: <?= $vacantStudentRooms ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Modern Metric Sparklines & Attendance -->
    <div class="row mt-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-0 chart-card-premium h-100 p-4" style="background: linear-gradient(45deg, #0dcaf00d, #fff);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="text-muted small fw-bold text-uppercase m-0">Resolved Issues</h6>
                    <span class="badge bg-info-subtle text-info" style="font-size: 0.6rem;">Monthly Recovered</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold text-dark mb-0"><?= array_sum($monthlyResolved) ?></h3>
                    <div style="width: 100px; height: 45px;">
                        <canvas id="recoveredChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-0 chart-card-premium h-100 p-4" style="background: linear-gradient(45deg, #ffc1070d, #fff);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="text-muted small fw-bold text-uppercase m-0">Leave Management</h6>
                    <span class="badge bg-warning-subtle text-warning" style="font-size: 0.6rem;">Status Overview</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold text-dark mb-0"><?= array_sum(array_column($leavesStatus, 'count')) ?></h3>
                    <div style="width: 100px; height: 45px;">
                        <canvas id="leaveStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card border-0 chart-card-premium h-100 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold mb-0 text-muted small text-uppercase">Attendance Trend</h6>
                </div>
                <div class="card-body px-4 pb-3"><canvas id="attTrendChart" style="min-height: 160px;"></canvas></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- ================= STUDENT DASHBOARD START ================= -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h3 class="fw-bold text-dark mb-1">Hello, <span class="text-primary"><?= explode(' ', $_SESSION['name'])[0] ?></span>! 👋</h3>
            <p class="text-muted">Welcome to your student portal. Stay updated with your hostel activities.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="p-2 px-3 bg-white rounded-pill border shadow-sm d-inline-block">
                <i class="bi bi-calendar-check text-success me-2"></i>
                <small class="fw-bold text-muted">Attendance: <span class="<?= $todayAtt === 'Present' ? 'text-success' : 'text-danger' ?>"><?= $todayAtt ?: 'Not Marked' ?></span></small>
            </div>
        </div>
    </div>

    <!-- Student Quick Info Tiles -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-primary text-white scan-card">
                <div class="d-flex justify-content-between">
                    <div><small class="text-white-50 fw-bold">MY ROOM</small><h4 class="fw-bold mb-0"><?= $myRoomInfo ? $myRoomInfo['room_no'] : 'N/A' ?></h4></div>
                    <i class="bi bi-door-open fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-success text-white scan-card">
                <div class="d-flex justify-content-between">
                    <div><small class="text-white-50 fw-bold">FEE STATUS</small><h4 class="fw-bold mb-0"><?= $pendingFeesCount > 0 ? 'Due' : 'Clear' ?></h4></div>
                    <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-info text-white scan-card">
                <div class="d-flex justify-content-between">
                    <div><small class="text-white-50 fw-bold">MESS TODAY</small><h4 class="fw-bold mb-0">Active</h4></div>
                    <i class="bi bi-egg-fried fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-warning text-dark scan-card">
                <div class="d-flex justify-content-between">
                    <div><small class="text-dark-50 fw-bold">ALERTS</small><h4 class="fw-bold mb-0"><?= $unread_count ?> New</h4></div>
                    <i class="bi bi-bell-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Side: History & Notices -->
        <div class="col-lg-8">
            <!-- Paid Fees Record -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-success me-2"></i> Recent Payment History</h5>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>Title</th><th>Paid Date</th><th>Amount</th><th>Receipt</th></tr></thead>
                            <tbody>
                                <?php foreach($paidFeesHistory as $pf): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($pf['title']) ?></td>
                                    <td class="small text-muted"><?= date('d M Y', strtotime($pf['paid_date'])) ?></td>
                                    <td><span class="badge bg-success-subtle text-success fw-bold">Rs. <?= number_format($pf['amount']) ?></span></td>
                                    <td><a href="student/print_receipt.php?id=<?= $pf['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-printer"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(!$paidFeesHistory): ?><tr><td colspan="4" class="text-center p-4 text-muted">No payment records found.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Digital Notice Board (Latest 3) -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-megaphone-fill text-warning me-2"></i> Notice Board</h5>
                    <a href="student/announcements.php" class="small text-decoration-none">View All</a>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php foreach(array_slice($latestAnnouncements, 0, 3) as $ann): ?>
                        <div class="p-3 mb-3 notice-item rounded-4">
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($ann['title']) ?></h6>
                            <p class="small text-muted mb-1 text-truncate"><?= htmlspecialchars($ann['content']) ?></p>
                            <small class="text-primary" style="font-size: 0.7rem;"><?= date('d M, Y', strtotime($ann['created_at'])) ?> by Warden</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Room & ID -->
        <div class="col-lg-4">
            <!-- My Room Details Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4">
                <h6 class="text-muted small fw-bold text-uppercase mb-3">Allocated Accommodation</h6>
                <?php if($myRoomInfo): ?>
                    <div class="display-4 fw-bold text-primary mb-1"><?= $myRoomInfo['room_no'] ?></div>
                    <p class="fw-bold mb-3 text-dark"><?= htmlspecialchars($myRoomInfo['building']) ?> - <?= htmlspecialchars($myRoomInfo['block'] ?? 'Standard Block') ?></p>
                    <div class="d-flex justify-content-around bg-light p-3 rounded-4">
                        <div><small class="d-block text-muted">Bed No</small><span class="fw-bold">#<?= $myRoomInfo['bed_no'] ?: '1' ?></span></div>
                        <div class="border-start"></div>
                        <div><small class="d-block text-muted">Joined</small><span class="fw-bold"><?= date('M Y', strtotime($myRoomInfo['start_date'])) ?></span></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning rounded-4 small">No active room allocation found. Please contact the warden.</div>
                <?php endif; ?>
            </div>

            <!-- Mini ID Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center" style="background: linear-gradient(135deg, #fdfbff 0%, #f4f0ff 100%);">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode(BASE_URL . 'verify_student.php?reg_no=' . ($_SESSION['registration_no'] ?? '')) ?>" class="mx-auto mb-3 img-thumbnail p-2" style="width: 120px;">
                <h6 class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['name']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($_SESSION['registration_no'] ?? 'ST-2024-XXX') ?></small>
                <hr>
                <a href="profile.php" class="btn btn-sm btn-primary rounded-pill px-4">Update Profile</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Card Image-style Preview Modal -->
<div class="modal fade" id="cardZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-body p-0 d-flex justify-content-center">
                <div id="zoomedCardContainer" style="width: 100%; max-width: 400px; transform: scale(1.1);">
                    <!-- Content injected by JS -->
                </div>
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
    const getLineGradient = (ctx, color) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, color + '44'); 
        gradient.addColorStop(0.5, color + '11');
        gradient.addColorStop(1, color + '00');
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
                    borderColor: '#2ecc71', 
                    backgroundColor: (context) => getLineGradient(context.chart.ctx, '#2ecc71'),
                    fill: true,
                    tension: 0.4,
                    borderWidth: 5,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#2ecc71',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: '#2ecc71',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [8, 4], color: '#f0f0f0', drawBorder: false },
                        ticks: { 
                            padding: 10,
                            callback: value => 'Rs.' + value.toLocaleString() 
                        }
                    },
                    x: { 
                        grid: { display: false },
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
                    backgroundColor: ['#6f42c1', '#2ecc71'],
                    hoverOffset: 15,
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '85%', 
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
                    label: 'Issues',
                    data: <?= json_encode($monthlyResolved) ?>,
                    borderColor: '#0dcaf0',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { y: { display: false }, x: { display: false } }
            }
        });
    }

    // 6. Leave Status Chart
    const ctxLeave = document.getElementById('leaveStatusChart');
    if(ctxLeave) {
        new Chart(ctxLeave, {
            type: 'doughnut',
            data: {
                labels: <?= $leaveLabels ?>,
                datasets: [{
                    data: <?= $leaveData ?>,
                    backgroundColor: ['#ffc107', '#2ecc71', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { y: { display: false }, x: { display: false } }
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
                        backgroundColor: '#2ecc71',
                        borderRadius: 6,
                        barThickness: 8,
                    },
                    {
                        label: 'Absent',
                        data: <?= json_encode($monthlyAtt['Absent']) ?>,
                        backgroundColor: '#ff5f56',
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