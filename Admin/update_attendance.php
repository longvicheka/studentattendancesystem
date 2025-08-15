<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for JSON response
header('Content-Type: application/json');

// Start session and include database connection
session_start();
include '../Includes/db.php';

// Function to send JSON response
function sendResponse($success, $message = '', $data = null)
{
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if (!$success) {
        $response['error'] = $message;
    }

    echo json_encode($response);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Get and validate input data
$studentId = isset($_POST['studentId']) ? trim($_POST['studentId']) : '';
$subjectCode = isset($_POST['subjectCode']) ? trim($_POST['subjectCode']) : '';
$sessionId = isset($_POST['sessionId']) ? (int) $_POST['sessionId'] : 0;
$isAbsent = isset($_POST['isAbsent']) ? (int) $_POST['isAbsent'] : 0;
$date = isset($_POST['date']) ? trim($_POST['date']) : '';

// Validation
if (empty($studentId)) {
    sendResponse(false, 'Student ID is required');
}

if (empty($subjectCode)) {
    sendResponse(false, 'Subject code is required');
}

if ($sessionId < 1 || $sessionId > 3) {
    sendResponse(false, 'Invalid session ID. Must be 1, 2, or 3');
}

if (empty($date) || !DateTime::createFromFormat('Y-m-d', $date)) {
    sendResponse(false, 'Valid date is required (YYYY-MM-DD format)');
}

// Validate that isAbsent is either 0 or 1
if ($isAbsent !== 0 && $isAbsent !== 1) {
    sendResponse(false, 'Invalid attendance status');
}

// Set timezone
date_default_timezone_set('Asia/Phnom_Penh');

try {
    // Check if student exists and is active
    $studentCheckQuery = "SELECT studentId FROM tblstudent WHERE studentId = ? AND isActive = 1";
    $studentStmt = $conn->prepare($studentCheckQuery);
    if (!$studentStmt) {
        throw new Exception('Database error: Failed to prepare student check query');
    }

    $studentStmt->bind_param("s", $studentId);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();

    if ($studentResult->num_rows === 0) {
        sendResponse(false, 'Student not found or inactive');
    }

    // Check if subject exists and is active
    $subjectCheckQuery = "SELECT subjectCode FROM tblsubject WHERE subjectCode = ? AND isActive = 1";
    $subjectStmt = $conn->prepare($subjectCheckQuery);
    if (!$subjectStmt) {
        throw new Exception('Database error: Failed to prepare subject check query');
    }

    $subjectStmt->bind_param("s", $subjectCode);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();

    if ($subjectResult->num_rows === 0) {
        sendResponse(false, 'Subject not found or inactive');
    }

    // Check if student is enrolled in the subject
    $enrollmentQuery = "SELECT * FROM tblstudentsubject WHERE studentId = ? AND subjectCode = ?";
    $enrollmentStmt = $conn->prepare($enrollmentQuery);
    if (!$enrollmentStmt) {
        throw new Exception('Database error: Failed to prepare enrollment check query');
    }

    $enrollmentStmt->bind_param("ss", $studentId, $subjectCode);
    $enrollmentStmt->execute();
    $enrollmentResult = $enrollmentStmt->get_result();

    if ($enrollmentResult->num_rows === 0) {
        sendResponse(false, 'Student is not enrolled in this subject');
    }

    // Determine attendance status
    $attendanceStatus = $isAbsent ? 'absent' : 'present';
    $currentDateTime = date('Y-m-d H:i:s');

    // FIXED: Check if attendance record already exists with correct parameter binding
    $checkQuery = "SELECT attendanceId, attendanceStatus FROM tblattendance 
                   WHERE studentId = ? AND subjectCode = ? AND sessionId = ? AND DATE(markedAt) = ?";
    $checkStmt = $conn->prepare($checkQuery);
    if (!$checkStmt) {
        throw new Exception('Database error: Failed to prepare attendance check query - ' . $conn->error);
    }

    // FIXED: Correct parameter binding - all should be strings except sessionId which is int
    $checkStmt->bind_param("ssis", $studentId, $subjectCode, $sessionId, $date);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing record
        $existingRecord = $checkResult->fetch_assoc();

        // FIXED: Update query with correct parameter binding and using record ID
        $updateQuery = "UPDATE tblattendance 
                        SET attendanceStatus = ?, markedAt = ? 
                        WHERE attendanceId = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            throw new Exception('Database error: Failed to prepare update query - ' . $conn->error);
        }

        // FIXED: Correct parameter binding for update
        $updateStmt->bind_param("ssi", $attendanceStatus, $currentDateTime, $existingRecord['attendanceId']);

        if (!$updateStmt->execute()) {
            throw new Exception('Database error: Failed to update attendance record - ' . $updateStmt->error);
        }

        if ($updateStmt->affected_rows === 0) {
            sendResponse(false, 'No attendance record was updated');
        }

        sendResponse(true, 'Attendance updated successfully', [
            'studentId' => $studentId,
            'subjectCode' => $subjectCode,
            'sessionId' => $sessionId,
            'status' => $attendanceStatus,
            'action' => 'updated',
            'previousStatus' => $existingRecord['attendanceStatus']
        ]);

    } else {
        // Insert new record
        $insertQuery = "INSERT INTO tblattendance (studentId, subjectCode, sessionId, attendanceStatus, markedAt) 
                        VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            throw new Exception('Database error: Failed to prepare insert query - ' . $conn->error);
        }

        $insertStmt->bind_param("ssiss", $studentId, $subjectCode, $sessionId, $attendanceStatus, $currentDateTime);

        if (!$insertStmt->execute()) {
            throw new Exception('Database error: Failed to insert attendance record - ' . $insertStmt->error);
        }

        sendResponse(true, 'Attendance recorded successfully', [
            'studentId' => $studentId,
            'subjectCode' => $subjectCode,
            'sessionId' => $sessionId,
            'status' => $attendanceStatus,
            'action' => 'inserted'
        ]);
    }

} catch (Exception $e) {
    error_log('Attendance Update Error: ' . $e->getMessage());
    sendResponse(false, 'System error: ' . $e->getMessage());
} catch (Error $e) {
    error_log('Attendance Update Fatal Error: ' . $e->getMessage());
    sendResponse(false, 'System error occurred. Please try again.');
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>