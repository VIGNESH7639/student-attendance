<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Admin Reports</h2>
        <p class="text-muted mb-0 small">Cross-departmental analytics and directory reports.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Directory Reports -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Directory Reports</h5>
            </div>
            <div class="card-body pt-0">
                <div class="list-group list-group-flush">
                    <a href="staff_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-0 rounded-3 mb-2 bg-light bg-opacity-50">
                        <div>
                            <div class="fw-bold">Staff Directory Report</div>
                            <small class="text-muted">List of all staff members with department filters.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="student_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-0 rounded-3 mb-2 bg-light bg-opacity-50">
                        <div>
                            <div class="fw-bold">Overall Student Report</div>
                            <small class="text-muted">Master list of students across all departments.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="batch_student_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-0 rounded-3 mb-2 bg-light bg-opacity-50">
                        <div>
                            <div class="fw-bold">Batch-wise Student Report</div>
                            <small class="text-muted">Student lists filtered by department and batch.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="department_batch_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-0 rounded-3 mb-2 bg-light bg-opacity-50 border-start border-primary border-4">
                        <div>
                            <div class="fw-bold text-primary">Department Batch Summary</div>
                            <small class="text-muted">Overview of batches, courses, and student counts.</small>
                        </div>
                        <i class="bi bi-chevron-right text-primary"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic & Performance -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h5 class="fw-bold mb-0 text-success"><i class="bi bi-graph-up-arrow me-2"></i>Academic Reports</h5>
            </div>
            <div class="card-body pt-0">
                <div class="list-group list-group-flush">
                    <a href="attendance_report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-0 rounded-3 mb-2 bg-light bg-opacity-50">
                        <div>
                            <div class="fw-bold text-dark">Attendance Summary</div>
                            <small class="text-muted">Overall attendance statistics by department/batch.</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <!-- Placeholder for future reports -->
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 rounded-3 mb-2 bg-light bg-opacity-25 opacity-50">
                        <div>
                            <div class="fw-bold">Academic Performance (TBA)</div>
                            <small class="text-muted">Examination and marks summary across college.</small>
                        </div>
                        <i class="bi bi-lock text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
