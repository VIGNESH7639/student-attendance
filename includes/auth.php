<?php
session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isHOD()
{
    return isset($_SESSION['is_hod']) && $_SESSION['is_hod'];
}

function isMentor()
{
    return isset($_SESSION['is_mentor']) && $_SESSION['is_mentor'];
}

function redirectIfLoggedIn()
{
    if (isLoggedIn()) {
        if (isHOD()) {
            header("Location: /college_erp/hod/dashboard.php");
        }
        else if (isset($_SESSION['role'])) {
            switch ($_SESSION['role']) {
                case 'Admin':
                    header("Location: /college_erp/admin/dashboard.php");
                    break;
                case 'Staff':
                    if (isMentor()) {
                        header("Location: /college_erp/mentor/dashboard.php");
                    }
                    else {
                        header("Location: /college_erp/staff/dashboard.php");
                    }
                    break;
                case 'Student':
                    header("Location: /college_erp/student/dashboard.php");
                    break;
            }
        }
        exit();
    }
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: /college_erp/login.php");
        exit();
    }
}

function requireRole($role)
{
    requireLogin();
    $current_role = $_SESSION['role'] ?? '';

    // Logic: Admin can access everything, HOD can access Staff pages
    if ($current_role === 'Admin')
        return;
    if ($current_role === $role)
        return;
    if ($current_role === 'HOD' && $role === 'Staff')
        return;

    die("Access Denied: You do not have the required permissions.");
}

function requireHOD()
{
    requireLogin();
    if (!isHOD()) {
        die("Access Denied: HOD access required.");
    }
}

function requireAdminOrHOD()
{
    requireLogin();
    if (hasRole('Admin') || isHOD()) {
        return;
    }
    die("Access Denied: You do not have the required permissions.");
}

function requireHODorTutor()
{
    requireLogin();
    if (isHOD() || isMentor() || hasRole('Admin')) {
        return;
    }
    die("Access Denied: HOD or Tutor access required.");
}

function requireMentor()
{
    requireLogin();
    if (isMentor() || hasRole('Admin')) {
        return;
    }
    die("Access Denied: Tutor access required.");
}
?>
