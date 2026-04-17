<?php
require_once '../includes/header.php';
require_once '../core/session.php';

// Ensure logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../login.php');
    exit;
}

// Auto-create table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS room_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    bed_no VARCHAR(50),
    start_date DATE,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB;");

$allocs = $pdo->prepare('SELECT ra.*, r.room_no, r.building, r.block FROM room_allocations ra JOIN rooms r ON r.id = ra.room_id WHERE ra.user_id = ? AND ra.is_active = 1');
$allocs->execute([$user_id]);
$allocs = $allocs->fetchAll();
?>

<div class="card card-primary card-outline mb-4">
    <div class="card-header"><h3 class="card-title">My Room</h3></div>
    <div class="card-body">
        <?php if(!$allocs): ?>
            <div class="alert alert-info">You currently have no active room allocation.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead><tr><th>Room</th><th>Bed</th><th>From</th><th>To</th></tr></thead>
                <tbody>
                <?php foreach($allocs as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['building'] . ' ' . $a['block'] . ' - ' . $a['room_no']) ?></td>
                        <td><?= htmlspecialchars($a['bed_no'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($a['start_date'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($a['end_date'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>