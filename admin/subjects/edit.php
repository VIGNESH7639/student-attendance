<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = (int)$_GET['id'];
$types = $conn->query("SELECT * FROM Subject_Type ORDER BY type_name")->fetchAll();

// Fetch active departments
$depts = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_sem_id = (int)$_POST['course_sem_id'];
    $type_id = (int)$_POST['type_id'];
    $code = trim($_POST['sub_code']);
    $name = trim($_POST['sub_name']);
    $status = (int)$_POST['status'];

    try {
        // Check for duplicate subject code (excluding current subject)
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Subject WHERE sub_code = ? AND sub_id != ?");
        $checkStmt->execute([$code, $id]);
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Subject code '$code' already exists.";
        } else {
            $stmt = $conn->prepare("UPDATE Subject SET sub_code = ?, sub_name = ?, type_id = ?, status = ? WHERE sub_id = ?");
            $stmt->execute([$code, $name, $type_id, $status, $id]);
            header("Location: list.php?msg=Subject updated successfully.");
            exit();
        }
    }
    catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

try {
    // Get subject details along with course_id and dept_id
    $stmt = $conn->prepare("SELECT s.*, cs.course_id, c.dept_id 
                            FROM Subject s 
                            JOIN Course_Semester cs ON s.course_sem_id = cs.course_sem_id 
                            JOIN Course c ON cs.course_id = c.course_id
                            WHERE s.sub_id = ?");
    $stmt->execute([$id]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        header("Location: list.php?error=Subject not found.");
        exit();
    }

    // Fetch courses for the current department
    $stmt = $conn->prepare("SELECT course_id, course_name, shift FROM Course WHERE dept_id = ? AND status = 1 ORDER BY course_name, shift");
    $stmt->execute([$subject['dept_id']]);
    $current_courses = $stmt->fetchAll();

    // Fetch semesters for the current course
    $stmt = $conn->prepare("SELECT cs.course_sem_id, sem.semester_no 
                            FROM Course_Semester cs 
                            JOIN Semester sem ON cs.semester_id = sem.semester_id 
                            WHERE cs.course_id = ? 
                            ORDER BY sem.semester_no");
    $stmt->execute([$subject['course_id']]);
    $current_semesters = $stmt->fetchAll();

}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-pencil-square fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Edit Subject</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Update subject details in the curriculum</p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Department</label>
                            <select class="form-select border-0 shadow-sm p-3 bg-light" id="dept_id" disabled>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['dept_id']; ?>" <?php echo ($d['dept_id'] == $subject['dept_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-muted small text-uppercase">Course</label>
                            <select class="form-select border-0 shadow-sm p-3 bg-light" id="course_id" disabled>
                                <?php foreach ($current_courses as $c): ?>
                                    <option value="<?php echo $c['course_id']; ?>" <?php echo ($c['course_id'] == $subject['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_name'] . " (Shift " . $c['shift'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Semester</label>
                            <select class="form-select border-0 shadow-sm p-3 bg-light" id="course_sem_id" disabled>
                                <?php foreach ($current_semesters as $sem): ?>
                                    <option value="<?php echo $sem['course_sem_id']; ?>" <?php echo ($sem['course_sem_id'] == $subject['course_sem_id']) ? 'selected' : ''; ?>>
                                        Semester <?php echo $sem['semester_no']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 text-start">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-3">Subject Code</label>
                            <input type="text" class="form-control border-0 shadow-sm p-3 px-4 rounded-pill" name="sub_code" value="<?php echo htmlspecialchars($subject['sub_code']); ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-3">Subject Name</label>
                            <input type="text" class="form-control border-0 shadow-sm p-3 px-4 rounded-pill" name="sub_name" value="<?php echo htmlspecialchars($subject['sub_name']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-5 text-start">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase ms-3">Subject Type</label>
                                <select class="form-select border-0 shadow-sm p-3 px-4 rounded-pill" name="type_id" required>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?php echo $t['type_id']; ?>" <?php echo($t['type_id'] == $subject['type_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase ms-3">Status</label>
                                <select class="form-select border-0 shadow-sm p-3 px-4 rounded-pill" name="status">
                                    <option value="1" <?php echo $subject['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $subject['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary fw-bold p-3 px-5 rounded-pill flex-fill">Update Subject</button>
                        <a href="list.php" class="btn btn-outline-secondary p-3 px-5 rounded-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Course/Sem selection is disabled on edit to maintain data integrity -->

<?php require_once '../../includes/footer.php'; ?>
