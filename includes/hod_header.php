<?php
@session_start();
$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['role']    ?? null;

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
    <title>HOD Dashboard - Student Apt</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Outfit for headings, Inter for body -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/college_erp/css/style.css">
    <style>
        /* Local Override for Navigation active states to maintain premium feel */
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
    </style>
</head>
<body>

<?php
// Role-based home URL
$home_url = '/college_erp/hod/dashboard.php';
?>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">
        <button class="btn btn-light border-0 me-3 rounded-circle shadow-sm" id="sidebarToggle" style="width: 45px; height: 45px;">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $home_url; ?>">
            <div class="bg-primary rounded-3 p-1 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                <i class="bi bi-mortarboard-fill text-white fs-5"></i>
            </div>
            <span class="font-heading fw-bold text-dark" style="letter-spacing: -0.5px;">STUDENT APT <span class="text-primary">HOD</span></span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <a href="/college_erp/hod/profile.php" class="text-decoration-none d-none d-md-flex align-items-center me-2 user-profile-link">
                <div class="text-end me-3">
                    <div class="small fw-bold text-dark mb-0"><?php echo htmlspecialchars($profile_name); ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">Department Head</div>
                </div>
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
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
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="/college_erp/hod/dashboard.php">
                        <i class="bi bi-grid-1x2-fill me-3"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/staff/') !== false) ? 'active' : ''; ?>" href="/college_erp/hod/staff/list.php">
                        <i class="bi bi-people-fill me-3"></i> Manage Staff
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/batches/') !== false) ? 'active' : ''; ?>" href="/college_erp/hod/batches/list.php">
                        <i class="bi bi-layers-fill me-3"></i> Manage Batches
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/subjects/') !== false) ? 'active' : ''; ?>" href="/college_erp/hod/subjects/list.php">
                        <i class="bi bi-book-half me-3"></i> Manage Subjects
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/allocation/') !== false) ? 'active' : ''; ?>" href="/college_erp/hod/allocation/list.php">
                        <i class="bi bi-person-check-fill me-3"></i> Staff Allocation
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/tutors/') !== false) ? 'active' : ''; ?>" href="/college_erp/hod/tutors/list.php">
                        <i class="bi bi-mortarboard-fill me-3"></i> Tutor Assignment
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'students/list.php') !== false) ? 'active' : ''; ?>" href="/college_erp/hod/students/list.php">
                        <i class="bi bi-person-video2 me-3"></i> Students
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/reports/') !== false) ? 'active' : ''; ?>" href="/college_erp/hod/reports/index.php">
                        <i class="bi bi-file-earmark-bar-graph-fill me-3"></i> Reports
                    </a>
                </li>

                <?php
                if (isset($_SESSION['staff_id'])) {
                    require_once $_SERVER['DOCUMENT_ROOT'] . '/college_erp/config/database.php';
                    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Staff_Subject WHERE staff_id = ? AND status = 1");
                    $checkStmt->execute([$_SESSION['staff_id']]);
                    if ($checkStmt->fetchColumn() > 0) {
                ?>
                        <li class="nav-item mt-4 px-4 mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing: 1px;">Teaching Panel</li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'staff/') !== false ? 'active' : ''; ?>" href="/college_erp/staff/dashboard.php">
                                <i class="bi bi-grid-1x2-fill me-3"></i> Teacher Dashboard
                            </a>
                        </li>
                          
<?php
                    }
                }

                if (isset($_SESSION['staff_id'])) {
                    $mentorStmt = $conn->prepare("SELECT COUNT(*) FROM Tutor WHERE staff_id = ? AND status = 1");
                    $mentorStmt->execute([$_SESSION['staff_id']]);
                    if ($mentorStmt->fetchColumn() > 0) {
                ?>
                        <li class="nav-item mt-3 px-4 mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing: 1px;">Tutor Services</li>
                        <li class="nav-item mb-1">
                            <a class="nav-link" href="/college_erp/mentor/dashboard.php">
                                <i class="bi bi-shield-check-fill me-3"></i> Tutor Hub
                            </a>
                        </li>
                <?php
                    }
                }
                ?>
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
            $current_page = basename($_SERVER['PHP_SELF']);
            $current_dir = basename(dirname($_SERVER['PHP_SELF']));

            if ($current_page !== 'dashboard.php'):
                $back_url = '/college_erp/hod/dashboard.php';
                if ($current_page !== 'list.php' && ($current_dir === 'staff' || $current_dir === 'students' || $current_dir === 'batches' || $current_dir === 'subjects' || $current_dir === 'allocation' || $current_dir === 'tutors')) {
                    $back_url = 'list.php';
                } elseif ($current_page !== 'index.php' && $current_dir === 'reports') {
                    $back_url = 'index.php';
                }
            ?>
                <div class="mb-4">
                    <button onclick="location.href='<?php echo $back_url; ?>'" class="btn btn-light shadow-sm btn-sm px-3 py-2 rounded-pill border-0 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i> <strong>Back</strong>
                    </button>
                </div>
            <?php endif; ?>

