<?php
require_once '../../core/db.php';
require_once '../../core/functions.php';
require_once '../../core/session.php';

// Handle Create Room before any output to allow header redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $building = sanitize($_POST['building'] ?? 'Main');
    $block = sanitize($_POST['block'] ?? 'A');
    $room_no = sanitize($_POST['room_id'] ?? '');
    $type = sanitize($_POST['room_type'] ?? 'student');
    $capacity = (int)($_POST['qty'] ?? 1);
    $gender = sanitize($_POST['gender'] ?? 'Any');
    $washroom_type = sanitize($_POST['washroom_type'] ?? 'common');
    $key_money = sanitize($_POST['key_money'] ?? '0');
    $notes = "Key Money: " . $key_money;

    $stmt = $pdo->prepare('INSERT INTO rooms (building, block, room_no, room_type, capacity, gender, washroom_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    try {
        $stmt->execute([$building, $block, $room_no, $type, $capacity, $gender, $washroom_type, $notes]);
        header('Location: manage_rooms.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Could not create room: ' . $e->getMessage();
    }
}

// Handle Update Room (Edit Feature)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $id = (int)$_POST['room_id'];
    $building = sanitize($_POST['building'] ?? 'Main');
    $block = sanitize($_POST['block'] ?? 'A');
    $room_no = sanitize($_POST['room_id_input']); // Separate name to avoid conflict with ID
    $type = sanitize($_POST['room_type']);
    $capacity = (int)$_POST['qty'];
    $gender = sanitize($_POST['gender']);
    $washroom_type = sanitize($_POST['washroom_type']);
    $key_money = sanitize($_POST['key_money'] ?? '0');
    $notes = "Key Money: " . $key_money;
    
    $pdo->prepare("UPDATE rooms SET building=?, block=?, room_no=?, room_type=?, capacity=?, gender=?, notes=?, washroom_type=? WHERE id=?")->execute([$building, $block, $room_no, $type, $capacity, $gender, $notes, $washroom_type, $id]);
    header('Location: manage_rooms.php?msg=updated');
    exit;
}

// Handle Delete Room before output as well
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if room has active allocations
    $c = $pdo->prepare('SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND is_active = 1');
    $c->execute([$id]);
    if ($c->fetchColumn() > 0) {
        $error = 'Cannot delete room with active allocations.';
    } else {
        // Soft Delete: Mark the room as deleted instead of removing it from DB
        $stmt = $pdo->prepare("UPDATE rooms SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: manage_rooms.php?msg=deleted');
        exit;
    }
}

// Now include header and fetch rooms for display
require_once '../../includes/header.php';
$rooms = $pdo->query('SELECT * FROM rooms WHERE is_deleted = 0 ORDER BY building, block, room_no')->fetchAll();
?>

<style>
    .room-manage-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: none;
        margin-top: 10px;
    }
    .card-header-custom {
        background: linear-gradient(to right, #2ecc71, #1abc9c);
 !important;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
    }
    .window-controls { display: flex; gap: 8px; }
    .win-dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background: #ff5f56; }
    .dot-y { background: #ffbd2e; }
    .dot-g { background: #009a17; }

    .header-title-purple {
        color: #f7f6fa;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 1.4rem;
        margin: 0;
    }
    .btn-home-white {
        background: white;
        color: var(--hostel-green) !important;
        font-weight: bold;
        border-radius: 20px;
        padding: 5px 20px;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-home-white:hover { background: #f1f1f1; transform: scale(1.05); }
    
    .form-section { padding: 30px; background: #fff; }
    .modern-input { 
        border: none !important; 
        border-bottom: 2px solid #e0e0e0 !important; 
        border-radius: 0 !important; 
        padding: 10px 5px !important; 
        background-color: transparent !important; 
        transition: border-color 0.4s ease !important;
        box-shadow: none !important;
        appearance: none; /* Removes default arrows in some browsers */
    }
    .modern-input:focus { 
        outline: none !important;
        background-color: transparent !important;
    }
    [name="room_id"]:focus { border-bottom-color: #0d6efd; } /* Blue */
    [name="room_type"]:focus { border-bottom-color: #198754; } /* Green */
    [name="key_money"]:focus { border-bottom-color: #dc3545; } /* Red */
    [name="qty"]:focus { border-bottom-color: #fd7e14; } /* Orange */
    [name="gender"]:focus { border-bottom-color: #0d6efd; } /* Blue */
    [name="washroom_type"]:focus { border-bottom-color: #198653; } /* Green */
    
    .btn-save { background-color: #198754; color: white; border-radius: 25px; padding: 10px 35px; border: none; font-weight: 600; }
    .btn-delete { background-color: #ff6b6b; color: white; border-radius: 25px; padding: 10px 35px; border: none; font-weight: 600; }
    .btn-new-room { background-color: #0d47a1; color: white; border-radius: 25px; padding: 10px 35px; border: none; font-weight: 600; }
    
    .table-container-scroll {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #eee;
        border-radius: 10px;
        margin: 0 30px 30px;
    }
    .modern-table thead { position: sticky; top: 0; background: #f8f9fa; z-index: 10; }
    .modern-table th { font-weight: 700; color: #555; text-transform: uppercase; font-size: 0.85rem; border-bottom: 2px solid #eee; }
</style>

<div class="room-manage-card">
    <!-- Header Section -->
    <div class="card-header-custom">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="header-title-purple">Manage Room</h2>
        <a href="<?= BASE_URL ?>" class="btn-home-white shadow-sm"><i class="bi bi-house-door-fill me-1"></i> Home</a>
    </div>

    <!-- Form Section -->
    <div class="form-section">
        <?php if(isset($error)): ?><div class="alert alert-danger rounded-3"><?= $error ?></div><?php endif; ?>
        <form method="post" id="roomForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold text-muted small">Room ID</label>
                    <input type="text" name="room_id" class="form-control modern-input" placeholder="e.g. R-101" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-muted small">Room Type</label>
                    <select name="room_type" class="form-select modern-input" required>
                        <option value="student">Student Room</option>
                        <option value="staff">Staff Room</option>
                        <option value="office">Office / Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-muted small">Key Money</label>
                    <select name="key_money" class="form-select modern-input">
                        <option value="0">Rs. 0 (None)</option>
                        <option value="2000">Rs. 2,000</option>
                        <option value="5000">Rs. 5,000</option>
                        <option value="10000">Rs. 10,000</option>
                        <option value="15000">Rs. 15,000</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-muted small">Capacity (Beds)</label>
                    <input type="text" name="qty" class="form-control modern-input" placeholder="e.g. 4">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-muted small">Gender</label>
                    <select name="gender" class="form-select modern-input">
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                        <option value="Any">Any</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-muted small">Washroom</label>
                    <select name="washroom_type" class="form-select modern-input">
                        <option value="common">Common</option>
                        <option value="attached">Attached</option>
                    </select>
                </div>
            </div>
            
            <!-- Buttons Section -->
            <div class="row mt-4 pt-2">
                <div class="col-12 text-center">
                    <button type="submit" name="create_room" class="btn-save shadow-sm me-2">Save</button>
                    <button type="button" class="btn-delete shadow-sm me-2" onclick="handleDelete()">Delete</button>
                    <button type="reset" class="btn-new-room shadow-sm">+ NEW ROOM</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="table-container-scroll shadow-sm">
        <table class="table table-hover modern-table mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Room ID</th>
                    <th>Room Type</th>
                    <th>Key Money</th>
                    <th>Capacity</th>
                    <th>Washroom</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rooms as $r): 
                    // Extracting key money from notes if stored there
                    $km = str_replace('Key Money: ', '', $r['notes'] ?? '0');
                    // Fetch real occupancy
                    $occ = $pdo->prepare('SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND is_active = 1');
                    $occ->execute([$r['id']]);
                    $occupancy = $occ->fetchColumn();
                ?>
                <tr onclick="fillForm('<?= $r['room_no'] ?>', '<?= $r['room_type'] ?>', '<?= $km ?>', '<?= $r['capacity'] ?>', '<?= $r['gender'] ?>', '<?= $r['washroom_type'] ?>')" style="cursor:pointer;">
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($r['room_no']) ?></td>
                    <td><span class="badge rounded-pill bg-light text-dark border"><?= ucfirst($r['room_type']) ?></span></td>
                    <td>Rs. <?= number_format((float)$km) ?></td>
                    <td><small class="fw-bold <?= $occupancy >= $r['capacity'] ? 'text-danger' : 'text-success' ?>"><?= $occupancy ?> / <?= $r['capacity'] ?></small> Beds</td>
                    <td><i class="bi <?= $r['washroom_type'] == 'attached' ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' ?>"></i> <?= ucfirst($r['washroom_type']) ?></td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="allocate_rooms.php?room_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary border-0" title="Accommodate"><i class="bi bi-person-plus-fill"></i></a>
                            <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Delete room?')" title="Delete"><i class="bi bi-trash3-fill"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($rooms)): ?>
                    <tr><td colspan="5" class="text-center p-4 text-muted">No rooms available. Create a new one above.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Fill form when clicking table row
    function fillForm(id, type, km, qty, gender, washroom) {
        document.getElementsByName('room_id')[0].value = id;
        document.getElementsByName('room_type')[0].value = type;
        document.getElementsByName('key_money')[0].value = km;
        document.getElementsByName('qty')[0].value = qty;
        document.getElementsByName('gender')[0].value = gender;
        document.getElementsByName('washroom_type')[0].value = washroom;
    }

    function handleDelete() {
        const id = document.getElementsByName('room_id')[0].value;
        if(id) {
            if(confirm('Are you sure you want to delete Room ' + id + '?')) {
                // Since we need ID, usually we'd search the rooms array or handle via AJAX
                alert('Please use the trash icon in the table to delete specific records.');
            }
        } else {
            alert('Please select a room from the table first.');
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>