<?php
session_start();
include_once 'db.php'; // Include database connection for enhanced session validation

// Get the current page path to determine if it's an admin or student page
$current_page = $_SERVER['PHP_SELF'];

// Check if it's an admin page (starts with /Admin/)
if (strpos($current_page, '/Admin/') !== false) {
    // For admin pages, check for admin session variables
    if (!isset($_SESSION['adminId']) || !isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
        // Enhanced validation: Check if admin still exists in database
        if (isset($_SESSION['adminId'])) {
            $adminId = $_SESSION['adminId'];
            $query = "SELECT Id FROM tbladmin WHERE Id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $rs = $stmt->get_result();

            if ($rs && $rs->num_rows === 0) {
                // Admin no longer exists in database, clear session
                session_unset();
                session_destroy();
            }
        }

        echo "<script type = \"text/javascript\">
        window.location = (\"../login.php\");
        </script>";
        exit();
    }
} else {
    // For student pages, check for student session variables
    if (!isset($_SESSION['studentId']) || !isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student') {
        // Enhanced validation: Check if student still exists in database
        if (isset($_SESSION['studentId'])) {
            $studentId = $_SESSION['studentId'];
            $query = "SELECT studentId FROM tblstudent WHERE studentId = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $studentId);
            $stmt->execute();
            $rs = $stmt->get_result();

            if ($rs && $rs->num_rows === 0) {
                // Student no longer exists in database, clear session
                session_unset();
                session_destroy();
            }
        }

        echo "<script type = \"text/javascript\">
        window.location = (\"../login.php\");
        </script>";
        exit();
    }
}
