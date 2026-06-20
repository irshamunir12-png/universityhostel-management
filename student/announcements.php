<?php
require_once '../includes/header.php';

// Fetch active announcements
// Pinned first, then by date. Exclude expired ones.
$stmt = $pdo->prepare(
    "SELECT a.*, u.name as author_name
     FROM announcements a 
     LEFT JOIN users u ON a.user_id = u.id 
     WHERE (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
     AND a.is_deleted = 0
     ORDER BY a.is_pinned DESC, a.created_at DESC"
);
$stmt->execute();
$announcements = $stmt->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title"><i class="bi bi-megaphone-fill"></i> Digital Notice Board</h3></div>
    <div class="card-body">
        <?php if(!$announcements): ?>
            <div class="alert alert-info">No announcements at the moment.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach($announcements as $item): ?>
                    <div class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php if($item['is_pinned']): ?><i class="bi bi-pin-angle-fill text-primary" title="Pinned Notice"></i><?php endif; ?> <?= htmlspecialchars($item['title']) ?></h5>
                            <small class="text-muted"><?= date('d M Y', strtotime($item['created_at'])) ?></small>
                        </div>
                        <p class="mb-1"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                        <small class="text-muted">Posted by: <?= htmlspecialchars($item['author_name'] ?? 'Admin') ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>