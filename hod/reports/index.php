<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';
require_once '../../includes/hod_header.php';

$dept_id = $_SESSION['dept_id'];

// Fetch Department Name
$stmt = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
$stmt->execute([$dept_id]);
$dept_name = $stmt->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Department Reports</h3>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo htmlspecialchars($dept_name); ?></span>
</div>

<div class="row g-4">
    <!-- Basic Reports -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Directory Reports</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <a href="staff_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">Department Staff Report</div>
                            <small class="text-muted">Full list of staff with roles and contact info.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="student_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">Department Student Report</div>
                            <small class="text-muted">All active students across all batches.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="batch_student_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">Batch-wise Student Report</div>
                            <small class="text-muted">Detailed list filtered by specific batch.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                </ul>
            </div>
        </div>
    </div>

    <!-- Allocation Reports -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-success"><i class="bi bi-link-45deg me-2"></i>Allocation Reports</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <a href="subject_allocation_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">Subject Allocation Report</div>
                            <small class="text-muted">Staff to Subject mapping for academic years.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="tutor_allocation_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">Tutor Allocation Report</div>
                            <small class="text-muted">Tutor assignments per batch.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="subject_list_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-success">Subject Summary Report</div>
                            <small class="text-muted">Course and Semester-wise subject listing.</small>
                        </div>
                        <i class="bi bi-journal-check text-success"></i>
                    </a>
                </ul>
            </div>
        </div>
    </div>

    <!-- Academic Performance -->
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-warning"><i class="bi bi-graph-up-arrow me-2"></i>Performance & Analytics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="attendance_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border rounded mb-2">
                            <div>
                                <div class="fw-bold text-dark">Attendance Percentage Report</div>
                                <small class="text-muted">Overall attendance statistics for students.</small>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <a href="daily_attendance.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border rounded mb-2 border-success-subtle bg-success-subtle bg-opacity-10">
                            <div>
                                <div class="fw-bold text-success">Daily Attendance Report</div>
                                <small class="text-muted">Day-by-day, period-level tracking.</small>
                            </div>
                            <i class="bi bi-calendar-event text-success"></i>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="marks_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border rounded mb-2">
                            <div>
                                <div class="fw-bold text-dark">Marks Summary</div>
                                <small class="text-muted">Pass/Fail performance.</small>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <a href="internal_summary.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border rounded mb-2 bg-warning bg-opacity-10 border-warning-subtle">
                            <div>
                                <div class="fw-bold text-dark text-warning">Internal Mark Summary</div>
                                <small class="text-muted">Detailed CIA, Assignment, Seminar breakdown.</small>
                            </div>
                            <i class="bi bi-file-earmark-diff text-warning"></i>
                        </a>
                    </div>
                    <div class="col-md-4 text-center">
                        <a href="arrears.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border rounded mb-2 border-danger-subtle bg-danger-subtle bg-opacity-10">
                            <div>
                                <div class="fw-bold text-danger">Arrear Tracking</div>
                                <small class="text-muted">Failed subject students list.</small>
                            </div>
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
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
