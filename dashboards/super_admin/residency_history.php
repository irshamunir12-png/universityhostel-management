<?php
require_once '../../includes/header.php';

// Fetch all past/inactive/deallocated allocations
$past_allocations = $pdo->query("
    SELECT 
        ra.id, ra.start_date, ra.end_date,
        u.name as student_name, u.registration_no,
        r.room_no, r.building
    FROM room_allocations ra
    JOIN users u ON ra.user_id = u.id
    JOIN rooms r ON ra.room_id = r.id
    WHERE ra.is_active = 0 
    ORDER BY ra.end_date DESC, ra.deleted_at DESC
")->fetchAll();

?>

<div class="card card-info card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-archive-fill"></i> Student Residency History (Alumni)</h3>
    </div>
    <div class="card-body p-0">
        <div class="p-3 text-muted bg-light border-bottom">This page shows a record of all students who have checked out or have been deallocated from their rooms.</div>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Room</th>
                    <th>Stay Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($past_allocations)): ?>
                    <tr><td colspan="4" class="text-center text-muted p-4">No past residency records found.</td></tr>
                <?php else: ?>
                    <?php foreach($past_allocations as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['student_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($a['registration_no']) ?></small></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($a['building'] . ' - ' . $a['room_no']) ?></span></td>
                        <td><?= date('d M Y', strtotime($a['start_date'])) ?> to <?= $a['end_date'] ? date('d M Y', strtotime($a['end_date'])) : 'N/A' ?></td>
                        <td><span class="badge bg-dark">Checked Out</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>