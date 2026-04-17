<?php
require_once '../../includes/header.php';

// Handle Allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    $user_id = (int)$_POST['user_id'];
    $room_id = (int)$_POST['room_id'];
    
    // Check if user already has a room
    $check = $pdo->prepare("SELECT id FROM room_allocations WHERE user_id = ? AND is_active = 1");
    $check->execute([$user_id]);
    
    // Check room capacity
    $capCheck = $pdo->prepare("SELECT r.capacity, (SELECT COUNT(*) FROM room_allocations ra WHERE ra.room_id = r.id AND ra.is_active = 1) as occupied FROM rooms r WHERE r.id = ?");
    $capCheck->execute([$room_id]);
    $roomData = $capCheck->fetch();

    if ($check->rowCount() > 0) {
        $error = "This user already has an active room allocation.";
    } elseif ($roomData['occupied'] >= $roomData['capacity']) {
        $error = "Room is fully occupied!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO room_allocations (user_id, room_id, start_date, is_active) VALUES (?, ?, CURDATE(), 1)");
        $stmt->execute([$user_id, $room_id]);
        $success = "Room allocated successfully.";
    }
}
<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Vacate Room (Delete Allocation)
if (isset($_GET['vacate'])) {
     = (int)['vacate'];
    ->prepare("UPDATE room_allocations SET is_active = 0, end_date = CURDATE() WHERE id = ?")->execute([]);
    header("Location: allocate_rooms.php?msg=vacated");
    exit;
}

// Handle Save Allocation
if (['REQUEST_METHOD'] === 'POST' && isset(['save_allocation'])) {
     = (int)['user_id'];
     = (int)['room_id'];
     = sanitize(['start_date']);
     = (int)['bed_no'];
     = isset(['allocation_id']) ? (int)['allocation_id'] : 0;

    try {
        if ( > 0) {
            // UPDATE existing allocation
             = ->prepare("UPDATE room_allocations SET user_id = ?, room_id = ?, start_date = ?, bed_no = ? WHERE id = ?");
            ->execute([, , , , ]);
             = "Allocation updated successfully!";
        } else {
            // Deactivate any old allocation for this student
            ->prepare("UPDATE room_allocations SET is_active = 0, end_date = CURDATE() WHERE user_id = ? AND is_active = 1")->execute([]);
            // Insert new allocation with bed number
             = ->prepare("INSERT INTO room_allocations (user_id, room_id, start_date, bed_no, is_active) VALUES (?, ?, ?, ?, 1)");
            ->execute([, , , ]);
             = "Room allocated successfully!";
        }
        header("Location: allocate_rooms.php?success_msg=" . urlencode());
        exit;
    } catch (Exception ) {
         = "Error: " . ->getMessage();
    }
}

// Fetch Active Students for Dropdown
 = ->query("SELECT id, name, registration_no FROM users WHERE role = 'student' AND is_deleted = 0 AND is_active = 1 ORDER BY name")->fetchAll();

// Fetch Real Rooms from "Manage Rooms"
 = ->query("SELECT id, room_no, building, capacity, 
    (SELECT COUNT(*) FROM room_allocations WHERE room_id = rooms.id AND is_active = 1) as occupied 
    FROM rooms WHERE is_deleted = 0 AND room_type = 'student' ORDER BY room_no")->fetchAll();

// Fetch Current Allocations for Table
 = ->query("
    SELECT ra.id, ra.user_id, ra.room_id, ra.start_date, ra.bed_no, u.name as student_name, u.registration_no, r.room_no, r.building 
    FROM room_allocations ra 
    JOIN users u ON ra.user_id = u.id 
    JOIN rooms r ON ra.room_id = r.id 
    WHERE ra.is_active = 1
    ORDER BY ra.id DESC
")->fetchAll();
?>

<style>
    /* Hide default dashboard header bar */
    .app-content-header { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .allocate-app-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-top: 10px;
        border: none;
    }
    .allocate-header-gradient {
        background: linear-gradient(to right, #2ecc71, #1abc9c);
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
    
    .title-bold { font-weight: 850; font-size: 1.7rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; }
    .btn-app-home {
        background: white;
        color: #20c997 !important;
        font-weight: bold;
        border-radius: 15px;
        padding: 5px 20px;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-app-home:hover { background: #f8f9fa; transform: scale(1.05); }

    .form-section-padding { padding: 40px; background: #fff; }
    .underline-input {
        border: none;
        border-bottom: 2px solid #ddd;
        border-radius: 0;
        padding: 10px 0;
        background: transparent;
        transition: 0.3s;
        font-weight: 500;
    }
    .underline-input:focus { box-shadow: none; outline: none; border-bottom-color: #6f42c1; }
    
    .btn-rounded-save { background-color: #198754; color: white; border-radius: 25px; padding: 10px 40px; border: none; font-weight: 600; transition: 0.3s; }
    .btn-rounded-delete { background-color: #ff8787; color: white; border-radius: 25px; padding: 10px 40px; border: none; font-weight: 600; transition: 0.3s; }
    .btn-rounded-new { background-color: #212529; color: white; border-radius: 25px; padding: 10px 40px; border: none; font-weight: 800; text-transform: uppercase; transition: 0.3s; }
    
    .btn-rounded-save:hover, .btn-rounded-delete:hover, .btn-rounded-new:hover { opacity: 0.9; transform: translateY(-2px); }

    .table-container-app { padding: 0 40px 40px; }
    .clean-grid-table { border: 1px solid #eee; }
    .clean-grid-table th { background: #fdfbff; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #eee; padding: 15px; }
    .clean-grid-table td { vertical-align: middle; border-bottom: 1px solid #f8f9fa; padding: 15px; }
</style>

<div class="allocate-app-card">
    <div class="allocate-header-gradient">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="title-bold">Allocate Room</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="form-section-padding">
        <?php if(isset()): ?><div class="alert alert-danger rounded-3 auto-hide"><?=  ?></div><?php endif; ?>
        <?php if(isset(['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars(['success_msg']) ?></div><?php endif; ?>
        
        <form method="post" id="allocationForm">
            <input type="hidden" name="allocation_id" id="edit_allocation_id" value="0">
            <div class="row g-5">
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Student</label>
                    <select name="user_id" class="form-select underline-input" required>
                        <option value="">Choose Student</option>
                        <?php foreach( as ): ?>
                            <option value="<?= ['id'] ?>"><?= htmlspecialchars(['name']) ?> (<?= ['registration_no'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Room</label>
                    <select name="room_id" class="form-select underline-input" required>
                        <option value="">Choose Room</option>
                        <?php foreach( as ): 
                             = (['occupied'] >= ['capacity']);
                        ?>
                            <option value="<?= ['id'] ?>" <?=  ? 'disabled' : '' ?>>
                                <?= htmlspecialchars(['room_no']) ?> - <?= htmlspecialchars(['building']) ?> 
                                (<?= ['occupied'] ?>/<?= ['capacity'] ?>) <?=  ? '[FULL]' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Start Date</label>
                    <input type="date" name="start_date" class="form-control underline-input" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-12">
                    <label class="text-muted small fw-bold">Bed No</label>
                    <input type="number" name="bed_no" class="form-control underline-input" placeholder="Enter Bed Number">
                </div>
            </div>

            <div class="mt-5 d-flex gap-3">
                <button type="submit" name="save_allocation" id="mainSubmitBtn" class="btn btn-rounded-save shadow-sm">Save</button>
                <button type="reset" class="btn btn-rounded-new shadow-sm">+ NEW ALLOCATION</button>
            </div>
        </form>
    </div>

    <div class="table-container-app">
        <div class="table-responsive rounded-3">
            <table class="table table-hover clean-grid-table mb-0" id="allocationTable">
                <thead>
                    <tr>
                        <th class="ps-3">Student Details</th>
                        <th>Allocated Room</th>
                        <th>Start Date</th>
                        <th>Bed No</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach( as ): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold"><?= htmlspecialchars(['student_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars(['registration_no']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars(['room_no']) ?> (<?= htmlspecialchars(['building']) ?>)</span></td>
                        <td><?= date('d M Y', strtotime(['start_date'])) ?></td>
                        <td><span class="fw-bold">Bed #<?= htmlspecialchars(['bed_no'] ?? '-') ?></span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm text-primary border-0" onclick="fillAllocationForm('<?= ['id'] ?>', '<?= ['user_id'] ?>', '<?= ['room_id'] ?>', '<?= ['start_date'] ?>', '<?= ['bed_no'] ?>')"><i class="bi bi-pencil-square"></i></button>
                            <a href="?vacate=<?= ['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Vacate room?')"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => el.style.display = 'none');
    }, 5000);

    function fillAllocationForm(id, userId, roomId, startDate, bedNo) {
        document.getElementById('edit_allocation_id').value = id;
        document.getElementsByName('user_id')[0].value = userId;
        document.getElementsByName('room_id')[0].value = roomId;
        document.getElementsByName('start_date')[0].value = startDate;
        document.getElementsByName('bed_no')[0].value = bedNo;
        document.getElementById('mainSubmitBtn').innerText = "UPDATE ALLOCATION";
    }

    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        document.getElementById('edit_allocation_id').value = "0";
        document.getElementById('mainSubmitBtn').innerText = "Save";
    });
</script>
<?php require_once '../../includes/footer.php'; ?>

// Handle De-allocation (Vacate Room)
if (isset($_GET['vacate'])) {
    $id = (int)$_GET['vacate'];
    $pdo->prepare("UPDATE room_allocations SET is_active = 0, end_date = CURDATE() WHERE id = ?")->execute([$id]);
    echo "<script>window.location.href='allocate_rooms.php';</script>";
    exit;
}

// Fetch Data for Dropdowns
$students = $pdo->query("SELECT id, name, email FROM users WHERE role = 'student' AND is_active = 1")->fetchAll();
$rooms = $pdo->query("SELECT id, room_no, building, capacity FROM rooms WHERE status = 'active'")->fetchAll();

// Fetch Current Allocations
$allocations = $pdo->query("
    SELECT ra.id, ra.start_date, u.name as student_name, u.email, r.room_no, r.building 
    FROM room_allocations ra 
    JOIN users u ON ra.user_id = u.id 
    JOIN rooms r ON ra.room_id = r.id 
    WHERE ra.is_active = 1
    ORDER BY ra.created_at DESC
")->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card card-success card-outline">
            <div class="card-header"><h3 class="card-title">Allocate Room</h3></div>
            <div class="card-body">
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label>Select Student</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['email'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Select Room</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">-- Choose Room --</option>
                            <?php foreach($rooms as $r): ?>
                                <option value="<?= $r['id'] ?>">
                                    <?= htmlspecialchars($r['room_no']) ?> - <?= htmlspecialchars($r['building']) ?> (Cap: <?= $r['capacity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="allocate" class="btn btn-success w-100">Assign Room</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Current Allocations</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Room</th>
                            <th>Assigned Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allocations as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['student_name']) ?><br><small class="text-muted"><?= $a['email'] ?></small></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($a['room_no']) ?></span> <small><?= htmlspecialchars($a['building']) ?></small></td>
                            <td><?= date('d M Y', strtotime($a['start_date'])) ?></td>
                            <td><a href="?vacate=<?= $a['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Vacate this room?')">Vacate</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>