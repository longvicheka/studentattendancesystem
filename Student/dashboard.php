<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student') {
    header("Location: ../login.php");
    exit();
}
include '../Includes/db.php';

$studentId = $_SESSION['studentId'] ?? null;

// Get filter values from GET
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$subjectFilter = $_GET['subject'] ?? '';

// Fetch student info, term, year
$studentQuery = "SELECT s.firstName, s.lastName, ss.term, ss.academicYear
                 FROM tblstudent s
                 JOIN tblstudentsubject ss ON s.studentId = ss.studentId
                 WHERE s.studentId = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param('s', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch subjects for this student in current term
$subjectQuery = "SELECT DISTINCT sub.subjectId, sub.subjectName
                 FROM tblstudentsubject ss
                 JOIN tblsubject sub ON ss.subjectCode = sub.subjectCode
                 WHERE ss.studentId = ?
                 ORDER BY sub.subjectName";
$stmt2 = $conn->prepare($subjectQuery);
$stmt2->bind_param('s', $studentId);
$stmt2->execute();
$subjects = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Attendance query with filters
$query = "SELECT DATE(a.markedAt) as classDate, s.subjectName, a.sessionId, a.attendanceStatus
          FROM tblattendance a
          JOIN tblsubject s ON a.subjectCode = s.subjectCode
          WHERE a.studentId = ?";
$params = [$studentId];
$types = "s";

if (!empty($startDate)) {
    $query .= " AND DATE(a.markedAt) >= ?";
    $params[] = $startDate;
    $types .= "s";
}
if (!empty($endDate)) {
    $query .= " AND DATE(a.markedAt) <= ?";
    $params[] = $endDate;
    $types .= "s";
}
if (!empty($subjectFilter)) {
    $query .= " AND s.subjectId = ?";
    $params[] = $subjectFilter;
    $types .= "s";
}

$query .= " ORDER BY classDate DESC, s.subjectName ASC, a.sessionId ASC";
$stmt3 = $conn->prepare($query);
$stmt3->bind_param($types, ...$params);
$stmt3->execute();
$result = $stmt3->get_result();

// Group attendance by date & subject
$groupedData = [];
$totals = ['present' => 0, 'absent' => 0, 'late' => 0, 'classes' => 0];

while ($row = $result->fetch_assoc()) {
    $key = $row['classDate'] . '|' . $row['subjectName'];
    if (!isset($groupedData[$key])) {
        $groupedData[$key] = ['date' => $row['classDate'], 'subject' => $row['subjectName'], 'sessions' => []];
    }
    $groupedData[$key]['sessions'][] = $row['attendanceStatus'];
}

// Determine daily status
foreach ($groupedData as &$day) {
    $statuses = $day['sessions'];
    $day['status'] = 'Present';

    if (count(array_unique($statuses)) === 1 && $statuses[0] == '0') {
        $day['status'] = 'Absent';
        $totals['absent']++;
    } elseif ($statuses[0] == 'Late') {
        $day['status'] = 'Late';
        $totals['late']++;
        $totals['present']++;
    } else {
        $totals['present']++;
    }
    $totals['classes']++;
}
unset($day);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Attendance Dashboard</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function autoSubmit() {
            document.getElementById('filterForm').submit();
        }
    </script>
</head>

<body>
    <?php include "Includes/topbar.php"; ?>
    <?php include "Includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="heading-content">
            <h2 class="page-title">Welcome, <?= htmlspecialchars($student['firstName'] . ' ' . $student['lastName']) ?>
            </h2>

            <!-- Stats Section -->
            <div class="stat-grid" style="padding: 24px 0;">
                <div class="stat-card">
                    <h3><?= $totals['present'] ?></h3>
                    <p class="stat-value">Total Present</p>
                </div>
                <div class="stat-card">
                    <h3><?= $totals['absent'] ?></h3>
                    <p class="stat-value">Total Absent</p>
                </div>
                <div class="stat-card">
                    <h3><?= $totals['classes'] ?></h3>
                    <p class="stat-value">Total Classes</p>
                </div>
                <div class="stat-card">
                    <h3><?= htmlspecialchars($student['term']) ?></h3>
                    <p class="stat-value">Term</p>
                </div>
                <div class="stat-card">
                    <h3><?= htmlspecialchars($student['academicYear']) ?></h3>
                    <p class="stat-value">Academic Year</p>
                </div>
                <div class="stat-card">
                    <h3><?= count($subjects) ?></h3>
                    <p class="stat-value">Subjects this Term</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <form id="filterForm" method="GET" class="filter-bar">
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                    onchange="autoSubmit()">
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" onchange="autoSubmit()">
                <select name="subject" onchange="autoSubmit()">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['subjectId'] ?>" <?= ($subjectFilter == $sub['subjectId']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subjectName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Attendance Table -->
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groupedData)): ?>
                        <tr>
                            <td colspan="3">No attendance records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groupedData as $day): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($day['date'])) ?></td>
                                <td><?= htmlspecialchars($day['subject']) ?></td>
                                <td class="<?= strtolower($day['status']) ?>"><?= $day['status'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>