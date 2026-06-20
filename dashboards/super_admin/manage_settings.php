<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// 1. Auto-Create Table if not exists (Self-Healing)
$pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 2. Initialize Default Settings if missing
$defaults = [
    'system_name' => 'University Hostel',
    'footer_text' => 'Copyright © ' . date('Y') . ' University Hostel. All rights reserved.',
    'curfew_time' => '22:00:00',
    'currency_symbol' => 'PKR'
];

foreach ($defaults as $key => $val) {
    $stmt = $pdo->prepare("SELECT setting_key FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $val]);
    }
}

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $updates = [
        'system_name' => sanitize($_POST['system_name']),
        'footer_text' => sanitize($_POST['footer_text']),
        'curfew_time' => sanitize($_POST['curfew_time']),
        'currency_symbol' => sanitize($_POST['currency_symbol'])
    ];

    $pdo->beginTransaction();
    try {
        foreach ($updates as $key => $value) {
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $key]);
        }
        $pdo->commit();
        $success = "System settings updated successfully! Changes will apply on next page load.";
        
        // Refresh settings variable for immediate display
        foreach ($updates as $k => $v) $settings[$k] = $v;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating settings: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-sliders"></i> General System Settings</h3>
            </div>
            <div class="card-body">
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <form method="post">
                    <div class="mb-4">
                        <label class="form-label fw-bold">System Name (Website Title)</label>
                        <input type="text" name="system_name" class="form-control" value="<?= htmlspecialchars($settings['system_name'] ?? 'University Hostel') ?>" required>
                        <div class="form-text">This name appears in the browser tab and top navigation.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Default Currency Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'PKR') ?>" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Hostel Curfew Time</label>
                            <input type="time" name="curfew_time" class="form-control" value="<?= htmlspecialchars($settings['curfew_time'] ?? '22:00') ?>" required>
                            <div class="form-text">Used for marking 'Late' entries in Gate Management.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Footer Text</label>
                        <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars($settings['footer_text'] ?? '') ?>">
                    </div>

                    <button type="submit" name="update_settings" class="btn btn-primary btn-lg"><i class="bi bi-save"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>