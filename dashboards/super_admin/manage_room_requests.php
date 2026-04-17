<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $remarks = sanitize($_POST['remarks']);

    if ($action === 'reject') {
        $pdo->prepare("UPDATE room_change_requests SET status = 'rejected', admin_remarks = ? WHERE id = ?")->execute([$remarks, $req_id]);
        $success = "Request rejected.";
    } elseif ($action === 'approve') {
        $new_room_id = (int)$_POST['new_room_id'];
        $user_id = (int)$_POST['user_id'];
        
        // 1. Check Capacity of New Room
        $cap = $pdo->prepare("SELECT capacity, (SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND is_active = 1) as occupied FROM rooms WHERE id = ?");
        $cap->execute([$new_room_id, $new_room_id]);
        $roomData = $cap->fetch();

        if ($roomData['occupied'] >= $roomData['capacity']) {
            $error = "Selected room is fully occupied!";
        } else {
            $pdo->beginTransaction();
            try {
                // 2. Deactivate Old Room
                $pdo->prepare("UPDATE room_allocations SET is_active = 0, end_date = CURDATE() WHERE user_id = ? AND is_active = 1")->execute([$user_id]);
                
                // 3. Allocate New Room
                $pdo->prepare("INSERT INTO room_allocations (user_id, room_id, start_date, is_active) VALUES (?, ?, CURDATE(), 1)")->execute([$user_id, $new_room_id]);
                
                // 4. Update Request Status
                $pdo->prepare("UPDATE room_change_requests SET status = 'approved', admin_remarks = ? WHERE id = ?")->execute([$remarks, $req_id]);
                
                $pdo->commit();
                $success = "Request approved & student shifted successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error shifting room: " . $e->getMessage();
            }
        }
    }
}

// Fetch Pending Requests
$requests = $pdo->query("
    SELECT rcr.*, u.name, u.registration_no, r.room_no as current_room, r.building
    FROM room_change_requests rcr
    JOIN users u ON rcr.user_id = u.id
    LEFT JOIN rooms r ON rcr.current_room_id = r.id
    WHERE rcr.status = 'pending'
    ORDER BY rcr.created_at ASC
")->fetchAll();

// Fetch Vacant Rooms for Dropdown
$vacantRooms = $pdo->query("
    SELECT r.id, r.room_no, r.building, r.capacity, 
    (SELECT COUNT(*) FROM room_allocations ra WHERE ra.room_id = r.id AND ra.is_active = 1) as occupied
    FROM rooms r 
    WHERE r.status = 'active' AND r.is_deleted = 0
    HAVING occupied < capacity
    ORDER BY r.building, r.room_no
")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title"><i class="bi bi-arrow-repeat"></i> Room Change Requests</h3></div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?><div class="alert alert-success m-3"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-danger m-3"><?= $error ?></div><?php endif; ?>

        <table class="table table-striped table-hover">
            <thead><tr><th>Student</th><th>Current Room</th><th>Reason</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($requests as $req): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($req['name']) ?></strong><br>
                        <small><?= htmlspecialchars($req['registration_no']) ?></small>
                    </td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($req['building'] . ' - ' . $req['current_room']) ?></span></td>
                    <td>
                        <span class="text-danger fw-bold"><?= htmlspecialchars($req['reason_category']) ?></span>
                        <p class="small mb-0 text-muted"><?= htmlspecialchars($req['description']) ?></p>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $req['id'] ?>">Approve & Shift</button>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $req['id'] ?>">Reject</button>
                    </td>
                </tr>

                <!-- Approve Modal -->
                <div class="modal fade" id="approveModal<?= $req['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="post" class="modal-content">
                            <div class="modal-header"><h5 class="modal-title">Approve Shifting</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $req['user_id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <p>Select new room for <strong><?= htmlspecialchars($req['name']) ?></strong>:</p>
                                <div class="mb-3">
                                    <label>New Room</label>
                                    <select name="new_room_id" class="form-select" required>
                                        <?php foreach($vacantRooms as $vr): ?>
                                            <option value="<?= $vr['id'] ?>"><?= htmlspecialchars($vr['building'] . ' - ' . $vr['room_no']) ?> (<?= $vr['occupied'] ?>/<?= $vr['capacity'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3"><label>Remarks</label><input type="text" name="remarks" class="form-control" value="Request Approved"></div>
                            </div>
                            <div class="modal-footer"><button type="submit" class="btn btn-success">Confirm Shift</button></div>
                        </form>
                    </div>
                </div>

                <!-- Reject Modal -->
                <div class="modal fade" id="rejectModal<?= $req['id'] ?>" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Reject Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="request_id" value="<?= $req['id'] ?>"><input type="hidden" name="action" value="reject"><div class="mb-3"><label>Reason for Rejection</label><textarea name="remarks" class="form-control" required></textarea></div></div><div class="modal-footer"><button type="submit" class="btn btn-danger">Reject</button></div></form></div></div>
                
                <?php endforeach; ?>
                <?php if(empty($requests)): ?><tr><td colspan="4" class="text-center text-muted p-4">No pending room change requests.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>