<?php
include '../Includes/db.php';
include '../Includes/session.php';

// Get current logged-in student's studentId from session
$studentId = $_SESSION['studentId'] ?? null;

// Fetch student info
$studentQuery = "SELECT firstName, lastName, studentId FROM tblstudent WHERE studentId = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$studentResult = $stmt->get_result();
$student = $studentResult->fetch_assoc();

// Fetch attendance records for this student
$attendanceQuery = "SELECT sessionId, markedAt, attendanceStatus FROM tblattendance WHERE studentId = ? ORDER BY markedAt, sessionId";
$stmt2 = $conn->prepare($attendanceQuery);
$stmt2->bind_param('s', $studentId);
$stmt2->execute();
$attendanceResult = $stmt2->get_result();

$attendanceData = [];
while ($row = $attendanceResult->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['markedAt']));
    if (!isset($attendanceData[$date])) {
        $attendanceData[$date] = [];
    }
    $attendanceData[$date][$row['sessionId']] = $row['attendanceStatus'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include "Includes/topbar.php"; ?>
    <?php include "Includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="heading-content">
            <h1 class="page-title">Welcome,
                <?php
                if ($student) {
                    echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']);
                } else {
                    echo "Student";
                }
                ?>
            </h1>
            <div class="breadcrumb">
                Dashboard
            </div>
        </div>

        <div class="dashboard-container">
            <h2>Your Attendance Records</h2>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceData as $date => $sessions): ?>
                        <?php foreach ($sessions as $sessionId => $status): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($date)); ?></td>
                                <td><?php echo htmlspecialchars($sessionId); ?></td>
                                <td><?php echo htmlspecialchars($status == '1' ? 'Present' : ($status == '0' ? 'Absent' : $status)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php if (empty($attendanceData)): ?>
                        <tr>
                            <td colspan="3">No attendance records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>