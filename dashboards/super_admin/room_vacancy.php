<?php
require_once '../../includes/header.php';

// Fetch rooms with occupancy
$rooms = $pdo->query('SELECT r.*, IFNULL(cnt.cnt,0) as occupancy FROM rooms r LEFT JOIN (SELECT room_id, COUNT(*) as cnt FROM room_allocations WHERE is_active = 1 GROUP BY room_id) cnt ON cnt.room_id = r.id ORDER BY r.building, r.block, r.room_no')->fetchAll();
?>

<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">Room Vacancy</h3>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <thead><tr><th>ID</th><th>Room</th><th>Capacity</th><th>Occupancy</th><th>Vacancy</th></tr></thead>
            <tbody>
                <?php foreach($rooms as $r):
                    $vac = max(0, $r['capacity'] - $r['occupancy']);
                ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['building'] . ' ' . $r['block'] . ' - ' . $r['room_no']) ?></td>
                    <td><?= $r['capacity'] ?></td>
                    <td><?= $r['occupancy'] ?></td>
                    <td><?= $vac ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>