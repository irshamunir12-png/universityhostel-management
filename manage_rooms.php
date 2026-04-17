<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Add Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_no = sanitize($_POST['room_no']);
    $building = sanitize($_POST['building']);
    $capacity = (int)$_POST['capacity'];
    $gender = $_POST['gender'];
    
    if ($room_no && $capacity > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_no, building, capacity, gender, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$room_no, $building, $capacity, $gender]);
            $success = "Room added successfully!";
        } catch (PDOException $e) {
            $error = "Error: Room Number might already exist.";
        }
    } else {
        $error = "Please fill all required fields correctly.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
    echo "<script>window.location.href='manage_rooms.php';</script>";
    exit;
}

// Fetch Rooms
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY building ASC, room_no ASC")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Hostel Rooms Management</h3>
        <button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="bi bi-plus-lg"></i> Add Room
        </button>
    </div>
    <div class="card-body">
        <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Room No</th>
                    <th>Building/Block</th>
                    <th>Capacity</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rooms as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['room_no']) ?></strong></td>
                    <td><?= htmlspecialchars($r['building']) ?></td>
                    <td><?= $r['capacity'] ?> Beds</td>
                    <td>
                        <?php if($r['gender'] == 'M'): ?><span class="badge text-bg-primary">Male</span>
                        <?php elseif($r['gender'] == 'F'): ?><span class="badge text-bg-danger">Female</span>
                        <?php else: ?><span class="badge text-bg-secondary">Mixed</span><?php endif; ?>
                    </td>
                    <td><?= $r['status'] == 'active' ? '<span class="text-success">Active</span>' : '<span class="text-muted">Inactive</span>' ?></td>
                    <td>
                        <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this room?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Room Number</label>
                    <input type="text" name="room_no" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Building / Block Name</label>
                    <input type="text" name="building" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Capacity (Beds)</label>
                    <input type="number" name="capacity" class="form-control" value="1" min="1" required>
                </div>
                <div class="mb-3">
                    <label>Gender Allocation</label>
                    <select name="gender" class="form-select"><option value="M">Male</option><option value="F">Female</option><option value="O">Other/Mixed</option></select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="add_room" class="btn btn-primary">Save Room</button></div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>