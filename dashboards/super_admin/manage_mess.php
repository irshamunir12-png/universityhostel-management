<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Add Tagify CSS for this page
echo '<link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />';

// Handle Mess Item Management (from Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food_item'])) {
    $itemName = sanitize($_POST['item_name']);
    $itemCat = sanitize($_POST['category']);
    if(!empty($itemName)) {
        $pdo->prepare("INSERT IGNORE INTO mess_items (item_name, category) VALUES (?, ?)")->execute([$itemName, $itemCat]);
        echo "<script>window.location.href='manage_mess.php';</script>";
        exit;
    }
}
if (isset($_GET['delete_item'])) {
    $itemId = (int)$_GET['delete_item'];
    $pdo->prepare("DELETE FROM mess_items WHERE id = ?")->execute([$itemId]);
    echo "<script>window.location.href='manage_mess.php';</script>";
    exit;
}

// Handle Form Submission (Update Single Day)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_menu'])) {
    $day = $_POST['day_of_week'];
    // Don't sanitize JSON strings, it breaks the format. PDO handles SQL safety.
    $breakfast = $_POST['breakfast'];
    $lunch = $_POST['lunch'];
    $dinner = $_POST['dinner'];

    // Update DB for the selected day
    $stmt = $pdo->prepare("UPDATE mess_menu SET breakfast = ?, lunch = ?, dinner = ? WHERE day_of_week = ?");
    $stmt->execute([$breakfast, $lunch, $dinner, $day]);
    
    $success = "Menu for <strong>$day</strong> has been updated successfully!";
}

// Fetch Existing Menu from Database
$menuRows = $pdo->query("SELECT * FROM mess_menu")->fetchAll(PDO::FETCH_ASSOC);
$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Fetch food items for whitelist
$foodItems = $pdo->query("SELECT item_name as value FROM mess_items ORDER BY value ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch full food items for modal list
$allFoodItems = $pdo->query("SELECT * FROM mess_items ORDER BY category, item_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Prepare Data Array for JS and Display
$menuData = [];
foreach($weekDays as $day) $menuData[$day] = ['breakfast'=>'', 'lunch'=>'', 'dinner'=>'']; // Default
foreach($menuRows as $row) {
    $menuData[$row['day_of_week']] = $row;
}

// Helper to display tags in table
function showTags($json) {
    if (empty($json)) return '<span class="text-muted small">-</span>';

    // 1. Try normal decode
    $arr = json_decode($json, true);
    // 2. If failed, try decoding HTML entities (Fix for &quot; issue)
    if (!is_array($arr)) {
        $arr = json_decode(html_entity_decode($json), true);
    }
    if (!is_array($arr)) return htmlspecialchars($json); // Show text if still not JSON

    if(empty($arr)) return '<span class="text-muted small">-</span>';
    $out = '';
    foreach($arr as $a) $out .= '<span class="badge bg-secondary me-1 mb-1">'.htmlspecialchars($a['value']).'</span> ';
    return $out;
}
?>
<style>
    /* Hide default dashboard header entirely */
    .app-content-header, .content-header, .breadcrumb { display: none !important; }
    .app-main { padding-top: 0 !important; }

    .desktop-app-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-top: 10px;
        border: none;
    }
    .app-header-teal {
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
    
    .app-title-center { font-weight: 850; font-size: 1.8rem; text-transform: uppercase; margin: 0; flex-grow: 1; text-align: center; letter-spacing: 1px; }
    
    .btn-app-home {
        background: white;
        color: #20c997 !important;
        font-weight: bold;
        border-radius: 15px;
        padding: 5px 20px;
        text-decoration: none;
        transition: 0.3s;
        width: 120px;
        text-align: center;
    }
    .btn-app-home:hover { background: #f8f9fa; transform: scale(1.05); }

    .form-section-app { padding: 30px; background: #fff; }
    .underline-input {
        border: none;
        border-bottom: 2px solid #eee;
        border-radius: 0;
        padding: 10px 0;
        background: transparent;
        transition: 0.3s;
        font-weight: 600;
        font-size: 1.1rem;
        width: 100%;
    }
    .underline-input:focus { box-shadow: none; outline: none; border-bottom-color: #20c997; }

    .btn-save-green { background-color: #198754; color: white; border-radius: 25px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-rounded-save-big { 
        background: linear-gradient(135deg, #28a745 0%, #198754 100%);
        color: white; border-radius: 15px; border: none; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s;
    }
    .btn-rounded-save-big:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(25, 135, 84, 0.3); opacity: 0.95; }
    
    .btn-delete-pink { background-color: #ff8787; color: white; border-radius: 25px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-new-blue { background-color: #0d47a1; color: white; border-radius: 25px; padding: 10px 30px; border: none; font-weight: 700; transition: 0.3s; }
    .btn-save-green:hover, .btn-delete-pink:hover, .btn-new-blue:hover { opacity: 0.9; transform: translateY(-2px); }

    .grid-table { border: 1px solid #eee; }
    .grid-table th { background: #fdfbff; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; }
    .grid-table td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f8f9fa; }
    
    .stats-label { font-size: 0.75rem; color: #888; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 5px; }
    .auto-hide { transition: opacity 0.5s ease; }
</style>

<div class="desktop-app-card">
    <!-- Top Header Bar -->
    <div class="app-header-teal">
        <div class="window-controls">
            <div class="win-dot dot-r"></div>
            <div class="win-dot dot-y"></div>
            <div class="win-dot dot-g"></div>
        </div>
        <h2 class="app-title-center">Mess Menu Management</h2>
        <a href="<?= BASE_URL ?>" class="btn-app-home shadow-sm">Home</a>
    </div>

    <div class="form-section-app">
        <div class="row">
            <!-- Left Column: Edit Menu Form -->
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="p-4 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> EDIT DAILY MENU</h5>
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#foodItemsModal">
                            <i class="bi bi-egg-fried"></i> MASTER LIST
                        </button>
                    </div>
                    <div class="p-4 bg-white">
                        <?php if(isset($success)): ?><div class="alert alert-success rounded-3 auto-hide"><?= $success ?></div><?php endif; ?>
                        <?php if(isset($error)): ?><div class="alert alert-danger rounded-3 auto-hide"><?= $error ?></div><?php endif; ?>
                        
                        <form method="post">
                            <div class="mb-4">
                                <label class="stats-label">SELECT DAY</label>
                                <select name="day_of_week" id="daySelect" class="form-select underline-input">
                                    <?php foreach($weekDays as $day): ?>
                                        <option value="<?= $day ?>"><?= $day ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="stats-label text-warning">BREAKFAST ITEMS</label>
                                <input type="text" name="breakfast" id="inBreakfast" class="form-control underline-input" placeholder="Add items...">
                            </div>
                            <div class="mb-4">
                                <label class="stats-label text-success">LUNCH ITEMS</label>
                                <input type="text" name="lunch" id="inLunch" class="form-control underline-input" placeholder="Add items...">
                            </div>
                            <div class="mb-4">
                                <label class="stats-label text-danger">DINNER ITEMS</label>
                                <input type="text" name="dinner" id="inDinner" class="form-control underline-input" placeholder="Add items...">
                            </div>

                            <button type="submit" name="update_menu" class="btn btn-rounded-save-big w-100 py-3 shadow-sm">
                                <i class="bi bi-check2-circle me-2 fs-5"></i> CONFIRM & SAVE MENU
                            </button>
                        </form>
                    </div>
                </div>
    </div>

    <!-- Right Column: View Full Menu -->
    <div class="col-md-7">
        <div class="card card-outline card-info desktop-app-card">
            <div class="card-header"><h3 class="card-title">Current Weekly Menu</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-bordered">
                    <thead class="table-light"><tr><th>Day</th><th>Breakfast</th><th>Lunch</th><th>Dinner</th></tr></thead>
                    <tbody>
                        <?php foreach($weekDays as $day): $m = $menuData[$day]; ?>
                        <tr>
                            <td class="fw-bold"><?= $day ?></td>
                            <td><?= showTags($m['breakfast']) ?></td>
                            <td><?= showTags($m['lunch']) ?></td>
                            <td><?= showTags($m['dinner']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Tagify JS and Initializer -->
<script src="https://unpkg.com/@yaireo/tagify"></script>
<script src="https://unpkg.com/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>

<!-- Modal for Managing Food Items -->
<div class="modal fade" id="foodItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-egg-fried me-2"></i> MASTER FOOD LIST</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-4">Manage the items that appear in the suggestions when creating the weekly menu.</p>
                <!-- Add Item Form -->
                <form method="post" class="row g-3 mb-5 align-items-end p-4 rounded-4 bg-light border shadow-sm">
                    <div class="col-md-5">
                        <label class="stats-label text-dark">NEW ITEM NAME</label>
                        <input type="text" name="item_name" class="form-control underline-input" placeholder="e.g. Omelette" required>
                    </div>
                    <div class="col-md-4">
                        <label class="stats-label text-dark">CATEGORY</label>
                        <select name="category" class="form-select underline-input">
                            <option>Breakfast</option><option>Lunch</option><option>Dinner</option><option selected>General</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="add_food_item" class="btn btn-save-green w-100 rounded-pill py-2 shadow-sm fw-bold">ADD ITEM</button>
                    </div>
                </form>
                <!-- List Section -->
                <div class="table-responsive rounded-3">
                <table class="table grid-table mb-0">
                    <thead class="sticky-top">
                        <tr><th class="ps-3">Item Name</th><th>Category</th><th class="text-center">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($allFoodItems as $item): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $item['category'] ?></span></td>
                            <td class="text-center"><a href="?delete_item=<?= $item['id'] ?>" class="btn btn-sm text-danger border-0" onclick="return confirm('Delete this item from master list?')"><i class="bi bi-trash3-fill"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Live Clock for App Header
    function updateClockApp() {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('live-clock-app').innerText = now.toLocaleDateString('en-US', options);
    }
    setInterval(updateClockApp, 1000);
    updateClockApp();

    // 2. Load Data from PHP
    var menuData = <?= json_encode($menuData) ?>;
    var whitelist = <?= json_encode($foodItems) ?>;
    var daySelect = document.getElementById('daySelect');

    // 3. Function to update inputs when Day changes
    function updateInputs() {
        var selectedDay = daySelect.value;
        var data = menuData[selectedDay];

        // Clear and Add new tags (Parse JSON string to Object)
        tagB.removeAllTags();
        if(data.breakfast) tagB.addTags(JSON.parse(data.breakfast));

        tagL.removeAllTags();
        if(data.lunch) tagL.addTags(JSON.parse(data.lunch));

        tagD.removeAllTags();
        if(data.dinner) tagD.addTags(JSON.parse(data.dinner));
    }
    
    // 1. Initialize Tagify on Inputs with Whitelist
    var tagifyOptions = {
        whitelist: whitelist,
        dropdown: {
            maxItems: 20,
            classname: "tags-look",
            enabled: 0, // show dropdown on focus
            closeOnSelect: false
        }
    };
    var tagB = new Tagify(document.getElementById('inBreakfast'), tagifyOptions);
    var tagL = new Tagify(document.getElementById('inLunch'), tagifyOptions);
    var tagD = new Tagify(document.getElementById('inDinner'), tagifyOptions);

    // 4. Bind Event and Trigger once
    daySelect.addEventListener('change', updateInputs);
    updateInputs(); // Run on load for 'Monday'

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => el.style.display = 'none');
    }, 5000);

});
</script>

<?php require_once '../../includes/footer.php'; ?>