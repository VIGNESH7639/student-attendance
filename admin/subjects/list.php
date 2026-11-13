<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$tab = $_GET['tab'] ?? 'master';
$dept_id = $_GET['dept_id'] ?? '';

try {
    // Fetch courses with Dept and Shift for filter
    $courses = $conn->query("SELECT c.course_id, c.course_name, c.shift, d.dept_name 
                             FROM Course c 
                             JOIN Department d ON c.dept_id = d.dept_id 
                             ORDER BY d.dept_name, c.course_name, c.shift")->fetchAll();
    
    // Fetch all semesters for filter
    $semesters = $conn->query("SELECT * FROM Semester ORDER BY semester_no")->fetchAll();

    if ($tab === 'master') {
        $query = "SELECT s.*, st.type_name, c.course_name, c.shift, sem.semester_no 
                 FROM Subject s 
                 JOIN Subject_Type st ON s.type_id = st.type_id
                 JOIN Course_Semester cs ON s.course_sem_id = cs.course_sem_id
                 JOIN Course c ON cs.course_id = c.course_id
                 JOIN Semester sem ON cs.semester_id = sem.semester_id";
        $params = [];

        if (!empty($_GET['course_id'])) {
            $query .= " WHERE c.course_id = ?";
            $params[] = $_GET['course_id'];
        }
        
        if (!empty($_GET['semester_id'])) {
            $query .= (strpos($query, 'WHERE') !== false ? " AND" : " WHERE") . " sem.semester_id = ?";
            $params[] = $_GET['semester_id'];
        }

        $query .= " ORDER BY c.course_name, sem.semester_no, s.sub_name";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll();
    }
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php require_once '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Subject Master</h2>
        <p class="text-muted mb-0 small">Definitions and configurations for all academic subjects.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold border-2">
            <i class="bi bi-printer me-2"></i> Print List
        </button>
        <a href="add.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-journal-plus me-2"></i> Add Subject
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4 bg-white">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted text-uppercase">Course Filter</label>
                <select name="course_id" class="form-select bg-light border-0 py-2 rounded-3" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo isset($_GET['course_id']) && $_GET['course_id'] == $c['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_name'] . " - " . $c['dept_name'] . " (Shift " . $c['shift'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Semester Filter</label>
                <select name="semester_id" class="form-select bg-light border-0 py-2 rounded-3" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo $sem['semester_id']; ?>" <?php echo isset($_GET['semester_id']) && $_GET['semester_id'] == $sem['semester_id'] ? 'selected' : ''; ?>>
                            Sem <?php echo $sem['semester_no']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill py-2">Filter</button>
                <a href="list.php?tab=<?php echo $tab; ?>" class="btn btn-outline-secondary fw-bold w-100 rounded-pill py-2 text-decoration-none text-center">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-container">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Code</th>
                    <th class="py-3">Subject Name</th>
                    <th class="py-3">Course & Semester</th>
                    <th class="py-3 text-center">Type</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end">Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No subjects found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php $sno = 1; foreach ($subjects as $s): ?>
                        <tr>
                            <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                            <td><span class="badge bg-light text-primary border font-monospace"><?php echo htmlspecialchars($s['sub_code']); ?></span></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($s['sub_name']); ?></td>
                            <td>
                                <div class="small fw-bold text-primary"><?php echo htmlspecialchars($s['course_name']); ?> (Shift <?php echo $s['shift']; ?>)</div>
                                <div class="small text-muted">Semester <?php echo $s['semester_no']; ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo htmlspecialchars($s['type_name']); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $s['status'] == 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?> rounded-pill px-3">
                                    <?php echo $s['status'] == 1 ? 'Active' : 'Deactive'; ?>
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="edit.php?id=<?php echo $s['sub_id']; ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 shadow-sm">
                                    <i class="bi bi-pencil-fill px-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
