<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../includes/header.php';

try {
    $stmt = $conn->query("SELECT * FROM Department ORDER BY dept_name ASC");
    $departments = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error fetching departments: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Departments</h2>
        <p class="text-muted mb-0 small">Manage organizational units and staff counts.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold border-2">
            <i class="bi bi-printer me-2"></i> Print List
        </button>
        <a href="add.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-plus-circle me-2"></i> Add Department
        </a>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-container border-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Name</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end">Edit</th>
                </tr>
            </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No departments found.</td>
                        </tr>
                    <?php
else: ?>
                        <?php $sno = 1; foreach ($departments as $dept): ?>
                            <tr>
                                <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($dept['dept_name']); ?></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $dept['status'] == 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?> rounded-pill px-3">
                                        <?php echo $dept['status'] == 1 ? 'Active' : 'Deactive'; ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="edit.php?id=<?php echo $dept['dept_id']; ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 shadow-sm">
                                        <i class="bi bi-pencil-fill px-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
