<?php
require_once '../../includes/header.php';

// Handle Restore
if (isset($_GET['restore_id']) && isset($_GET['type'])) {
    $id = (int)$_GET['restore_id'];
    $type = $_GET['type'];
    $table = '';
    if ($type === 'room') $table = 'rooms';
    if ($type === 'announcement') $table = 'announcements';
    if ($type === 'department') $table = 'departments';
    if ($type === 'user') $table = 'users';

    if ($table) {
        $pdo->prepare("UPDATE $table SET is_deleted = 0, deleted_at = NULL WHERE id = ?")->execute([$id]);
        echo "<script>window.location.href='deletion_history.php?msg=restored';</script>";
        exit;
    }
}

// Handle Permanent Delete
if (isset($_GET['perm_delete_id']) && isset($_GET['type'])) {
    $id = (int)$_GET['perm_delete_id'];
    $type = $_GET['type'];
    $table = '';
    if ($type === 'room') $table = 'rooms';
    if ($type === 'announcement') $table = 'announcements';
    if ($type === 'department') $table = 'departments';
    if ($type === 'user') $table = 'users';

    if ($table) {
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        echo "<script>window.location.href='deletion_history.php?msg=deleted';</script>";
        exit;
    }
}

// Handle Empty Trash (Delete All)
if (isset($_POST['empty_trash'])) {
    // Delete all soft-deleted records permanently
    $pdo->exec("DELETE FROM rooms WHERE is_deleted = 1");
    $pdo->exec("DELETE FROM announcements WHERE is_deleted = 1");
    $pdo->exec("DELETE FROM departments WHERE is_deleted = 1");
    $pdo->exec("DELETE FROM users WHERE is_deleted = 1");
    
    echo "<script>window.location.href='deletion_history.php?msg=trash_emptied';</script>";
    exit;
}

// Fetch all deleted items
$history = [];
try {
    $history = $pdo->query("
        (SELECT id, 'Room' as item_type, room_no as item_name, deleted_at, 'room' as type_key FROM rooms WHERE is_deleted = 1)
        UNION ALL
        (SELECT id, 'Announcement' as item_type, title as item_name, deleted_at, 'announcement' as type_key FROM announcements WHERE is_deleted = 1)
        UNION ALL
        (SELECT id, 'Department' as item_type, department_name as item_name, deleted_at, 'department' as type_key FROM departments WHERE is_deleted = 1)
        UNION ALL
        (SELECT id, 'User' as item_type, CONCAT(name, ' (', email, ')') as item_name, deleted_at, 'user' as type_key FROM users WHERE is_deleted = 1)
        ORDER BY deleted_at DESC
    ")->fetchAll();
} catch (Exception $e) {
    $dbError = "Database Error: Please ensure all tables (users, rooms, announcements, departments) have 'is_deleted' column. <br>Details: " . $e->getMessage();
}

?>
<div class="card card-danger card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="bi bi-trash"></i> Deletion History (Trash)</h3>
        <?php if(!empty($history)): ?>
        <form method="post" onsubmit="return confirm('Are you sure? This will PERMANENTLY delete all items in the trash. This cannot be undone.');">
            <button type="submit" name="empty_trash" class="btn btn-sm btn-danger"><i class="bi bi-fire"></i> Empty Trash</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="text-muted">Items deleted from the system are moved here. You can restore them or delete them permanently.</p>
        <?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Action completed successfully.</div><?php endif; ?>
        <?php if(isset($dbError)): ?><div class="alert alert-danger"><?= $dbError ?></div><?php endif; ?>
        <table class="table table-hover">
            <thead><tr><th>Item Type</th><th>Name / Title</th><th>Date Deleted</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if(empty($history)): ?>
                    <tr><td colspan="4" class="text-center text-muted">The trash is empty.</td></tr>
                <?php else: ?>
                    <?php foreach($history as $item): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($item['item_type']) ?></span></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($item['deleted_at'])) ?></td>
                        <td>
                            <a href="?restore_id=<?= $item['id'] ?>&type=<?= $item['type_key'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Restore this item?')"><i class="bi bi-arrow-counterclockwise"></i> Restore</a>
                            <a href="?perm_delete_id=<?= $item['id'] ?>&type=<?= $item['type_key'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('This is permanent and cannot be undone. Are you sure?')"><i class="bi bi-x-octagon-fill"></i> Delete Forever</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>