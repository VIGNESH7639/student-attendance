<?php
require_once '../../includes/auth.php';
requireAdminOrHOD();
require_once '../../config/database.php';

$is_hod = isHOD();
$hod_dept_id = $_SESSION['dept_id'] ?? null;

if ($is_hod) {
    require_once '../../includes/hod_header.php';
}
else {
    require_once '../../includes/header.php';
}

try {
    $query = "SELECT b.batch_id, b.batch_name, b.admission_year, d.dept_name, c.course_name, c.shift,
              COUNT(s.student_id) AS total_students
              FROM Batch b
              JOIN Course c ON b.course_id = c.course_id
              JOIN Department d ON c.dept_id = d.dept_id
              LEFT JOIN Student s ON b.batch_id = s.batch_id";

    $params = [];
    if ($is_hod) {
        $query .= " WHERE c.dept_id = ? ";
        $params[] = $hod_dept_id;
    }

    $query .= " GROUP BY b.batch_id, b.batch_name, b.admission_year, d.dept_name, c.course_name, c.shift
                ORDER BY b.admission_year DESC, b.batch_name ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $batches = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>


<?php

$dashboard_link = $is_hod ? "../../hod/dashboard.php" : "../dashboard.php";
?>
<a href="<?php echo $dashboard_link; ?>" class="text-decoration-none text-muted mb-3 d-inline-block">
    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
</a>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Student Management - Batches</h2>
        <p class="text-muted">Select a batch to manage its students</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4">Batch Details</th>
                    <th>Course & Shift</th>
                    <th>Department</th>
                    <th class="text-center">Total Students</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-layers opacity-25 d-block mb-3" style="font-size: 3rem;"></i>
                            No batches found assigned to your department.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($batches as $b): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($b['batch_name']); ?></div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small">
                                        <?php echo htmlspecialchars($b['admission_year']); ?>
                                    </span>
                                    <span class="small text-muted font-monospace">ID: <?php echo htmlspecialchars($b['batch_id']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-secondary"><?php echo htmlspecialchars($b['course_name']); ?></div>
                                <span class="badge <?php echo $b['shift'] == 1 ? 'bg-info bg-opacity-10 text-info border-info-subtle' : 'bg-warning bg-opacity-10 text-warning border-warning-subtle'; ?> border px-2 py-1 mt-1">
                                    Shift <?php echo htmlspecialchars($b['shift']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-muted fw-medium"><i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($b['dept_name']); ?></div>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle fw-bold text-dark" style="width: 40px; height: 40px;">
                                    <?php echo htmlspecialchars($b['total_students']); ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                    <a href="list.php?batch_id=<?php echo $b['batch_id']; ?>" class="btn btn-sm btn-light border-end px-3 fw-bold text-primary hover-primary" title="View Students">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="add.php?batch_id=<?php echo $b['batch_id']; ?>" class="btn btn-sm btn-light px-3 fw-bold text-success hover-success" title="Add Students">
                                        <i class="bi bi-person-plus"></i> Add
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.hover-primary:hover { background-color: #eff6ff !important; color: #0d6efd !important; }
.hover-success:hover { background-color: #f0fdf4 !important; color: #198754 !important; }
</style>

<?php require_once '../../includes/footer.php'; ?>
