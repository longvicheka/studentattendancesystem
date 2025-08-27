<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}
include '../Includes/db.php';

// Test academic years query
echo "<h2>Testing Academic Years Filter</h2>";
$yearsQuery = "SELECT DISTINCT academicYear FROM tblstudent WHERE isActive = 1 ORDER BY academicYear";
$yearsResult = $conn->query($yearsQuery);
if ($yearsResult && $yearsResult->num_rows > 0) {
    echo "<p>Found " . $yearsResult->num_rows . " academic years:</p>";
    echo "<ul>";
    while ($row = $yearsResult->fetch_assoc()) {
        echo "<li>Year " . htmlspecialchars($row['academicYear']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No academic years found or query failed: " . $conn->error . "</p>";
}

// Test majors query
echo "<h2>Testing Majors Filter</h2>";
$majorsQuery = "SELECT major_id, major_name FROM tblmajor WHERE isDeleted = 0 ORDER BY major_name";
$majorsResult = $conn->query($majorsQuery);
if ($majorsResult && $majorsResult->num_rows > 0) {
    echo "<p>Found " . $majorsResult->num_rows . " majors:</p>";
    echo "<ul>";
    while ($row = $majorsResult->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['major_id']) . " - " . htmlspecialchars($row['major_name']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No majors found or query failed: " . $conn->error . "</p>";
}

// Test students with academic years and majors
echo "<h2>Testing Students with Academic Years</h2>";
$studentsQuery = "SELECT studentId, firstName, lastName, academicYear FROM tblstudent WHERE isActive = 1 ORDER BY academicYear, firstName";
$studentsResult = $conn->query($studentsQuery);
if ($studentsResult && $studentsResult->num_rows > 0) {
    echo "<p>Found " . $studentsResult->num_rows . " students:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Academic Year</th></tr>";
    while ($row = $studentsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['studentId']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstName'] . ' ' . $row['lastName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['academicYear']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No students found or query failed: " . $conn->error . "</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='report.php'>Go to Report Page</a></p>";
?>
