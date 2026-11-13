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
$selected_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;

// Get subjects filtered by selected batch (if any)
if ($selected_batch) {
    $stmt = $conn->prepare("SELECT DISTINCT s.sub_id as subject_id, s.sub_name as subject_name, s.sub_code as subject_code 
                           FROM Subject s 
                           JOIN Staff_Subject ss ON s.sub_id = ss.sub_id 
                           WHERE ss.batch_id = ? AND s.status = 1 
                           ORDER BY s.sub_name");
    $stmt->execute([$selected_batch]);
} else {
    $stmt = $conn->prepare("SELECT s.sub_id as subject_id, s.sub_name as subject_name, s.sub_code as subject_code 
                           FROM Subject s 
                           JOIN Course_Semester cs ON s.course_sem_id = cs.course_sem_id 
                           JOIN Course c ON cs.course_id = c.course_id 
                           WHERE c.dept_id = ? AND s.status = 1 
                           ORDER BY s.sub_name");
    $stmt->execute([$dept_id]);
}
$subjects = $stmt->fetchAll();

$marks = [];

if ($selected_batch && $selected_subject) {
    // Security check
    $stmt = $conn->prepare("SELECT s.sub_name as subject_name 
                           FROM Subject s 
                           JOIN Course_Semester cs ON s.course_sem_id = cs.course_sem_id 
                           JOIN Course c ON cs.course_id = c.course_id 
                           WHERE s.sub_id = ? AND c.dept_id = ?");
    $stmt->execute([$selected_subject, $dept_id]);
    $subject_info = $stmt->fetch();

    if ($subject_info) {
        $stmt = $conn->prepare("SELECT s.register_no, s.student_name, r.internal_total, r.external_marks, r.total_marks, r.result_status 
                                FROM Student s 
                                LEFT JOIN result r ON s.student_id = r.student_id AND r.sub_id = ?
                                WHERE s.batch_id = ? AND s.status = 1 
                                ORDER BY s.register_no ASC");
        $stmt->execute([$selected_subject, $selected_batch]);
        $marks = $stmt->fetchAll();

        // Fetch Allocation ID for correction route
        $stmt_alloc = $conn->prepare("SELECT alloc_id FROM Staff_Subject WHERE sub_id = ? AND batch_id = ? AND status = 1 LIMIT 1");
        $stmt_alloc->execute([$selected_subject, $selected_batch]);
        $current_alloc_id = $stmt_alloc->fetchColumn();
    }
}

if (isHOD()) {
    require_once '../../includes/hod_header.php';
} else {
    require_once '../../includes/header.php';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Marks Summary Report</h3>
    <div class="d-flex gap-2">
        <?php if (!empty($marks)): ?>
            <?php if (isHOD() && isset($current_alloc_id) && $current_alloc_id): ?>
                <a href="../../staff/marks/entry.php?alloc_id=<?php echo $current_alloc_id; ?>" class="btn btn-primary d-print-none shadow-sm rounded-pill px-4 fw-bold">
                    <i class="bi bi-pencil-square me-2"></i> Edit Marks
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-outline-secondary d-print-none rounded-pill px-4 fw-bold shadow-sm"><i class="bi bi-printer me-2"></i> Print PDF</button>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-bold">Batch</label>
                <select class="form-select shadow-sm border-primary-subtle" name="batch_id" required onchange="this.form.submit()">
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $selected_batch == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Subject</label>
                <select class="form-select" name="subject_id" required>
                    <option value="">-- Choose Subject --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo $s['subject_id']; ?>" <?php echo $selected_subject == $s['subject_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['subject_name']); ?> (<?php echo $s['subject_code']; ?>)
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Get Marks</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (!empty($marks)): ?>
            <h5 class="fw-bold mb-3">Marks Summary: <?php echo htmlspecialchars($subject_info['subject_name']); ?></h5>
            <div class="table-container border-0">
                <table class="table table-bordered table-striped text-center">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="text-start">Roll No</th>
                            <th class="text-start">Student Name</th>
                            <th>Internal</th>
                            <th>Semester</th>
                            <th>Total</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marks as $row): ?>
                            <tr>
                                <td class="text-start"><?php echo htmlspecialchars($row['register_no']); ?></td>
                                <td class="text-start fw-bold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo $row['internal_total'] ?? '-'; ?></td>
                                <td><?php echo $row['external_marks'] ?? '-'; ?></td>
                                <td class="fw-bold"><?php echo $row['total_marks'] ?? '-'; ?></td>
                                <td>
                                    <?php if ($row['result_status'] !== null): ?>
                                        <span class="badge rounded-pill px-3 <?php echo $row['result_status'] == 1 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $row['result_status'] == 1 ? 'PASS' : 'FAIL'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-award fs-1 mb-3"></i>
                <p>Please select a batch and subject to view marks.</p>
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
