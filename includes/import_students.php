<?php
require_once 'auth.php';
require_once '../config/database.php';

// Verify access (HOD or Staff)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['HOD', 'Staff', 'Admin'])) {
    http_response_code(403);
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    
    // Check if batch is provided
    $batch_id = $_POST['batch_id'] ?? null;
    if (!$batch_id) {
        $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
        header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode("Error: Batch ID is required for import."));
        exit;
    }
    
    $file = $_FILES['import_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
        header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode("Error uploading file. code: " . $file['error']));
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
        header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode("Error: Only CSV files are allowed."));
        exit;
    }

    $handle = fopen($file['tmp_name'], "r");
    if ($handle === false) {
        $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
        header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode("Error: Cannot read CSV file."));
        exit;
    }

    // Read headers
    $headers = fgetcsv($handle, 1000, ",");
    if (!$headers) {
        $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
        header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode("Error: Invalid or empty CSV file."));
        exit;
    }
    
    // Identify required columns (case-insensitive mapping)
    $headerMap = [];
    foreach ($headers as $index => $col) {
        $headerMap[trim(strtolower($col))] = $index;
    }
    
    $required_cols = ['register no', 'student name', 'mobile no'];
    foreach ($required_cols as $req) {
        if (!isset($headerMap[$req])) {
            $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
            header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode("Error: Missing required column '$req'."));
            exit;
        }
    }

    $success_count = 0;
    $error_count = 0;
    
    try {
        $conn->beginTransaction();

        $stmtUser = $conn->prepare("INSERT INTO Users (username, password_hash, role, status) VALUES (?, ?, 'Student', 1)");
        $stmtStudent = $conn->prepare("INSERT INTO Student (user_id, register_no, batch_id, student_name, gender, dob, email, mobile_no, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmtSem = $conn->prepare("INSERT INTO Student_Semester (student_id, semester_id, academic_year, is_active) VALUES (?, 1, ?, 1)");

        $academic_year = date('Y');

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // Skip empty rows
            if (empty(array_filter($data))) continue;

            $reg_no = trim($data[$headerMap['register no']]);
            $name = trim($data[$headerMap['student name']]);
            $mobile = trim($data[$headerMap['mobile no']]);
            $genderRaw = isset($headerMap['gender']) && isset($data[$headerMap['gender']]) ? trim(ucfirst(strtolower($data[$headerMap['gender']]))) : 'Male';
            $gender = in_array($genderRaw, ['Male', 'Female', 'Other']) ? $genderRaw : 'Male';
            $email = isset($headerMap['email']) && isset($data[$headerMap['email']]) ? trim($data[$headerMap['email']]) : null;
            $dobRaw = isset($headerMap['dob']) && isset($data[$headerMap['dob']]) ? trim($data[$headerMap['dob']]) : null;
            $address = isset($headerMap['address']) && isset($data[$headerMap['address']]) ? trim($data[$headerMap['address']]) : null;
            
            if (empty($reg_no) || empty($name)) {
                $error_count++;
                continue;
            }

            // Format DOB
            $dob = null;
            $password_plain = $mobile ?: '12345678'; // fallback
            if ($dobRaw) {
                $time = strtotime($dobRaw);
                if ($time !== false) {
                    $dob = date('Y-m-d', $time);
                    $password_plain = date('dmY', $time);
                }
            }

            // Check if register_no already exists in Student
            $checkStmt = $conn->prepare("SELECT student_id FROM Student WHERE register_no = ?");
            $checkStmt->execute([$reg_no]);
            if ($checkStmt->fetch()) {
                $error_count++;
                continue; // Skip duplicate
            }

            // Also check if username exists in Users
            $checkUserStmt = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
            $checkUserStmt->execute([$reg_no]);
            if ($checkUserStmt->fetch()) {
                $error_count++;
                continue; // Skip duplicate username
            }

            $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

            try {
                // 1. Insert User
                $stmtUser->execute([$reg_no, $password_hash]);
                $user_id = $conn->lastInsertId();

                // 2. Insert Student
                $stmtStudent->execute([$user_id, $reg_no, $batch_id, $name, $gender, $dob, $email, $mobile, $address]);
                $student_id = $conn->lastInsertId();

                // 3. Insert Student_Semester (Default Semester 1)
                $stmtSem->execute([$student_id, $academic_year]);

                $success_count++;
            } catch (PDOException $ex) {
                // Log and continue
                error_log("Import Error for Reg No $reg_no: " . $ex->getMessage());
                $error_count++;
            }
        }

        $conn->commit();
        fclose($handle);

        $msg = "Import Complete. Success: $success_count, Errors/Skipped: $error_count.";
        $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
        header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode($msg));
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        fclose($handle);
        $redirect = $_SERVER['HTTP_REFERER'] ?: '../index.php';
        header("Location: $redirect" . (strpos($redirect, '?') !== false ? '&' : '?') . "msg=" . urlencode("Database error during import."));
        exit;
    }
}
