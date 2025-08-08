<?php
// CRITICAL: No output before this point - no echo, no HTML, no whitespace
// Clean output buffer to prevent any stray HTML
if (ob_get_level()) {
    ob_end_clean();
}

// Set JSON header first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Disable error display to prevent HTML in response
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo '{"success":false,"message":"Method not allowed"}';
        exit;
    }

    // Include database connection
    if (!file_exists('../Includes/db.php')) {
        echo '{"success":false,"message":"Database config not found"}';
        exit;
    }

    include '../Includes/db.php';

    // Check database connection
    if (!isset($conn) || !$conn) {
        echo '{"success":false,"message":"Database connection failed"}';
        exit;
    }

    // Validate input
    $studentId = isset($_POST['studentId']) ? intval($_POST['studentId']) : 0;
    $sessionId = isset($_POST['sessionId']) ? intval($_POST['sessionId']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($studentId <= 0) {
        echo '{"success":false,"message":"Invalid student ID"}';
        exit;
    }

    if (!in_array($sessionId, [1, 2, 3])) {
        echo '{"success":false,"message":"Invalid session ID"}';
        exit;
    }

    if (!in_array($status, ['present', 'absent'])) {
        echo '{"success":false,"message":"Invalid status"}';
        exit;
    }

    // Find the record using CURDATE() 
    $findSQL = "SELECT attendanceId, attendanceStatus FROM tblAttendance WHERE studentId = ? AND sessionId = ? AND DATE(markedAt) = CURDATE()";
    $findStmt = $conn->prepare($findSQL);

    if (!$findStmt) {
        echo '{"success":false,"message":"Database prepare error"}';
        exit;
    }

    $findStmt->bind_param("ii", $studentId, $sessionId);
    $findStmt->execute();
    $findResult = $findStmt->get_result();

    if ($findResult->num_rows === 0) {
        $findStmt->close();
        echo '{"success":false,"message":"No attendance record found for today"}';
        exit;
    }

    $record = $findResult->fetch_assoc();
    $attendanceId = $record['attendanceId'];
    $oldStatus = $record['attendanceStatus'];
    $findStmt->close();

    // Update the record
    $updateSQL = "UPDATE tblAttendance SET attendanceStatus = ?, markedAt = NOW() WHERE attendanceId = ?";
    $updateStmt = $conn->prepare($updateSQL);

    if (!$updateStmt) {
        echo '{"success":false,"message":"Update prepare failed"}';
        exit;
    }

    $updateStmt->bind_param("si", $status, $attendanceId);

    if (!$updateStmt->execute()) {
        $updateStmt->close();
        echo '{"success":false,"message":"Update failed"}';
        exit;
    }

    $affectedRows = $updateStmt->affected_rows;
    $updateStmt->close();

    if ($affectedRows > 0) {
        echo '{"success":true,"message":"Status changed from ' . $oldStatus . ' to ' . $status . '"}';
    } else {
        echo '{"success":false,"message":"No changes made"}';
    }

} catch (Exception $e) {
    echo '{"success":false,"message":"Server error occurred"}';
} catch (Error $e) {
    echo '{"success":false,"message":"Server error occurred"}';
}

// Close connection if it exists
if (isset($conn) && $conn) {
    $conn->close();
}
?>