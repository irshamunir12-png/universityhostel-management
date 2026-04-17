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
    global $current_url; // Defined in header.php
    return ($url == $current_url) ? 'active' : '';
}

// Helper to check if any child is active (to open the dropdown)
function isTreeOpen($children) {
    global $current_url;
    foreach ($children as $child) {
        if ($child['page_url'] == $current_url) return 'menu-open';
    }
    return '';
}
?>

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>" class="brand-link text-decoration-none">
            <span class="brand-text fw-bold fs-4 text-white">
                <i class="bi bi-house-door-fill me-2"></i> University <span class="fw-light text-light">Hostel MNG</span>
            </span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                
                <!-- Sidebar Collapse Toggle (Desktop only) -->
                <li class="nav-item d-none d-lg-block border-bottom mb-3 pb-2">
                    <a href="#" class="nav-link" data-lte-toggle="sidebar">
                        <i class="nav-icon bi bi-list"></i>
                        <p>Minimize Menu</p>
                    </a>
                </li>
                
                <?php foreach ($menu as $item): ?>
                    <?php if (empty($item['children'])): ?>
                        <!-- Single Item (Like Dashboard) -->
                        <li class="nav-item">
                            <a href="<?= BASE_URL . $item['page_url'] ?>" class="nav-link <?= isActive($item['page_url']) ?>">
                                <i class="nav-icon <?= $item['icon_class'] ?>"></i>
                                <p><?= htmlspecialchars($item['page_name']) ?></p>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Dropdown Group -->
                        <li class="nav-item <?= isTreeOpen($item['children']) ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon <?= $item['icon_class'] ?>"></i>
                                <p><?= htmlspecialchars($item['page_name']) ?> <i class="nav-arrow bi bi-chevron-right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL . $child['page_url'] ?>" class="nav-link <?= isActive($child['page_url']) ?>">
                                            <i class="nav-icon bi bi-circle"></i>
                                            <p><?= htmlspecialchars($child['page_name']) ?></p>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>

            </ul>
        </nav>
    </div>
</aside>