<?php
require_once '../../includes/header.php';
require_once '../../core/functions.php';

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = sanitize($_POST['department_name']);
    $desc = sanitize($_POST['description']);

    if (empty($name)) {
        $error = "Department Name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO departments (department_name, description) VALUES (?, ?)");
        try {
            $stmt->execute([$name, $desc]);
            $success = "Department added successfully!";
        } catch (PDOException $e) {
            $error = "Error: Department might already exist.";
        }
    }
}

// Handle Soft Delete Department
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("UPDATE departments SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='manage_departments.php?msg=deleted';</script>";
    exit;
}

// Fetch All Non-Deleted Departments
$departments = $pdo->query("SELECT * FROM departments WHERE is_deleted = 0 ORDER BY department_name ASC")->fetchAll();
?>

<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">Department Management</h3>
    </div>
    <div class="card-body">
        <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?><div class="alert alert-info">Department moved to trash.</div><?php endif; ?>

        <!-- Add Department Form -->
        <form method="post" class="row g-3 mb-4 align-items-end p-3 border rounded bg-light">
            <div class="col-md-5">
                <label class="form-label">Department Name</label>
                <input type="text" name="department_name" class="form-control" placeholder="e.g. Computer Science" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Description (Optional)</label>
                <input type="text" name="description" class="form-control" placeholder="Short description">
            </div>
            <div class="col-md-2">
                <button type="submit" name="add_department" class="btn btn-primary w-100 mt-3">Add Department</button>
            </div>
        </form>

        <!-- Departments List -->
        <h5 class="mt-4">Available Departments</h5>
        <table class="table table-striped table-hover">
            <thead><tr><th>ID</th><th>Department Name</th><th>Description</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($departments as $d): ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td><strong><?= htmlspecialchars($d['department_name']) ?></strong></td>
                    <td><?= htmlspecialchars($d['description']) ?></td>
                    <td><a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Move this department to trash?')"><i class="bi bi-trash"></i></a></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($departments)): ?><tr><td colspan="4" class="text-center text-muted">No departments found. Add one above.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>