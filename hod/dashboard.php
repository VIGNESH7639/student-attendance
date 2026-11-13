<?php
require_once '../includes/auth.php';
requireHOD();
require_once '../config/database.php';
require_once '../includes/hod_header.php';

$dept_id = $_SESSION['dept_id'];

try {
    // 1. Total Staff
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE dept_id = ?");
    $stmt->execute([$dept_id]);
    $total_staff = $stmt->fetchColumn();

    // 2. Total Students
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Student s 
                           JOIN Batch b ON s.batch_id = b.batch_id 
                           JOIN Course c ON b.course_id = c.course_id 
                           WHERE c.dept_id = ? AND s.status = 1");
    $stmt->execute([$dept_id]);
    $total_students = $stmt->fetchColumn();

    // 3. Total Subjects
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Subject sub 
                           JOIN Course_Semester cs ON sub.course_sem_id = cs.course_sem_id
                           JOIN Course c ON cs.course_id = c.course_id 
                           WHERE c.dept_id = ? AND sub.status = 1");
    $stmt->execute([$dept_id]);
    $total_subjects = $stmt->fetchColumn();

    // 4. Total Batches
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Batch b 
                           JOIN Course c ON b.course_id = c.course_id 
                           WHERE c.dept_id = ? AND b.status = 1");
    $stmt->execute([$dept_id]);
    $total_batches = $stmt->fetchColumn();

    // Fetch Department Name
    $stmt = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
    $stmt->execute([$dept_id]);
    $dept_name = $stmt->fetchColumn();

    // Fetch HOD's own subjects (Teaching Panel)
    $my_subjects = [];
    if (isset($_SESSION['staff_id'])) {
        $stmt = $conn->prepare("SELECT ss.*, sub.sub_name, sub.sub_code, b.batch_name, sem.semester_no, c.course_name, c.shift
                               FROM Staff_Subject ss 
                               JOIN Subject sub ON ss.sub_id = sub.sub_id 
                               JOIN Batch b ON ss.batch_id = b.batch_id 
                               JOIN Course c ON b.course_id = c.course_id
                               JOIN Course_Semester cs ON sub.course_sem_id = cs.course_sem_id
                               JOIN Semester sem ON cs.semester_id = sem.semester_id
                               WHERE ss.staff_id = ? AND ss.status = 1 AND sub.status = 1");
        $stmt->execute([$_SESSION['staff_id']]);
        $my_subjects = $stmt->fetchAll();
    }
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="row g-4 mb-4 mt-2">
    <!-- Welcome Section -->
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-building-fill fs-3"></i>
                        </div>
                        <div>
                            <h2 class="font-heading fw-bold text-dark mb-0">Department of <?php echo htmlspecialchars($dept_name); ?></h2>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($dept_name); ?> Administration Panel</p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="../hod/students/add.php" class="btn btn-premium btn-premium-primary">
                            <i class="bi bi-person-plus-fill"></i> Register Student
                        </a>
                        <a href="staff/list.php" class="btn btn-premium bg-white text-dark shadow-sm border">
                            <i class="bi bi-people-fill text-primary"></i> Staff Directory
                        </a>
                    </div>
                </div>
                <div class="col-md-4 text-end d-none d-md-block opacity-25">
                    <i class="bi bi-bank2" style="font-size: 5rem; color: var(--primary-color);"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-premium">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <h6 class="text-muted small mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Staff</h6>
            </div>
            <h2 class="font-heading fw-bold mb-1"><?php echo $total_staff; ?></h2>
            <p class="text-muted small mb-0">Faculty Members</p>
            <a href="staff/list.php" class="stretched-link"></a>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-premium">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 me-3">
                    <i class="bi bi-mortarboard-fill fs-4"></i>
                </div>
                <h6 class="text-muted small mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Students</h6>
            </div>
            <h2 class="font-heading fw-bold mb-1"><?php echo $total_students; ?></h2>
            <p class="text-muted small mb-0">Enrolled Students</p>
            <a href="../admin/students/list.php" class="stretched-link"></a>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-premium">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 me-3">
                    <i class="bi bi-journal-check fs-4"></i>
                </div>
                <h6 class="text-muted small mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Subjects</h6>
            </div>
            <h2 class="font-heading fw-bold mb-1"><?php echo $total_subjects; ?></h2>
            <p class="text-muted small mb-0">Curriculum Load</p>
            <a href="subjects/list.php" class="stretched-link"></a>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card-premium">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-2 me-3">
                    <i class="bi bi-layers-fill fs-4"></i>
                </div>
                <h6 class="text-muted small mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Batches</h6>
            </div>
            <h2 class="font-heading fw-bold mb-1"><?php echo $total_batches; ?></h2>
            <p class="text-muted small mb-0">Active Batches</p>
            <a href="batches/list.php" class="stretched-link"></a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-md-8">
        <div class="glass-card overflow-hidden">
            <div class="p-4 border-bottom border-light">
                <h5 class="font-heading fw-bold mb-0 text-dark">My Teaching Load</h5>
            </div>
            <div class="table-responsive-premium">
                <table class="table table-premium align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Subject</th>
                            <th>Course</th>
                            <th class="text-center">Shift</th>
                            <th>Batch & Sem</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_subjects)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No active teaching allocations.</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_subjects as $s): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['sub_name']); ?></div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-2 small mt-1"><?php echo htmlspecialchars($s['sub_code']); ?></span>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-dark"><?php echo htmlspecialchars($s['course_name']); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $s['shift'] == 1 ? 'bg-info' : 'bg-warning'; ?> bg-opacity-10 <?php echo $s['shift'] == 1 ? 'text-info' : 'text-warning'; ?> rounded-pill px-3 py-1">
                                            Shift <?php echo $s['shift']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['batch_name']); ?></div>
                                        <div class="small text-muted">Semester <?php echo $s['semester_no']; ?></div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group">
                                            <a href="../staff/attendance/create.php?id=<?php echo $s['alloc_id']; ?>" class="btn btn-sm btn-light border p-2 px-3 rounded-start" title="Attendance">
                                                <i class="bi bi-calendar-check-fill text-primary"></i>
                                            </a>
                                            <a href="../staff/marks/entry.php?alloc_id=<?php echo $s['alloc_id']; ?>" class="btn btn-sm btn-light border p-2 px-3" title="Marks">
                                                <i class="bi bi-award-fill text-success"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light border p-2 px-2 dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                <li><a class="dropdown-item py-2" href="../staff/reports/attendance.php?id=<?php echo $s['alloc_id']; ?>"><i class="bi bi-file-earmark-person me-2 text-primary"></i> Attendance Report</a></li>
                                                <li><a class="dropdown-item py-2" href="../staff/reports/marks.php?id=<?php echo $s['alloc_id']; ?>"><i class="bi bi-file-earmark-bar-graph me-2 text-success"></i> Marks Report</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="glass-card p-4 h-100">
            <h5 class="font-heading fw-bold mb-4 text-dark">Quick Panels</h5>
            
            <?php
            $mentorStmt = $conn->prepare("SELECT t.*, b.batch_name FROM Tutor t JOIN Batch b ON t.batch_id = b.batch_id WHERE t.staff_id = ? AND t.status = 1");
            $mentorStmt->execute([$_SESSION['staff_id']]);
            $mentor_roles = $mentorStmt->fetchAll();
            
            if (!empty($mentor_roles)): ?>
                <div class="mb-4">
                    <label class="small fw-bold text-muted text-uppercase mb-3 opacity-50" style="letter-spacing: 1px;">Tutor Responsibility</label>
                    <?php foreach ($mentor_roles as $mr): ?>
                        <div class="p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-10 mb-2">
                            <div class="small fw-bold text-success mb-1">Batch Active</div>
                            <h6 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($mr['batch_name']); ?></h6>
                            <a href="../mentor/dashboard.php" class="btn btn-premium btn-premium-primary w-100 py-2 shadow-sm">Open Tutor Hub</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="small fw-bold text-muted text-uppercase mb-3 opacity-50" style="letter-spacing: 1px;">Status & Logs</label>
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-4 bg-primary bg-opacity-5 border border-primary border-opacity-10">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Secure Access</h6>
                        <p class="text-muted small mb-0">HOD Credentials Active</p>
                    </div>
                </div>
                
                
            </div>

            <div class="mt-auto pt-3">
                <div class="bg-light p-3 rounded-3">
                    <p class="mb-0 small text-muted"><i class="bi bi-info-circle-fill me-1 text-primary"></i> Operating for <strong><?php echo htmlspecialchars($dept_name); ?></strong> department.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
