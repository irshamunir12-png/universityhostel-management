<?php
require_once __DIR__ . '/../core/session.php';

// 1. Fetch System Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 2. Identify Current Page & Security Check
$current_url = substr($_SERVER['SCRIPT_NAME'], strlen('/universal/')); // Adjust offset
// Clean URL for DB matching (assuming DB stores relative paths)
$db_url_match = $current_url; 
// If your script is in a folder, the DB url should match "dashboards/super_admin/file.php"

// Fetch Page Info
$pageStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE page_url = ? LIMIT 1");
$pageStmt->execute([$current_url]); 
$currentPageData = $pageStmt->fetch();

$pageTitle = $currentPageData['page_name'] ?? 'Dashboard';
$pageId = $currentPageData['id'] ?? 0;

// 3. Security Access Check (The Gatekeeper)
if ($pageId > 0 && $_SESSION['role'] !== 'super_admin') {
    $accessStmt = $pdo->prepare("SELECT * FROM role_access WHERE role_key = ? AND page_id = ?");
    $accessStmt->execute([$_SESSION['role'], $pageId]);
    if ($accessStmt->rowCount() == 0) {
        die('<div class="alert alert-danger m-5">⛔ Access Denied: You do not have permission to view this page.</div>');
    }
}

// 4. Breadcrumb Logic (Recursive Upwards)
$breadcrumbs = [];
if ($currentPageData) {
    $crumbId = $currentPageData['id'];
    while($crumbId != 0) {
        $crumbStmt = $pdo->prepare("SELECT id, parent_id, page_name, page_url FROM sys_pages WHERE id = ?");
        $crumbStmt->execute([$crumbId]);
        $crumb = $crumbStmt->fetch();
        array_unshift($breadcrumbs, $crumb); // Add to beginning
        $crumbId = $crumb['parent_id'];
    }
}

// 5. Notification & SLA Logic (Auto-Check)
$my_notifs = [];
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    // SLA Check (Only runs for Super Admin to save resources)
    if ($_SESSION['role'] === 'super_admin') {
        try {
            // Find complaints that exceeded SLA time and haven't been flagged yet
            $breached = $pdo->query("
                SELECT c.id, c.title, cat.sla_hours 
                FROM complaints c
                JOIN complaint_categories cat ON c.category_id = cat.id
                WHERE c.status IN ('pending', 'in_progress') 
                AND c.sla_breached = 0
                AND c.created_at < (NOW() - INTERVAL cat.sla_hours HOUR)
            ")->fetchAll();

            if ($breached) {
                $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
                $markStmt = $pdo->prepare("UPDATE complaints SET sla_breached = 1 WHERE id = ?");
                foreach ($breached as $b) {
                    $msg = "SLA Alert: Complaint #{$b['id']} ({$b['title']}) has crossed {$b['sla_hours']} hours limit!";
                    $notifStmt->execute([$_SESSION['user_id'], 'SLA Breached', $msg, 'dashboards/super_admin/manage_complaints.php']);
                    $markStmt->execute([$b['id']]);
                }
            }
        } catch (Exception $e) { /* Ignore if tables not ready */ }
    }

    // Fetch My Notifications
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
        $stmt->execute([$_SESSION['user_id']]);
        $my_notifs = $stmt->fetchAll();
        $unread_count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$_SESSION['user_id']} AND is_read = 0")->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($settings['system_name']) ?></title>
    
    <script>
        // Immediately check local storage to prevent "White Flash"
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        } else {
            // Default to system preference if no choice made
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemTheme);
        }
    </script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/adminlte.min.css" />

    <style>
        :root {
            --hostel-green: #198754;
            --hostel-purple: #6f42c1;
        }
        /* Header Customization */
        .app-header.navbar { 
            background: #198754 !important; 
            border-bottom: none; 
            height: 70px; 
            padding: 0 20px; 
        }
        .navbar-nav .nav-link { color: white !important; }
        .dashboard-title { 
            color: #f8fafc; 
            font-weight: 900; 
            font-size: 1.8rem; 
            text-transform: uppercase; 
            letter-spacing: 2px;
        }
        
        /* Modern Sidebar Styles */
        .app-sidebar { background-color: #1e293b !important; }
        .sidebar-menu .nav-link {
            border-radius: 10px;
            margin: 4px 12px;
            color: #cbd5e1 !important;
            transition: all 0.25s ease;
        }
        .sidebar-menu .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: #fff !important;
        }
        .sidebar-menu .nav-link.active {
            background-color: #3b82f6 !important; /* Soft Blue */
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        .sidebar-brand { background-color: #111827 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .nav-header { color: #64748b !important; font-weight: 700; font-size: 0.75rem; letter-spacing: 1px; padding: 1.5rem 1rem 0.5rem !important; }
        .header-right-group { display: flex; align-items: center; gap: 20px; color: white; }
        #live-clock { 
            font-weight: 500; 
            font-size: 0.9rem; 
            background: rgba(255,255,255,0.1); 
            padding: 5px 15px; 
            border-radius: 50px; 
        }
        /* Button and Dropdown Styling */
        .logout-btn {
            background-color: white; 
            color: var(--hostel-green) !important; 
            font-weight: bold; 
            border-radius: 25px; 
            padding: 6px 20px; 
            transition: 0.3s; 
            text-decoration: none;
        }
        .logout-btn:hover { background-color: #f1f1f1; transform: scale(1.05); color: var(--hostel-green) !important; }
        .dropdown-menu { border-radius: 12px; margin-top: 10px !important; border: none; }
        
        .app-brand-logo { height: 30px; width: auto; }
        .user-image { width: 30px; height: 30px; object-fit: cover; }

        @media (min-width: 992px) {
            .sidebar-mini.sidebar-collapse .app-sidebar:hover { width: 250px !important; transition: width 0.3s ease; }
            .sidebar-mini.sidebar-collapse .app-sidebar:hover .brand-text,
            .sidebar-mini.sidebar-collapse .app-sidebar:hover .nav-link p { display: inline-block !important; }
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini sidebar-collapse">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand shadow">
        <div class="container-fluid">
            <!-- Left: Sidebar Toggle -->
            <ul class="navbar-nav">
                <li class="nav-item"> 
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list fs-4"></i>
                    </a> 
                </li>
            </ul>

            <!-- Center: Dashboard Title -->
            <div class="mx-auto">
                <span class="dashboard-title">Dashboard</span>
            </div>

            <!-- Right Group: Clock & Sign Out -->
            <div class="header-right-group">
                <div id="live-clock" class="d-none d-lg-block"></div>
                <div class="dropdown">
                    <button class="logout-btn shadow-sm dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i> Account
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger fw-bold" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('live-clock').innerText = now.toLocaleDateString('en-US', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    <?php include 'sidebar.php'; ?>
    
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0"><?= $pageTitle ?></h3></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                            <?php foreach($breadcrumbs as $b): ?>
                                <li class="breadcrumb-item <?= ($b['id'] == $pageId) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($b['page_name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>

            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">