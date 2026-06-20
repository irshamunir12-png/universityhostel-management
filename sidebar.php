<<<<<<< HEAD
<?php

// 1. Fetch Allowed Pages for Current User
$role = $_SESSION['role'];
$stmt = $pdo->prepare("
    SELECT p.* 
    FROM sys_pages p
    JOIN role_access ra ON p.id = ra.page_id
    WHERE ra.role_key = ?
    ORDER BY p.sort_order ASC
");
$stmt->execute([$role]);
$allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Build Menu Tree (Parent -> Children)
$menu = [];
foreach ($allPages as $p) {
    if ($p['parent_id'] == 0) {
        $menu[$p['id']] = $p;
        $menu[$p['id']]['children'] = [];
    }
}
foreach ($allPages as $p) {
    if ($p['parent_id'] != 0 && isset($menu[$p['parent_id']])) {
        $menu[$p['parent_id']]['children'][] = $p;
    }
}

// Helper to check if a menu item is active
function isActive($url) {
    global $current_url; // Header.php se aa rahi hai
    return ($url == $current_url) ? 'active' : '';
}

function isTreeOpen($children) {
    global $current_url;
    foreach ($children as $child) {
        if ($child['page_url'] == $current_url) return 'menu-open';
    }
    return '';
}
?>

<aside class="app-sidebar shadow" data-bs-theme="dark" style="background-color: #1e293b !important;">
    <div class="sidebar-brand d-flex align-items-center justify-content-between px-3" style="background-color: #111827 !important;">
        <a href="<?= BASE_URL ?>" class="brand-link text-decoration-none">
            <i class="bi bi-grid-1x2-fill me-2 text-primary"></i>
            <span class="brand-text fw-bold" style="letter-spacing: 1px;">University <span class="text-primary">Hostel</span></span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex px-3 align-items-center" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div class="image">
                <img src="<?= !empty($_SESSION['avatar']) ? BASE_URL . $_SESSION['avatar'] : BASE_URL . 'assets/img/avatar.png' ?>" class="rounded-circle" alt="User Image" style="width: 35px; height: 38px; object-fit: cover; border: 2px solid #3b82f6;">
            </div>
            <div class="info ms-3">
                <a href="<?= BASE_URL ?>profile.php" class="d-block text-decoration-none text-white fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['name']) ?></a>
                <small class="text-muted" style="font-size: 0.65rem; text-transform: uppercase;"><?= $_SESSION['role'] ?></small>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <?php foreach ($menu as $item): ?>
                    <?php 
                    // Super Admin ke liye 'Student Portal' section hide karne ka logic
                    if ($_SESSION['role'] === 'super_admin' && $item['page_name'] === 'Student Portal') continue; 
                    ?>

                    <?php if (empty($item['children'])): ?>
                        <!-- Single Item -->
                        <li class="nav-item" title="<?= htmlspecialchars($item['page_name']) ?>">
                            <a href="<?= BASE_URL . $item['page_url'] ?>" class="nav-link <?= isActive($item['page_url']) ?>">
                                <i class="nav-icon <?= $item['icon_class'] ?>"></i>
                                <p><?= htmlspecialchars($item['page_name']) ?></p>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Dropdown Group (Yesterday's feature) -->
                        <li class="nav-item <?= isTreeOpen($item['children']) ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon <?= $item['icon_class'] ?>"></i>
                                <p>
                                    <?= htmlspecialchars($item['page_name']) ?>
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL . $child['page_url'] ?>" class="nav-link <?= isActive($child['page_url']) ?>">
                                            <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                            <p><?= htmlspecialchars($child['page_name']) ?></p>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Logout Button -->
                <li class="nav-item mt-4">
                    <a href="<?= BASE_URL ?>logout.php" class="nav-link bg-danger text-white">
                        <i class="nav-icon bi bi-box-arrow-right"></i>
                        <p>Sign Out</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
=======
<?php

// 1. Fetch Allowed Pages for Current User
$role = $_SESSION['role'];
$stmt = $pdo->prepare("
    SELECT p.* 
    FROM sys_pages p
    JOIN role_access ra ON p.id = ra.page_id
    WHERE ra.role_key = ?
    ORDER BY p.sort_order ASC
");
$stmt->execute([$role]);
$allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Build Menu Tree (Parent -> Children)
$menu = [];
foreach ($allPages as $p) {
    if ($p['parent_id'] == 0) {
        $menu[$p['id']] = $p;
        $menu[$p['id']]['children'] = [];
    }
}
foreach ($allPages as $p) {
    if ($p['parent_id'] != 0 && isset($menu[$p['parent_id']])) {
        $menu[$p['parent_id']]['children'][] = $p;
    }
}

// Helper to check if a menu item is active
function isActive($url) {
    global $current_url; // Header.php se aa rahi hai
    return ($url == $current_url) ? 'active' : '';
}

function isTreeOpen($children) {
    global $current_url;
    foreach ($children as $child) {
        if ($child['page_url'] == $current_url) return 'menu-open';
    }
    return '';
}
?>

<aside class="app-sidebar shadow" data-bs-theme="dark" style="background-color: #1e293b !important;">
    <div class="sidebar-brand d-flex align-items-center justify-content-between px-3" style="background-color: #111827 !important;">
        <a href="<?= BASE_URL ?>" class="brand-link text-decoration-none">
            <i class="bi bi-grid-1x2-fill me-2 text-primary"></i>
            <span class="brand-text fw-bold" style="letter-spacing: 1px;">University <span class="text-primary">Hostel</span></span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex px-3 align-items-center" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div class="image">
                <img src="<?= !empty($_SESSION['avatar']) ? BASE_URL . $_SESSION['avatar'] : BASE_URL . 'assets/img/avatar.png' ?>" class="rounded-circle" alt="User Image" style="width: 35px; height: 38px; object-fit: cover; border: 2px solid #3b82f6;">
            </div>
            <div class="info ms-3">
                <a href="<?= BASE_URL ?>profile.php" class="d-block text-decoration-none text-white fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['name']) ?></a>
                <small class="text-muted" style="font-size: 0.65rem; text-transform: uppercase;"><?= $_SESSION['role'] ?></small>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <?php foreach ($menu as $item): ?>
                    <?php 
                    // Super Admin ke liye 'Student Portal' section hide karne ka logic
                    if ($_SESSION['role'] === 'super_admin' && $item['page_name'] === 'Student Portal') continue; 
                    ?>

                    <?php if (empty($item['children'])): ?>
                        <!-- Single Item -->
                        <li class="nav-item" title="<?= htmlspecialchars($item['page_name']) ?>">
                            <a href="<?= BASE_URL . $item['page_url'] ?>" class="nav-link <?= isActive($item['page_url']) ?>">
                                <i class="nav-icon <?= $item['icon_class'] ?>"></i>
                                <p><?= htmlspecialchars($item['page_name']) ?></p>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Dropdown Group (Yesterday's feature) -->
                        <li class="nav-item <?= isTreeOpen($item['children']) ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon <?= $item['icon_class'] ?>"></i>
                                <p>
                                    <?= htmlspecialchars($item['page_name']) ?>
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL . $child['page_url'] ?>" class="nav-link <?= isActive($child['page_url']) ?>">
                                            <i class="nav-icon bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                            <p><?= htmlspecialchars($child['page_name']) ?></p>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Logout Button -->
                <li class="nav-item mt-4">
                    <a href="<?= BASE_URL ?>logout.php" class="nav-link bg-danger text-white">
                        <i class="nav-icon bi bi-box-arrow-right"></i>
                        <p>Sign Out</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
>>>>>>> 565f129c970092d2d1b44ff2d6bbfebc50c7c53d
</aside>