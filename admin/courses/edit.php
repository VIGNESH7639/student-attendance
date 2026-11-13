<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $dept_id = (int)$_POST['dept_id'];
    $shift = (int)$_POST['shift'];
    $type = $_POST['type'];
    $status = (int)$_POST['status'];

    if (empty($name)) {
        $error = "Course name is required.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE Course SET course_name = ?, dept_id = ?, shift = ?, course_type = ?, status = ? WHERE course_id = ?");
            $stmt->execute([$name, $dept_id, $shift, $type, $status, $id]);
            header("Location: list.php?msg=Course updated successfully.");
            exit();
        } catch (PDOException $e) {
            $error = "Error updating course: " . $e->getMessage();
        }
    }
}

try {
    $stmt = $conn->prepare("SELECT * FROM Course WHERE course_id = ?");
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    if (!$course) {
        header("Location: list.php?error=Course not found.");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$depts = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-pencil-square fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Edit Course</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Update course details and settings</p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="ex: B.Sc, M.Sc, BBA, BCA" value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="dept_id" class="form-label">Department</label>
                        <select class="form-select" id="dept_id" name="dept_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($depts as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>" <?php echo $course['dept_id'] == $dept['dept_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="shift" class="form-label">Shift</label>
                            <select class="form-select" id="shift" name="shift">
                                <option value="1" <?php echo $course['shift'] == 1 ? 'selected' : ''; ?>>Shift 1</option>
                                <option value="2" <?php echo $course['shift'] == 2 ? 'selected' : ''; ?>>Shift 2</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="UG" <?php echo $course['course_type'] == 'UG' ? 'selected' : ''; ?>>UG</option>
                                <option value="PG" <?php echo $course['course_type'] == 'PG' ? 'selected' : ''; ?>>PG</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="1" <?php echo $course['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $course['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Update</button>
                        <a href="list.php" class="btn btn-outline-secondary px-4 fw-bold">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
