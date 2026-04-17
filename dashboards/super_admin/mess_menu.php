<?php
require_once '../includes/header.php';

// Fetch Menu Data and format it
$stmt = $pdo->query("SELECT * FROM mess_menu");
$allMenu = $stmt->fetchAll(PDO::FETCH_ASSOC);
$menuData = [];
foreach($allMenu as $row) {
    $menuData[$row['day_of_week']] = $row;
}

$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-calendar-week"></i> Weekly Mess Menu</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach($weekDays as $day): 
                $menu = $menuData[$day] ?? ['breakfast'=>'[]', 'lunch'=>'[]', 'dinner'=>'[]'];
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light fw-bold text-center"><?= $day ?></div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <h6 class="text-muted mb-2">Breakfast</h6>
                            <?php 
                                $items = json_decode($menu['breakfast'], true);
                                if (empty($items)) {
                                    echo '<span class="text-muted small fst-italic">Not Set</span>';
                                } else {
                                    foreach($items as $item) {
                                        echo '<span class="badge bg-secondary me-1 mb-1">' . htmlspecialchars($item['value']) . '</span> ';
                                    }
                                }
                            ?>
                        </li>
                        <li class="list-group-item">
                            <h6 class="text-muted mb-2">Lunch</h6>
                            <?php 
                                $items = json_decode($menu['lunch'], true);
                                if (empty($items)) {
                                    echo '<span class="text-muted small fst-italic">Not Set</span>';
                                } else {
                                    foreach($items as $item) {
                                        echo '<span class="badge bg-info text-dark me-1 mb-1">' . htmlspecialchars($item['value']) . '</span> ';
                                    }
                                }
                            ?>
                        </li>
                        <li class="list-group-item">
                            <h6 class="text-muted mb-2">Dinner</h6>
                            <?php 
                                $items = json_decode($menu['dinner'], true);
                                if (empty($items)) {
                                    echo '<span class="text-muted small fst-italic">Not Set</span>';
                                } else {
                                    foreach($items as $item) {
                                        echo '<span class="badge bg-dark me-1 mb-1">' . htmlspecialchars($item['value']) . '</span> ';
                                    }
                                }
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>