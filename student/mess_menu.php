<?php
require_once '../includes/header.php';
require_once '../core/session.php';

// --- DATABASE REPAIR: Ensure required tables exist ---
$pdo->exec("CREATE TABLE IF NOT EXISTS `mess_special_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `available_date` date DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS `mess_special_orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `item_id` int(11) NOT NULL,
    `ordered_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$user_id = $_SESSION['user_id'];

// Handle Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_item'])) {
    $item_id = (int)$_POST['item_id'];
    $price = (float)$_POST['price'];
    $title = $_POST['title'];
    $date = $_POST['date'];

    // 1. Record Order
    $pdo->prepare("INSERT INTO mess_special_orders (user_id, item_id) VALUES (?, ?)")->execute([$user_id, $item_id]);

    // 2. Add to Fees (Automated Billing)
    $fee_title = "Special Meal: " . $title . " (" . date('d M', strtotime($date)) . ")";
    $pdo->prepare("INSERT INTO student_fees (user_id, title, amount, due_date, status) VALUES (?, ?, ?, ?, 'pending')")
        ->execute([$user_id, $fee_title, $price, $date]);

    $success = "Order placed! Rs. $price added to your bill.";
}

$menu = [];
try {
    $menu = $pdo->query("SELECT * FROM mess_menu ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
    // Table might not exist
}

// Fetch Upcoming Specials
$specials = $pdo->query("SELECT * FROM mess_special_items WHERE available_date >= CURDATE() ORDER BY available_date ASC")->fetchAll();

// Fetch My Orders (to prevent double ordering)
$my_orders = $pdo->prepare("SELECT item_id FROM mess_special_orders WHERE user_id = ?");
$my_orders->execute([$user_id]);
$ordered_ids = $my_orders->fetchAll(PDO::FETCH_COLUMN);

// Helper to display tags (decodes JSON from Tagify)
function showTags($json) {
    if (empty($json)) return '<span class="text-muted small">-</span>';

    // 1. Try normal decode
    $arr = json_decode($json, true);
    
    // 2. If failed, try decoding HTML entities (Fix for &quot; issue)
    if (!is_array($arr)) {
        $arr = json_decode(html_entity_decode($json), true);
    }

    // 3. If still failed, show text
    if (!is_array($arr)) return htmlspecialchars($json);

    if (empty($arr)) return '<span class="text-muted small">-</span>';
    
    $out = '';
    foreach($arr as $a) {
        $val = isset($a['value']) ? $a['value'] : '';
        $out .= '<span class="badge bg-secondary me-1 mb-1">'.htmlspecialchars($val).'</span> ';
    }
    return $out;
}
?>

<?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<h3 class="mb-3 text-white" style="text-shadow: 1px 1px 2px #000;"><i class="bi bi-egg-fried"></i> Weekly Mess Menu</h3>
<table class="table table-striped table-hover table-bordered" style="background-color: rgba(255,255,255,0.95);">
    <thead class="table-light"><tr><th>Day</th><th>Breakfast</th><th>Lunch</th><th>Dinner</th></tr></thead>
    <tbody>
        <?php foreach($menu as $m): ?>
        <!-- Highlight Today -->
        <tr class="<?= strtolower($m['day_of_week']) == strtolower(date('l')) ? 'table-warning border-start border-5 border-warning' : '' ?>">
            <td class="fw-bold"><?= $m['day_of_week'] ?></td>
            <td><?= showTags($m['breakfast']) ?></td>
            <td><?= showTags($m['lunch']) ?></td>
            <td><?= showTags($m['dinner']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Special Menu Section -->
<?php if($specials): ?>
<div class="card card-outline card-danger mt-4">
    <div class="card-header"><h3 class="card-title"><i class="bi bi-fire"></i> Special Menu Offers</h3></div>
    <div class="card-body">
        <div class="row">
            <?php foreach($specials as $s): 
                $is_ordered = in_array($s['id'], $ordered_ids);
            ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-danger">
                    <div class="card-header bg-danger text-white text-center">
                        <h5 class="mb-0"><?= date('l, d M', strtotime($s['available_date'])) ?></h5>
                    </div>
                    <div class="card-body text-center">
                        <h4><?= htmlspecialchars($s['title']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($s['description']) ?></p>
                        <h3 class="text-success">Rs. <?= number_format($s['price']) ?></h3>
                        
                        <?php if($is_ordered): ?>
                            <button class="btn btn-secondary w-100" disabled><i class="bi bi-check-circle"></i> Ordered</button>
                            <small class="text-muted d-block mt-2">Added to your bill</small>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="price" value="<?= $s['price'] ?>">
                                <input type="hidden" name="title" value="<?= htmlspecialchars($s['title']) ?>">
                                <input type="hidden" name="date" value="<?= $s['available_date'] ?>">
                                <button type="submit" name="order_item" class="btn btn-outline-danger w-100" onclick="return confirm('Confirm Order? Rs. <?= $s['price'] ?> will be added to your fees.')">Pre-Order Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>