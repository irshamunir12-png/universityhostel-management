<?php
require_once 'db.php';

// Usage: open http://localhost/residential-hostel/core/run_migrations.php?run=rooms
$run = $_GET['run'] ?? '';
if (!in_array($run, ['rooms', 'courses', 'complaints', 'pages', 'announcements'])) {
    echo "No valid migration specified. Use ?run=rooms, ?run=courses, ?run=complaints, ?run=pages or ?run=announcements";
    exit;
}

// Check if table already exists
$tableName = ($run === 'pages') ? 'sys_pages' : $run; 
$check = $pdo->prepare("SHOW TABLES LIKE ?");
$check->execute([$tableName]);
if ($check->rowCount() > 0 && $run !== 'pages') { // Allow pages to run to update menu
    echo "Migration skipped: '$tableName' table already exists.";
    exit;
}

// Read SQL file
$sqlFile = __DIR__ . '/../sql/create_' . $run . '.sql';
if (!file_exists($sqlFile)) {
    echo "Migration SQL file not found: $sqlFile";
    exit;
}
$sql = file_get_contents($sqlFile);
// Split into statements (simple split by semicolon)
$stmts = array_filter(array_map('trim', explode(';', $sql)));

try {
    foreach ($stmts as $s) {
        if ($s === '') continue;
        $pdo->exec($s);
    }
    echo ucfirst($run) . " migration executed successfully.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
