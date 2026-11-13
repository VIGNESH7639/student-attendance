<?php
@session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/college_erp/includes/auth.php';
$user_id = $_SESSION['user_id'] ?? null;

// Fetch Profile Name
$profile_name = $_SESSION['username'] ?? 'User';
if ($user_id) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/college_erp/config/database.php';
    $nameStmt = $conn->prepare("SELECT staff_name FROM Staff WHERE user_id = ?");
    $nameStmt->execute([$user_id]);
    $nameRow = $nameStmt->fetch();
    if ($nameRow) {
        $profile_name = $nameRow['staff_name'] . ' (' . ($_SESSION['username'] ?? '') . ')';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard - Student Apt</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Outfit for headings, Inter for body -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/college_erp/css/style.css">
    <style>
        /* Premium nav link active state */
        .nav-link.active {
            background: var(--primary-gradient) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            border-radius: 12px;
        }
        .nav-link {
            border-radius: 12px;
            margin: 2px 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #64748b;
        }
        .nav-link:hover:not(.active) {
            background: #f1f5f9;
            color: #6366f1;
        }
        .nav-link i { font-size: 1.25rem; }

        /* Sub-menu styling */
        .nav-submenu {
            list-style: none;
            padding-left: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .nav-submenu .nav-link {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            margin: 1px 0;
            border-radius: 8px;
        }

        /* Success-themed accent for tutor */
        --tutor-color: #10b981;
    </style>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Role-based home URL for logo and dashboard link
$home_url = '/college_erp/staff/dashboard.php';
if (isHOD()) {
    $home_url = '/college_erp/hod/dashboard.php';
} elseif (isMentor()) {
    $home_url = '/college_erp/mentor/dashboard.php';
}
?>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">
        <button class="btn btn-light border-0 me-3 rounded-circle shadow-sm" id="sidebarToggle" style="width: 45px; height: 45px;">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $home_url; ?>">
            <div class="bg-success rounded-3 p-1 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                <i class="bi bi-person-check-fill text-white fs-5"></i>
            </div>
            <span class="font-heading fw-bold text-dark" style="letter-spacing: -0.5px;">STUDENT APT <span class="text-success">TUTOR</span></span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <a href="/college_erp/mentor/profile.php" class="text-decoration-none d-none d-md-flex align-items-center me-2 user-profile-link">
                <div class="text-end me-3">
                    <div class="small fw-bold text-dark mb-0"><?php echo htmlspecialchars($profile_name); ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">Class Tutor</div>
                </div>
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-person-fill fs-5"></i>
                </div>
            </a>
            <a class="btn btn-premium btn-premium-primary py-2" href="/college_erp/logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-md-inline">Logout</span>
            </a>
        </div>
    </div>
</nav>

<div class="app-container">
    <div class="overlay" id="sidebarOverlay"></div>

    <div class="sidebar-wrapper" id="sidebar">
        <nav class="h-100 d-flex flex-column py-3">
            <ul class="nav flex-column mb-auto">
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'staff') === false ? 'active' : ''; ?>" href="<?php echo $home_url; ?>">
                        <i class="bi bi-grid-1x2-fill me-3"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" href="/college_erp/mentor/students.php">
                        <i class="bi bi-people-fill me-3"></i> Batch Students
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>" href="/college_erp/mentor/attendance.php">
                        <i class="bi bi-calendar-check-fill me-3"></i> Attendance Overview
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'marks.php' ? 'active' : ''; ?>" href="/college_erp/mentor/marks.php">
                        <i class="bi bi-award-fill me-3"></i> Marks Overview
                    </a>
                </li>
               
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : ''; ?>" href="/college_erp/mentor/subjects.php">
                        <i class="bi bi-book-half me-3"></i> Semester Subjects
                    </a>
                </li>
               
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'promote.php' ? 'active' : ''; ?>" href="/college_erp/mentor/promote.php">
                        <i class="bi bi-arrow-up-circle-fill me-3"></i> Promote Batch
                    </a>
                </li>
                 <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'arrear_management.php') ? 'active' : ''; ?>" href="/college_erp/mentor/arrear_management.php">
                        <i class="bi bi-exclamation-triangle-fill me-3 text-danger"></i> Arrear Management
                    </a>
                </li>
                 <li class="nav-item mb-1">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="/college_erp/mentor/reports.php">
                        <i class="bi bi-file-earmark-bar-graph-fill me-3"></i> Reports
                    </a>
                </li>

                <li class="nav-item mt-4 px-4 mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing: 1px;">Teaching Panel</li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'staff') !== false ? 'active' : ''; ?>" href="/college_erp/staff/dashboard.php">
                        <i class="bi bi-person-workspace me-3"></i> Faculty Dashboard
                    </a>
                </li>
            </ul>
            <ul class="nav flex-column mt-auto mx-3 border-top pt-3">
                <li class="nav-item">
                    <a class="nav-link text-danger logout-link" href="/college_erp/logout.php" style="background: rgba(220, 53, 69, 0.05);">
                        <i class="bi bi-box-arrow-right me-3"></i> Logout Account
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="container-fluid p-3 p-md-4">
            <?php
            // ── Smart Navigation Origin ──────────────────────────────────────────
            $current_page = basename($_SERVER['PHP_SELF']);
            $nav_origin = $_SESSION['mentor_nav_origin'] ?? 'dashboard.php';

            // Pages that can be reached from multiple origins (Reports or Sidebar)
            $report_subpages = [
                'attendance_report.php', 'marks_report.php', 'internal_summary.php',
                'batch_student_report.php', 'daily_attendance.php', 'student_report.php',
                'subjects.php', 'marks.php', 'attendance.php', 'students.php',
                'student_attendance_details.php'
            ];

            // Default home URL
            $mentor_home = '/college_erp/mentor/dashboard.php';
            if (isHOD()) { $home_url = '/college_erp/hod/dashboard.php'; }
            elseif (isMentor()) { $home_url = $mentor_home; }
            else { $home_url = '/college_erp/staff/dashboard.php'; }

            $back_url = $home_url;
            $back_label = 'Dashboard';

            if ($current_page === 'reports.php') {
                $back_url = $home_url;
                $back_label = 'Dashboard';
            } elseif ($current_page === 'add_student.php' || $current_page === 'edit_student.php') {
                $back_url = '/college_erp/mentor/students.php';
                $back_label = 'Batch Students';
            } elseif (in_array($current_page, $report_subpages)) {
                // Return to the measured origin (Reports, Dashboard, etc.)
                $back_url = '/college_erp/mentor/' . $nav_origin;
                $back_label = ucwords(str_replace(['.php', '_'], ['', ' '], $nav_origin));
            } else {
                $back_url = $home_url;
                $back_label = 'Dashboard';
            }
            ?>
            <?php if ($current_page !== 'dashboard.php'): ?>
                <div class="mb-4">
                    <a href="<?php echo $back_url; ?>" class="btn btn-light shadow-sm btn-sm px-3 py-2 rounded-pill border-0 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i> <strong>Back to <?php echo $back_label; ?></strong>
                    </a>
                </div>
            <?php endif; ?>

