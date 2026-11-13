<?php
require_once '../../includes/auth.php';
requireHODorTutor();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

if (isHOD()) {
    $stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE c.dept_id = ? AND b.status = 1 ORDER BY c.course_name, b.batch_name");
    $stmt->execute([$dept_id]);
    $batches = $stmt->fetchAll();
} else {
    $stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift FROM Batch b JOIN Tutor t ON b.batch_id = t.batch_id JOIN Staff s ON t.staff_id = s.staff_id JOIN Course c ON b.course_id = c.course_id WHERE s.user_id = ? AND t.status = 1 AND b.status = 1 ORDER BY c.course_name, b.batch_name");
    $stmt->execute([$_SESSION['user_id']]);
    $batches = $stmt->fetchAll();
}

$selected_batch = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;
$students = [];

if ($selected_batch) {
    // Security check: ensure batch belongs to department
    $stmt = $conn->prepare("SELECT b.batch_id, b.batch_name FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE b.batch_id = ? AND c.dept_id = ?");
    $stmt->execute([$selected_batch, $dept_id]);
    $batch_info = $stmt->fetch();

    if ($batch_info) {
        $stmt = $conn->prepare("SELECT s.*, 
                                (SELECT ss.semester_id FROM Student_Semester ss WHERE ss.student_id = s.student_id AND ss.is_active = 1 LIMIT 1) as current_semester
                                FROM Student s 
                                WHERE s.batch_id = ? AND s.status = 1 
                                ORDER BY s.student_name ASC");
        $stmt->execute([$selected_batch]);
        $students = $stmt->fetchAll();
    }
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $selected_batch) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="batch_students_' . str_replace(' ', '_', $batch_info['batch_name']) . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Register No', 'Name', 'Current Sem', 'Mobile', 'Email']);
    foreach ($students as $row) {
        fputcsv($output, [$row['register_no'], $row['student_name'], 'Sem ' . ($row['current_semester'] ?? '-'), $row['mobile_no'], $row['email']]);
    }
    fclose($output);
    exit();
}

if (isHOD()) {
    require_once '../../includes/hod_header.php';
} else {
    require_once '../../includes/header.php';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Batch-wise Student Report</h3>
    <div class="d-flex gap-2">
        <?php if ($selected_batch): ?>
            <button onclick="window.print()" class="btn btn-outline-secondary d-print-none"><i class="bi bi-printer"></i> Print PDF</button>
            <a href="?batch_id=<?php echo $selected_batch; ?>&export=csv" class="btn btn-success d-print-none"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
        <?php
endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Select Batch</label>
                <select class="form-select" name="batch_id" onchange="this.form.submit()">
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $selected_batch == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if ($selected_batch && $batch_info): ?>
            <div class="mb-3">
                <h5 class="fw-bold">Batch: <?php echo htmlspecialchars($batch_info['batch_name']); ?></h5>
                <hr>
            </div>
            <div class="table-container border-0">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="py-3">Reg No</th>
                            <th class="py-3">Name</th>
                            <th class="py-3">Sem</th>
                            <th class="py-3">Mobile</th>
                            <th class="py-3">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5" class="text-center py-4">No students found.</td></tr>
                        <?php
    else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="font-monospace small"><?php echo htmlspecialchars($s['register_no']); ?></td>
                                    <td><?php echo htmlspecialchars($s['student_name']); ?></td>
                                    <td class="text-center">Sem <?php echo $s['current_semester'] ?? '-'; ?></td>
                                    <td><?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                                </tr>
                            <?php
        endforeach; ?>
                        <?php
    endif; ?>
                    </tbody>
                </table>
            </div>
        <?php
else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-info-circle fs-1 mb-3"></i>
                <p>Please select a batch to generate the report.</p>
            </div>
        <?php
endif; ?>
    </div>
</div>

<?php

// Wrapper close
?>
        </div> <!-- Fixed mismatched tag -->
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
