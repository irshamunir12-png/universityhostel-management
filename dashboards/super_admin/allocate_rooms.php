<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Vacate Room (Delete Allocation)
if (isset($_GET['vacate'])) {
    $id = (int)$_GET['vacate'];
    $pdo->prepare("UPDATE room_allocations SET is_active = 0, end_date = CURDATE() WHERE id = ?")->execute([$id]);
    header("Location: allocate_rooms.php?msg=vacated");
    exit;
}

// Handle Save Allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_allocation'])) {
    $user_id = (int)$_POST['user_id'];
    $room_id = (int)$_POST['room_id'];
    $start_date = sanitize($_POST['start_date']);
    $bed_no = (int)$_POST['bed_no'];
    $alloc_id = isset($_POST['allocation_id']) ? (int)$_POST['allocation_id'] : 0;

    try {
        if ($alloc_id > 0) {
            // UPDATE existing allocation
            $stmt = $pdo->prepare("UPDATE room_allocations SET user_id = ?, room_id = ?, start_date = ?, bed_no = ? WHERE id = ?");
            $stmt->execute([$user_id, $room_id, $start_date, $bed_no, $alloc_id]);
            $msg = "Allocation updated successfully!";
        } else {
            // Deactivate any old allocation for this student
            $pdo->prepare("UPDATE room_allocations SET is_active = 0, end_date = CURDATE() WHERE user_id = ? AND is_active = 1")->execute([$user_id]);
            // Insert new allocation with bed number
            $stmt = $pdo->prepare("INSERT INTO room_allocations (user_id, room_id, start_date, bed_no, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$user_id, $room_id, $start_date, $bed_no]);
            $msg = "Room allocated successfully!";
        }
        header("Location: allocate_rooms.php?success_msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Active Students for Dropdown
$students_list = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' AND is_deleted = 0 AND is_active = 1 ORDER BY name")->fetchAll();

// Fetch Real Rooms from "Manage Rooms"
$rooms_list = $pdo->query("SELECT id, room_no, building, capacity, 
    (SELECT COUNT(*) FROM room_allocations WHERE room_id = rooms.id AND is_active = 1) as occupied 
    FROM rooms WHERE is_deleted = 0 AND room_type = 'student' ORDER BY room_no")->fetchAll();

// Fetch Current Allocations for Table
$allocations = $pdo->query("
    SELECT ra.id, ra.user_id, ra.room_id, ra.start_date, ra.bed_no, u.name as student_name, u.registration_no, r.room_no, r.building 
    FROM room_allocations ra 
    JOIN users u ON ra.user_id = u.id 
    JOIN rooms r ON ra.room_id = r.id 
    WHERE ra.is_active = 1
    ORDER BY ra.id DESC
")->fetchAll();
?>

<style>
    /* Hide default dashboard header bar (the black text one) */
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
        background: linear-gradient(to right, #2ecc71, #1abc9c) !important;
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
    .underline-input:focus { box-shadow: none; outline: none; }
    
    /* Highlight colors on focus */
    .student-field:focus { border-bottom-color: #0d6efd; } /* Blue */
    .room-field:focus { border-bottom-color: #198754; }    /* Green */
    .date-field:focus { border-bottom-color: #dc3545; }    /* Red */
    .bed-field:focus { border-bottom-color: #fd7e14; }     /* Orange */

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
    <!-- Top Header Bar -->
    <div class="allocate-header-gradient">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="title-bold">Allocate Room</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <!-- Form Section -->
    <div class="form-section-padding">
        <?php if(isset($error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $error ?></div><?php endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'vacated'): ?><div class="alert alert-info rounded-3 auto-hide">Student has vacated the room.</div><?php endif; ?>
        <?php if(isset($_GET['success_msg'])): ?><div class="alert alert-success rounded-3 auto-hide"><?= htmlspecialchars($_GET['success_msg']) ?></div><?php endif; ?>
        
        <form method="post" id="allocationForm">
            <input type="hidden" name="allocation_id" id="edit_allocation_id" value="0">
            <div class="row g-5">
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Student</label>
                    <select name="user_id" class="form-select underline-input student-field" required>
                        <option value="">Choose Student</option>
                        <?php foreach($students_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['registration_no'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Room</label>
                    <select name="room_id" class="form-select underline-input room-field" required>
                        <option value="">Choose Room</option>
                        <?php foreach($rooms_list as $rm): 
                            $is_full = ($rm['occupied'] >= $rm['capacity']);
                        ?>
                            <option value="<?= $rm['id'] ?>" <?= $is_full ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($rm['room_no']) ?> - <?= htmlspecialchars($rm['building']) ?> 
                                (<?= $rm['occupied'] ?>/<?= $rm['capacity'] ?>) <?= $is_full ? '[FULL]' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small fw-bold">Start Date</label>
                    <input type="date" name="start_date" class="form-control underline-input date-field" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-12">
                    <label class="text-muted small fw-bold">Bed</label>
                    <input type="number" name="bed_no" class="form-control underline-input bed-field" placeholder="Enter Bed Number">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-5 d-flex gap-3">
                <button type="submit" name="save_allocation" id="mainSubmitBtn" class="btn btn-rounded-save shadow-sm">Save</button>
                <button type="button" class="btn btn-rounded-delete shadow-sm" onclick="return confirm('Delete this selection?')">Delete</button>
                <button type="reset" class="btn btn-rounded-new shadow-sm">+ NEW ALLOCATION</button>
            </div>
        </form>
    </div>

    <!-- Allocation Table Section -->
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
                    <?php foreach($allocations as $row): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold"><?= htmlspecialchars($row['student_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['registration_no']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['room_no']) ?> (<?= htmlspecialchars($row['building']) ?>)</span></td>
                        <td><?= date('d M Y', strtotime($row['start_date'])) ?></td>
                        <td><span class="fw-bold">Bed #<?= htmlspecialchars($row['bed_no'] ?? '-') ?></span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm text-primary border-0" onclick="fillAllocationForm('<?= $row['id'] ?>', '<?= $row['user_id'] ?>', '<?= $row['room_id'] ?>', '<?= $row['start_date'] ?>', '<?= $row['bed_no'] ?>')"><i class="bi bi-pencil-square"></i></button>
                            <a href="?vacate=<?= $row['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Vacate room?')"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Auto-hide alerts after 5 seconds to keep UI clean
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => {
            el.style.display = 'none';
        });
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