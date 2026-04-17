<?php
require_once '../../core/db.php';

echo "<pre style='font-family: monospace; background: #333; color: #0f0; padding: 20px; border-radius: 5px; line-height: 1.6;'>";

try {
    $pdo->beginTransaction();

    echo ">> Checking 'student_fees' table...\n";

    // 1. Change status column to include new values
    echo "  - Modifying 'status' column to add new verification states...\n";
    $pdo->exec("ALTER TABLE student_fees MODIFY COLUMN status ENUM('pending', 'paid', 'pending_verification', 'rejected', 'due') NOT NULL DEFAULT 'due';");

    // 2. Add new columns for receipt and remarks
    echo "  - Adding 'payment_receipt' column...\n";
    try { $pdo->exec("ALTER TABLE student_fees ADD COLUMN payment_receipt VARCHAR(255) DEFAULT NULL AFTER amount;"); } catch (Exception $e) { echo "    - Column 'payment_receipt' already exists. Skipping.\n"; }
    
    echo "  - Adding 'admin_remarks' column...\n";
    try { $pdo->exec("ALTER TABLE student_fees ADD COLUMN admin_remarks TEXT DEFAULT NULL AFTER payment_receipt;"); } catch (Exception $e) { echo "    - Column 'admin_remarks' already exists. Skipping.\n"; }

    // 3. Migrate old data
    echo "  - Migrating old 'pending' status to new 'due' status...\n";
    $updated = $pdo->exec("UPDATE student_fees SET status = 'due' WHERE status = 'pending';");
    echo "    - $updated records updated.\n";

    // 4. Create uploads directory
    $upload_dir = __DIR__ . '/../../uploads/receipts';
    if (!is_dir($upload_dir)) {
        if (mkdir($upload_dir, 0777, true)) {
            echo "  - Created directory: /uploads/receipts/\n";
        } else {
            throw new Exception("Failed to create uploads directory. Please create it manually: " . realpath(__DIR__ . '/../..') . "/uploads/receipts");
        }
    } else {
        echo "  - Directory /uploads/receipts/ already exists.\n";
    }

    $pdo->commit();
    echo "\n\n<h2 style='color: #0f0;'>✅ SUCCESS: Database updated for Fee Verification System.</h2> <p style='color: #fff;'>You can now delete this script file (`fix_fees_db.php`).</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n\n<h2 style='color: #f00;'>❌ ERROR: An error occurred.</h2>\n";
    echo "Error Message: " . $e->getMessage();
}

echo "</pre>";
?>