<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';
require_once '../../includes/hod_header.php';

$dept_id = $_SESSION['dept_id'];

// Get filter parameters
$batch_id = $_GET['batch_id'] ?? '';
$semester_no = $_GET['semester_no'] ?? '';
$search = $_GET['search'] ?? '';

try {
    // 1. Fetch Batches for the Department
    $batch_stmt = $conn->prepare("SELECT DISTINCT b.batch_id, b.batch_name, c.course_name, c.shift 
                                   FROM Batch b 
                                   JOIN Course c ON b.course_id = c.course_id 
                                   WHERE c.dept_id = ? 
                                   ORDER BY c.course_name, b.batch_name DESC");
    $batch_stmt->execute([$dept_id]);
    $batches = $batch_stmt->fetchAll();

    // 2. Fetch Semesters
    $sem_stmt = $conn->query("SELECT DISTINCT semester_no FROM Semester ORDER BY semester_no ASC");
    $semesters = $sem_stmt->fetchAll();

    // 3. Build the Main Query with Filters
    $query = "SELECT ss.alloc_id, ss.academic_year, ss.status, ss.staff_id,
                    st.staff_name, st.staff_reg_id,
                    sub.sub_name, sub.sub_code, 
                    b.batch_name, c.shift, c.course_name, sem.semester_no
             FROM Staff_Subject ss 
             LEFT JOIN Staff st ON ss.staff_id = st.staff_id 
             JOIN Subject sub ON ss.sub_id = sub.sub_id 
             JOIN Batch b ON ss.batch_id = b.batch_id 
             JOIN Course_Semester cs ON sub.course_sem_id = cs.course_sem_id
             JOIN Semester sem ON cs.semester_id = sem.semester_id
             JOIN Course c ON cs.course_id = c.course_id
             WHERE c.dept_id = ?";
    
    $params = [$dept_id];

    if ($batch_id !== '') {
        $query .= " AND ss.batch_id = ?";
        $params[] = $batch_id;
    }
    if ($semester_no !== '') {
        $query .= " AND sem.semester_no = ?";
        $params[] = $semester_no;
    }
    if ($search !== '') {
        $query .= " AND (st.staff_name LIKE ? OR st.staff_reg_id LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $query .= " ORDER BY ss.academic_year DESC, b.batch_name ASC, sem.semester_no ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $allocations = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="row align-items-center mb-4 mt-2">
    <div class="col-sm-7 col-12">
        <h2 class="fw-bold mb-1 text-dark">Staff Allocation</h2>
        <p class="text-muted mb-0">Manage and track subject assignments for the <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> department</p>
    </div>
    <div class="col-sm-5 col-12 text-sm-end mt-3 mt-sm-0">
        <a href="add.php" class="btn btn-primary rounded-pill shadow-lg fw-bold px-4 py-3 d-inline-flex align-items-center gap-2 border-0">
            <i class="bi bi-person-plus-fill fs-5"></i>
            <span>New Subject Allocation</span>
        </a>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Filter by Batch</label>
                <select name="batch_id" class="form-select border-0 bg-light rounded-3">
                    <option value="">All Batches</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $batch_id == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Semester</label>
                <select name="semester_no" class="form-select border-0 bg-light rounded-3">
                    <option value="">Any Semester</option>
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?php echo $s['semester_no']; ?>" <?php echo $semester_no == $s['semester_no'] ? 'selected' : ''; ?>>
                            Sem <?php echo $s['semester_no']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Search Staff</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-0 bg-light rounded-end-3" 
                           placeholder="Name or Staff ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100 fw-bold">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <?php if ($batch_id || $semester_no || $search): ?>
                    <a href="list.php" class="btn btn-light rounded-3 w-100 fw-bold border">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="table-container">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Faculty Member</th>
                    <th class="py-3">Subject Details</th>
                    <th class="py-3">Batch</th>
                    <th class="py-3 text-center">Semester</th>
                    <th class="py-3 text-center">Academic Year</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allocations)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="mb-3">
                                <i class="bi bi-inbox fs-1 opacity-25"></i>
                            </div>
                            No subject allocations found for your department.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $sno = 1; foreach ($allocations as $a): ?>
                        <tr>
                            <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo $a['staff_name'] ? htmlspecialchars($a['staff_name']) : '<span class="text-danger small">Not Allocated</span>'; ?></div>
                                        <div class="small text-muted italic">Reg ID: #<?php echo $a['staff_reg_id'] ?: '---'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($a['sub_name']); ?></div>
                                <div class="small text-muted font-monospace"><?php echo htmlspecialchars($a['sub_code']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($a['batch_name']); ?></div>
                                <div class="small fw-bold text-primary"><?php echo htmlspecialchars($a['course_name']); ?></div>
                                <div class="small text-muted italic">Shift <?php echo $a['shift']; ?></div>
                            </td>
...
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fw-normal px-3 py-2">Sem <?php echo $a['semester_no']; ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-white text-dark border shadow-xs px-3 py-2"><?php echo htmlspecialchars($a['academic_year']); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?php echo $a['status'] == 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?> px-3">
                                    <?php echo $a['status'] == 1 ? 'ACTIVE' : 'INACTIVE'; ?>
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="edit.php?id=<?php echo $a['alloc_id']; ?>" class="btn btn-sm btn-white border shadow-xs rounded-pill px-3 fw-bold">
                                        <i class="bi bi-pencil me-1"></i>Edit
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

<?php require_once '../../includes/footer.php'; ?>
