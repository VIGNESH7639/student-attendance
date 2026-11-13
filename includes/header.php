<?php
@session_start();
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

// Unified Profile Name logic
$profile_name = $_SESSION['username'] ?? 'User';
if ($user_id) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/college_erp/config/database.php';
    if ($role === 'Student') {
        $stmt = $conn->prepare("SELECT student_name FROM Student WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $res = $stmt->fetch();
        if ($res) $profile_name = $res['student_name'] . ' (' . $_SESSION['username'] . ')';
    } elseif ($role === 'Staff' || $role === 'HOD') {
        $stmt = $conn->prepare("SELECT staff_name FROM Staff WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $res = $stmt->fetch();
        if ($res) $profile_name = $res['staff_name'] . ' (' . $_SESSION['username'] . ')';
    } elseif ($role === 'Admin') {
        $profile_name = 'Admin (' . $_SESSION['username'] . ')';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Apt Portal</title>
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
        .profile-trigger:hover { transform: translateY(-2px); opacity: 0.8 !important; }
        .transition-all { transition: all 0.2s ease-in-out; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">
        <?php if ($user_id): ?>
            <button class="btn btn-light border-0 me-3 rounded-circle shadow-sm" id="sidebarToggle" style="width: 45px; height: 45px;">
                <i class="bi bi-list fs-4"></i>
            </button>
        <?php endif; ?>
        
        <a class="navbar-brand d-flex align-items-center" href="/college_erp/index.php">
            <div class="bg-primary rounded-3 p-1 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                <i class="bi bi-mortarboard-fill text-white fs-5"></i>
            </div>
            <span class="font-heading fw-bold text-dark" style="letter-spacing: -0.5px;">STUDENT APT <span class="text-primary">PORTAL</span></span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <?php if ($user_id): ?>
                <?php 
                    $profile_link = '#';
                    if ($role === 'Student') $profile_link = '/college_erp/student/profile.php';
                    else if ($role === 'Staff') $profile_link = '/college_erp/staff/profile.php';
                    else if ($role === 'HOD') $profile_link = '/college_erp/hod/profile.php';
                ?>
                <a href="<?php echo $profile_link; ?>" class="d-none d-md-flex align-items-center me-2 text-decoration-none transition-all profile-trigger">
                    <div class="text-end me-3">
                        <div class="small fw-bold text-dark mb-0"><?php echo htmlspecialchars($profile_name); ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo $role; ?> Account</div>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center shadow-sm-hover" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill fs-5"></i>
                    </div>
                </a>
                <a class="btn btn-premium btn-premium-primary py-2" href="/college_erp/logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-md-inline">Logout</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="app-container">
    <?php if ($user_id): ?>
    <div class="overlay" id="sidebarOverlay"></div>
    
    <div class="sidebar-wrapper" id="sidebar">
        <nav class="h-100 d-flex flex-column py-3">
            <ul class="nav flex-column mb-auto">
                <?php if ($is_admin): ?>
                    <!-- Admin Sidebar -->
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>" href="/college_erp/admin/dashboard.php">
                            <i class="bi bi-grid-1x2-fill me-3"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/departments/') !== false ? 'active' : ''; ?>" href="/college_erp/admin/departments/list.php">
                            <i class="bi bi-building-fill me-3"></i> Departments
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/courses/') !== false ? 'active' : ''; ?>" href="/college_erp/admin/courses/list.php">
                            <i class="bi bi-book-half me-3"></i> Courses
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/batches/') !== false ? 'active' : ''; ?>" href="/college_erp/admin/batches/list.php">
                            <i class="bi bi-layers-fill me-3"></i> Batches
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/subjects/') !== false ? 'active' : ''; ?>" href="/college_erp/admin/subjects/list.php">
                            <i class="bi bi-journal-check me-3"></i> Subjects
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/staff/') !== false ? 'active' : ''; ?>" href="/college_erp/admin/staff/list.php">
                            <i class="bi bi-person-workspace me-3"></i> Staff Profiles
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/students/') !== false ? 'active' : ''; ?>" href="/college_erp/admin/students/list.php">
                            <i class="bi bi-people-fill me-3"></i> Students List
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>" href="/college_erp/admin/reports/index.php">
                            <i class="bi bi-file-earmark-bar-graph-fill me-3"></i> Reports
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Staff/Student/HOD Sidebar -->
                    <?php 
                        $dash_url = '/college_erp/index.php';
                        if ($role === 'Student') $dash_url = '/college_erp/student/dashboard.php';
                        else if ($role === 'Staff') $dash_url = '/college_erp/staff/dashboard.php';
                        else if ($role === 'HOD') $dash_url = '/college_erp/hod/dashboard.php';
                    ?>
                    <li class="nav-item mb-1">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'mentor/') === false) ? 'active' : ''; ?>" href="<?php echo $dash_url; ?>">
                            <i class="bi bi-grid-1x2-fill me-3"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($role === 'Staff' || $role === 'HOD'): ?>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'profile.php') !== false ? 'active' : ''; ?>" href="/college_erp/staff/profile.php">
                                <i class="bi bi-person-badge-fill me-3"></i> My Profile
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/marks/') !== false ? 'active' : ''; ?>" href="/college_erp/staff/marks/list.php">
                                <i class="bi bi-pencil-square me-3"></i> Marks Entry
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/attendance/') !== false && strpos($_SERVER['PHP_SELF'], 'mentor/') === false) ? 'active' : ''; ?>" href="/college_erp/staff/attendance/list.php">
                                <i class="bi bi-calendar-check-fill me-3"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item mb-1 text-uppercase small fw-bold text-muted px-4 mt-3 mb-1">Reports</li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/reports/') !== false && strpos($_SERVER['PHP_SELF'], 'mentor/') === false) ? 'active' : ''; ?>" href="/college_erp/staff/reports/index.php">
                                <i class="bi bi-calendar-check-fill me-3"></i> Subject Attendance
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/reports/') !== false && strpos($_SERVER['PHP_SELF'], 'mentor/') === false) ? 'active' : ''; ?>" href="/college_erp/staff/reports/index.php">
                                <i class="bi bi-file-earmark-diff-fill me-3"></i> Internal Summary
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/reports/') !== false && strpos($_SERVER['PHP_SELF'], 'mentor/') === false) ? 'active' : ''; ?>" href="/college_erp/staff/reports/index.php">
                                <i class="bi bi-file-earmark-bar-graph-fill me-3"></i> Final Marks List
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($role === 'Student'): ?>
                        
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'subjects.php') !== false ? 'active' : ''; ?>" href="/college_erp/student/subjects.php">
                                <i class="bi bi-journal-text me-3"></i> My Subjects
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'history.php') !== false ? 'active' : ''; ?>" href="/college_erp/student/history.php">
                                <i class="bi bi-clock-history me-3"></i> Academic History
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'arrears.php') !== false ? 'active' : ''; ?>" href="/college_erp/student/arrears.php">
                                <i class="bi bi-exclamation-triangle-fill me-3 text-warning"></i> Arrear Subjects
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role === 'Staff' || $role === 'HOD'): ?>
                        <?php 
                            require_once $_SERVER['DOCUMENT_ROOT'] . '/college_erp/config/database.php';
                            $tutorStmt = $conn->prepare("SELECT tutor_id FROM Tutor WHERE staff_id = (SELECT staff_id FROM Staff WHERE user_id = ?) AND status = 1");
                            $tutorStmt->execute([$user_id]);
                            $tutor_data = $tutorStmt->fetch();
                        ?>
                        <?php if ($tutor_data): ?>
                            <li class="nav-item mt-4 px-4 mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing: 1px;">Tutor Services</li>
                            <li class="nav-item mb-1">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'mentor/') !== false ? 'active' : ''; ?>" href="/college_erp/mentor/dashboard.php">
                                    <i class="bi bi-shield-check-fill me-3"></i> Tutor Hub
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" href="/college_erp/mentor/students.php">
                                    <i class="bi bi-people-fill me-3"></i> Student Roster
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'daily_attendance.php' ? 'active' : ''; ?>" href="/college_erp/mentor/daily_attendance.php">
                                    <i class="bi bi-calendar-check-fill me-3"></i> Attendance Lab
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="/college_erp/mentor/reports.php">
                                    <i class="bi bi-file-bar-graph-fill me-3"></i> Insights & Reports
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
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
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="container-fluid p-3 p-md-4">
            <?php if ($user_id && basename($_SERVER['PHP_SELF']) !== 'dashboard.php'): ?>
                <div class="mb-4">
                    <button onclick="history.back()" class="btn btn-light shadow-sm btn-sm px-3 py-2 rounded-pill border-0 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i> <strong>Back</strong>
                    </button>
                </div>
            <?php endif; ?>
