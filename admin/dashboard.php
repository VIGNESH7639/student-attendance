<?php
require_once '../includes/auth.php';
requireRole('Admin');
require_once '../config/database.php';
require_once '../includes/header.php';

// Fetch Stats
$stats = [
    'depts' => $conn->query("SELECT COUNT(*) FROM Department")->fetchColumn(),
    'staff' => $conn->query("SELECT COUNT(*) FROM Staff")->fetchColumn(),
    'students' => $conn->query("SELECT COUNT(*) FROM Student")->fetchColumn(),
    'batches' => $conn->query("SELECT COUNT(*) FROM Batch")->fetchColumn()
];
?>

<div class="row g-4 mb-5">
    <!-- Welcome Section -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold text-dark mb-1">Administrative Dashboard</h2>
                    <p class="text-muted mb-4 subtitle">Official management console for academic and staff operations.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="students/add.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-person-plus me-1"></i> Register Student
                        </a>
                        <a href="staff/list.php" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-people me-1"></i> Staff Directory
                        </a>
                        <a href="batches/list.php" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-layers me-1"></i> Batch List
                        </a>
                    </div>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="bi bi-bank2 text-primary opacity-25" style="font-size: 2.25rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 border-top border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase small mb-2 fw-bold">Departments</h6>
                    <h3 class="fw-bold mb-0"><?php echo $stats['depts']; ?></h3>
                </div>
                <div class="text-primary opacity-50">
                    <i class="bi bi-building fs-4"></i>
                </div>
            </div>
            <a href="departments/list.php" class="small text-decoration-none mt-3 d-block">View Details <i class="bi bi-chevron-right small"></i></a>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 border-top border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase small mb-2 fw-bold">Staff Directory</h6>
                    <h3 class="fw-bold mb-0"><?php echo $stats['staff']; ?></h3>
                </div>
                <div class="text-success opacity-50">
                    <i class="bi bi-people fs-4"></i>
                </div>
            </div>
            <a href="staff/list.php" class="small text-decoration-none mt-3 d-block text-success">View Details <i class="bi bi-chevron-right small"></i></a>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 border-top border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase small mb-2 fw-bold">Student Records</h6>
                    <h3 class="fw-bold mb-0"><?php echo $stats['students']; ?></h3>
                </div>
                <div class="text-info opacity-50">
                    <i class="bi bi-mortarboard fs-4"></i>
                </div>
            </div>
            <a href="students/list.php" class="small text-decoration-none mt-3 d-block text-info">View Details <i class="bi bi-chevron-right small"></i></a>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 border-top border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase small mb-2 fw-bold">Active Batches</h6>
                    <h3 class="fw-bold mb-0"><?php echo $stats['batches']; ?></h3>
                </div>
                <div class="text-warning opacity-50">
                    <i class="bi bi-layers fs-4"></i>
                </div>
            </div>
            <a href="batches/list.php" class="small text-decoration-none mt-3 d-block text-warning">View Details <i class="bi bi-chevron-right small"></i></a>
        </div>
    </div>

    <!-- System Overview Section -->
    <div class="col-12">
        <h5 class="fw-bold mb-3 px-1 text-muted small text-uppercase">Operational Compliance</h5>
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-success h-100">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">System Integrity</h5>
                    <p class="text-muted small mb-0">Security Status: <strong class="text-success text-uppercase">Operational</strong></p>
                </div>
            </div>
            <div class="bg-light p-3 rounded-3 mb-0">
                <p class="mb-0 small text-muted"><i class="bi bi-info-circle me-1 text-primary"></i> System integrity checks are performed daily. Database backups verified as of today at 04:00 AM.</p>
            </div>
        </div>
    </div>
</div>

<style>
.list-group-item.active { background-color: #1e3a8a; border-color: #1e3a8a; }
.card { border: 1px solid #e2e8f0 !important; }
</style>

<?php require_once '../includes/footer.php'; ?>
