<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

// Get all active batches for the department
$stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE c.dept_id = ? AND b.status = 1 ORDER BY c.course_name, b.batch_name");
$stmt->execute([$dept_id]);
$batches = $stmt->fetchAll();

$selected_batch = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;
$arrears = [];

if ($selected_batch) {
    // Fetch students who have result_status = 0 (Fail) in the Result table for the selected batch
    $stmt = $conn->prepare("SELECT s.register_no, s.student_name, sub.sub_name, sub.sub_code, r.total_marks, r.result_status
                            FROM Result r
                            JOIN Student s ON r.student_id = s.student_id
                            JOIN Subject sub ON r.sub_id = sub.sub_id
                            WHERE s.batch_id = ? AND r.result_status = 0
                            ORDER BY s.register_no ASC, sub.sub_code ASC");
    $stmt->execute([$selected_batch]);
    $arrears = $stmt->fetchAll();
}

require_once '../../includes/hod_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Arrear Tracking Report</h3>
        <p class="text-muted small mb-0">List of students with failed subjects and pending marks.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (!empty($arrears)): ?>
            <button onclick="window.print()" class="btn btn-outline-secondary d-print-none"><i class="bi bi-printer"></i> Print PDF</button>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 d-print-none rounded-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-9">
                <label class="form-label fw-bold text-muted small text-uppercase">Select Batch</label>
                <select class="form-select border-0 bg-light p-2 rounded-3" name="batch_id" required>
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $selected_batch == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Generate List</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-container">
        <?php if (!empty($arrears)): ?>
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-dark small fw-bold text-uppercase border-bottom">
                    <tr>
                        <th class="ps-4 py-3">Roll No</th>
                        <th class="py-3">Student Name</th>
                        <th class="py-3">Failed Subject</th>
                        <th class="py-3 text-center">Marks</th>
                        <th class="py-3 text-center px-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arrears as $row): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['register_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($row['sub_name']); ?></div>
                                <span class="badge bg-light text-muted border small"><?php echo $row['sub_code']; ?></span>
                            </td>
                            <td class="text-center fw-bold text-danger"><?php echo $row['total_marks']; ?></td>
                            <td class="text-center px-4">
                                <span class="badge bg-danger rounded-pill px-3">ARREAR</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-emoji-smile fs-1 mb-3 opacity-25"></i>
                <p><?php echo $selected_batch ? "No arrear records found for this batch. Excellent!" : "Select a batch to view arrear reports."; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
