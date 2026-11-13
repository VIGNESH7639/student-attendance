<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

try {
    // Fetch Department Name
    $stmt = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
    $stmt->execute([$dept_id]);
    $dept_name = $stmt->fetchColumn();

    // Fetch all subjects grouped by Course and Semester
    $query = "SELECT s.*, st.type_name, c.course_name, c.shift, sem.semester_no 
             FROM Subject s 
             JOIN Subject_Type st ON s.type_id = st.type_id
             JOIN Course_Semester cs ON s.course_sem_id = cs.course_sem_id
             JOIN Course c ON cs.course_id = c.course_id
             JOIN Semester sem ON cs.semester_id = sem.semester_id
             WHERE c.dept_id = ?
             ORDER BY c.course_name, c.shift, sem.semester_no, s.sub_name";
    $stmt = $conn->prepare($query);
    $stmt->execute([$dept_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "Department_Subject_Summary_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S.No', 'Course', 'Shift', 'Semester', 'Sub Code', 'Subject Name', 'Type', 'Status']);
    
    $i = 1;
    foreach ($subjects as $s) {
        fputcsv($output, [
            $i++,
            $s['course_name'],
            "Shift " . $s['shift'],
            "Semester " . $s['semester_no'],
            $s['sub_code'],
            $s['sub_name'],
            $s['type_name'],
            $s['status'] == 1 ? 'Active' : 'Deactive'
        ]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/hod_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Subject Summary Report</h2>
        <p class="text-muted mb-0 small"><?php echo htmlspecialchars($dept_name); ?> Department</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold border-2 d-print-none">
            <i class="bi bi-printer me-2"></i> Print Report
        </button>
        <a href="?export=csv" class="btn btn-outline-success rounded-pill px-4 shadow-sm fw-bold border-2 d-print-none">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i> Export CSV
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Course & Semester</th>
                    <th class="py-3">Subject Details</th>
                    <th class="py-3 text-center">Type</th>
                    <th class="pe-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No subjects defined in this department.</td></tr>
                <?php else: ?>
                    <?php 
                    $current_group = "";
                    $sno = 1; 
                    foreach ($subjects as $s): 
                        $group_name = $s['course_name'] . " (Shift " . $s['shift'] . ") - Semester " . $s['semester_no'];
                        if ($current_group !== $group_name):
                            $current_group = $group_name;
                    ?>
                        <tr class="bg-primary bg-opacity-10">
                            <td colspan="5" class="ps-4 py-2 fw-bold text-primary small text-uppercase letter-spacing-1">
                                <i class="bi bi-journal-bookmark-fill me-2"></i> <?php echo htmlspecialchars($group_name); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                        <tr>
                            <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                            <td>
                                <div class="fw-bold text-dark small text-uppercase"><?php echo htmlspecialchars($s['course_name']); ?></div>
                                <div class="text-muted extra-small">Shift <?php echo $s['shift']; ?> • Sem <?php echo $s['semester_no']; ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['sub_name']); ?></div>
                                <code class="text-primary small fw-bold"><?php echo htmlspecialchars($s['sub_code']); ?></code>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-white text-dark border px-3 py-2 shadow-xs fw-bold rounded-pill"><?php echo htmlspecialchars($s['type_name']); ?></span>
                            </td>
                            <td class="pe-4 text-center">
                                <span class="badge rounded-pill px-3 <?php echo $s['status'] == 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                    <?php echo $s['status'] == 1 ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.extra-small { font-size: 0.75rem; }
.letter-spacing-1 { letter-spacing: 0.5px; }
@media print {
    body { background: white !important; }
    .app-container { margin-left: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #eee !important; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
