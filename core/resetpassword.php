<?php
require_once 'db.php'; // Database connection load karein

$target_email = 'admin@hostel.com';
$target_password = 'admin123';

echo "<h2>Residential Hostel ERP - Password Reset Tool</h2>";

try {
    // 1. Pehle check karein ke ye email database mein hai ya nahi
    $check = $pdo->prepare("SELECT id, name, role, is_active FROM users WHERE email = ?");
    $check->execute([$target_email]);
    $user = $check->fetch();

    if (!$user) {
        echo "<p style='color:red;'>❌ ERROR: Email <strong>$target_email</strong> database mein nahi mili!</p>";
        echo "<p>Aapne shayad 'database.sql' sahi se import nahi kiya ya email galat hai.</p>";
        exit;
    }

    // 2. Agar user mil gaya, to uska password aur status update karein
    $new_hash = password_hash($target_password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ?, is_active = 1 WHERE email = ?");
    $update->execute([$new_hash, $target_email]);
    
    echo "<p style='color:green;'>✅ SUCCESS: Password reset ho gaya!</p>";
    echo "<ul>
            <li>User Found: " . $user['name'] . "</li>
            <li>Login Email: <strong>$target_email</strong></li>
            <li>New Password: <strong>$target_password</strong></li>
            <li>Account Status: <strong>Active (1)</strong></li>
          </ul>";
    
    echo "<p>Ab aap login page par ja kar ye email aur password try karein.</p>";
    echo "<a href='../login.php' style='padding:10px; background:#198754; color:white; text-decoration:none; border-radius:5px;'>Go to Login Page</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>