<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = (int)$_GET['id'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_semesters = isset($_POST['semesters']) ? $_POST['semesters'] : [];
    
    try {
        $conn->beginTransaction();
        
        // 1. Remove existing assignments for this course
        $stmt = $conn->prepare("DELETE FROM Course_Semester WHERE course_id = ?");
        $stmt->execute([$id]);
        
        // 2. Insert new assignments
        if (!empty($selected_semesters)) {
            $stmt = $conn->prepare("INSERT INTO Course_Semester (course_id, semester_id) VALUES (?, ?)");
            foreach ($selected_semesters as $sem_id) {
                $stmt->execute([$id, $sem_id]);
            }
        }
        
        $conn->commit();
        header("Location: list.php?msg=Semesters assigned successfully for the course.");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error updating semesters: " . $e->getMessage();
    }
}

// Fetch Course Details
try {
    $stmt = $conn->prepare("SELECT c.*, d.dept_name FROM Course c JOIN Department d ON c.dept_id = d.dept_id WHERE c.course_id = ?");
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    if (!$course) {
        header("Location: list.php?error=Course not found.");
        exit();
    }

    // Fetch All Semesters
    $all_semesters = $conn->query("SELECT * FROM Semester ORDER BY semester_no ASC")->fetchAll();

    // Fetch Currently Assigned Semesters
    $stmt = $conn->prepare("SELECT semester_id FROM Course_Semester WHERE course_id = ?");
    $stmt->execute([$id]);
    $assigned_semesters = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mt-4 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4 text-primary">
                    <i class="bi bi-calendar-check fs-3 me-2"></i>
                    <h3 class="fw-bold mb-0">Assign Semesters</h3>
                </div>

                <div class="mb-4 p-3 bg-light rounded-3 border-start border-primary border-4">
                    <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                    <p class="text-muted mb-0">
                        <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($course['dept_name']); ?></span>
                        <span class="badge bg-info"><?php echo $course['course_type']; ?></span>
                        <span class="badge bg-dark"><?php echo $course['shift'] == 1 ? 'Shift 1' : 'Shift 2'; ?></span>
                    </p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="form-label fw-bold mb-3">Select Applicable Semesters:</label>
                    <div class="row g-3 mb-4">
                        <?php foreach ($all_semesters as $sem): ?>
                            <div class="col-md-4 col-6">
                                <div class="form-check p-3 border rounded-3 hover-shadow-sm transition-all h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="semesters[]" 
                                           value="<?php echo $sem['semester_id']; ?>" 
                                           id="sem_<?php echo $sem['semester_id']; ?>"
                                           <?php echo in_array($sem['semester_id'], $assigned_semesters) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-medium cursor-pointer" for="sem_<?php echo $sem['semester_id']; ?>">
                                        Semester <?php echo $sem['semester_no']; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4 flex-fill py-2">Save Assignments</button>
                        <a href="list.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-white rounded-3 shadow-sm border-start border-info border-4">
            <p class="mb-0 small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Assigning semesters allows you to map subjects to specific semesters for this course. 
                Typically, UG courses have 6 semesters and PG courses have 4 semesters.
            </p>
        </div>
    </div>
</div>

<style>
.hover-shadow-sm:hover {
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    border-color: #0d6efd!important;
    background-color: #f8fbff;
}
.transition-all {
    transition: all 0.2s ease-in-out;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<?php require_once '../../includes/footer.php'; ?>
