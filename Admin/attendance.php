<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}
include '../Includes/db.php';

// Function to check if today is weekend
function isWeekend()
{
    $dayOfWeek = date('N');
    return ($dayOfWeek >= 6);
}

date_default_timezone_set('Asia/Phnom_Penh');

// Get selected date from URL parameter, default to today
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
// Get selected subject from URL parameter, default to 'all'
$selectedSubject = isset($_GET['subject']) ? $_GET['subject'] : 'all';
$selectedYear = isset($_GET['year']) ? $_GET['year'] : 'all'; // Get selected academic year from URL parameter, default to 'all'
$selectedMajor = isset($_GET['major']) ? $_GET['major'] : 'all'; // Get selected major from URL parameter, default to 'all'

// Validate the date format
if (!DateTime::createFromFormat('Y-m-d', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Check if selected date is weekend
$selectedDateTime = DateTime::createFromFormat('Y-m-d', $selectedDate);
$dayOfWeek = (int) $selectedDateTime->format('N');
$isSelectedDateWeekend = ($dayOfWeek >= 6);

$showAttendance = !$isSelectedDateWeekend;
$rs = null;

// Get subjects available for the selected date (or all subjects if showing all)
function getSubjectsForDate($conn, $dayOfWeek)
{
    $query = "SELECT s.subjectCode, s.subjectName, s.scheduledDay
              FROM tblsubject s 
              WHERE s.isActive = 1 
              AND (s.scheduledDay IS NULL OR s.scheduledDay = '' OR FIND_IN_SET(?, s.scheduledDay) > 0)
              ORDER BY s.subjectName";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed for getSubjectsForDate: " . $conn->error);
    }
    $stmt->bind_param("i", $dayOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    return $subjects;
}

// Get subjects for the selected day
$availableSubjects = getSubjectsForDate($conn, $dayOfWeek);

// Get academic years from database
$yearsQuery = "SELECT DISTINCT academicYear FROM tblstudent WHERE isActive = 1 ORDER BY academicYear";
$yearsResult = $conn->query($yearsQuery);
$academicYears = [];
if ($yearsResult && $yearsResult->num_rows > 0) {
    while ($row = $yearsResult->fetch_assoc()) {
        $academicYears[] = $row['academicYear'];
    }
}

// Get majors from database
$majorsQuery = "SELECT major_id, major_name FROM tblmajor WHERE isDeleted = 0 ORDER BY major_name";
$majorsResult = $conn->query($majorsQuery);
$majors = [];
if ($majorsResult && $majorsResult->num_rows > 0) {
    while ($row = $majorsResult->fetch_assoc()) {
        $majors[] = $row;
    }
}

// Check if selected subject is valid for the selected date
$isValidSubjectForDate = false;
if ($selectedSubject === 'all') {
    $isValidSubjectForDate = true;
} else {
    foreach ($availableSubjects as $subject) {
        if ($subject['subjectCode'] == $selectedSubject) {
            // Check if this subject is scheduled for this day
            if (isset($subject['scheduledDay']) && !empty($subject['scheduledDay'])) {
                $scheduledDays = explode(',', $subject['scheduledDay']);
                $isValidSubjectForDate = in_array($dayOfWeek, $scheduledDays);
            } else {
                // If no schedule specified, assume it's available all days
                $isValidSubjectForDate = true;
            }
            break;
        }
    }
}

// Function to create attendance records for students if they don't exist
function createAttendanceRecords($conn, $selectedDate, $selectedSubject, $dayOfWeek)
{
    $currentDateTime = $selectedDate . ' ' . date('H:i:s');

    if ($selectedSubject === 'all') {
        // Create for all students in subjects scheduled for the selected date
        $insertQuery = "INSERT IGNORE INTO tblattendance (studentId, subjectCode, sessionId, attendanceStatus, markedAt)
            SELECT DISTINCT ss.studentId, ss.subjectCode, sessions.sessionId, 'present', ?
            FROM tblstudentsubject ss
            INNER JOIN tblstudent s ON ss.studentId = s.studentId
            INNER JOIN tblsubject sub ON ss.subjectCode = sub.subjectCode
            CROSS JOIN (SELECT 1 AS sessionId UNION ALL SELECT 2 UNION ALL SELECT 3) sessions
            WHERE s.isActive = 1 AND sub.isActive = 1
            AND (sub.scheduledDay IS NULL OR sub.scheduledDay = '' OR FIND_IN_SET(?, sub.scheduledDay) > 0)";

        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            die("Prepare failed: " . $conn->error . "<br>SQL: " . $insertQuery);
        }
        $insertStmt->bind_param("si", $currentDateTime, $dayOfWeek);
        if (!$insertStmt->execute()) {
            die("Execute failed: " . $insertStmt->error);
        }
    } else {
        // Create for students in specific subject (if it's scheduled for the selected date)
        $insertQuery = "INSERT IGNORE INTO tblattendance (studentId, subjectCode, sessionId, attendanceStatus, markedAt)
            SELECT ss.studentId, ss.subjectCode, sessions.sessionId, 'present', ?
            FROM tblstudentsubject ss
            INNER JOIN tblstudent s ON ss.studentId = s.studentId
            INNER JOIN tblsubject sub ON ss.subjectCode = sub.subjectCode
            CROSS JOIN (SELECT 1 AS sessionId UNION ALL SELECT 2 UNION ALL SELECT 3) sessions
            WHERE s.isActive = 1 AND sub.isActive = 1
            AND ss.subjectCode = ?
            AND (sub.scheduledDay IS NULL OR sub.scheduledDay = '' OR FIND_IN_SET(?, sub.scheduledDay) > 0)";

        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            die("Prepare failed: " . $conn->error . "<br>SQL: " . $insertQuery);
        }
        $insertStmt->bind_param("ssi", $currentDateTime, $selectedSubject, $dayOfWeek);
        if (!$insertStmt->execute()) {
            die("Execute failed: " . $insertStmt->error);
        }
    }
}

if ($showAttendance && $isValidSubjectForDate) {
    // Check if attendance records exist for selected date
    $checkQuery = "SELECT COUNT(*) as count FROM tblattendance WHERE DATE(markedAt) = ?";
    $checkStmt = $conn->prepare($checkQuery);
    if (!$checkStmt) {
        die("Prepare failed for check query: " . $conn->error);
    }
    $checkStmt->bind_param("s", $selectedDate);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult) {
        $row = $checkResult->fetch_assoc();

        // Only auto-create attendance for today's date if no records exist
        if ($row['count'] == 0 && $selectedDate === date('Y-m-d')) {
            createAttendanceRecords($conn, $selectedDate, $selectedSubject, $dayOfWeek);
        }
    }

    // Build the main query - FIXED VERSION
    if ($selectedSubject === 'all') {
        // For "all subjects" - show each student-subject combination as separate rows
        // This is actually correct behavior - each student should appear once per subject
        $query = "SELECT DISTINCT
            s.studentId, 
            s.firstName, 
            s.lastName,
            s.academicYear,
            ss.subjectCode,
            sub.subjectName,
            a1.attendanceStatus as session1_status,
            a2.attendanceStatus as session2_status,
            a3.attendanceStatus as session3_status
            FROM tblstudent s
            INNER JOIN tblstudentsubject ss ON s.studentId = ss.studentId
            INNER JOIN tblsubject sub ON ss.subjectCode = sub.subjectCode
            LEFT JOIN tblattendance a1 ON s.studentId = a1.studentId AND ss.subjectCode = a1.subjectCode 
                AND DATE(a1.markedAt) = ? AND a1.sessionId = 1
            LEFT JOIN tblattendance a2 ON s.studentId = a2.studentId AND ss.subjectCode = a2.subjectCode 
                AND DATE(a2.markedAt) = ? AND a2.sessionId = 2
            LEFT JOIN tblattendance a3 ON s.studentId = a3.studentId AND ss.subjectCode = a3.subjectCode 
                AND DATE(a3.markedAt) = ? AND a3.sessionId = 3
            WHERE s.isActive = 1 AND sub.isActive = 1
            AND (sub.scheduledDay IS NULL OR sub.scheduledDay = '' OR FIND_IN_SET(?, sub.scheduledDay) > 0)";
        
        // Add academic year filter if selected
        if ($selectedYear !== 'all') {
            $query .= " AND s.academicYear = ?";
        }
        
        // Add major filter if selected
        if ($selectedMajor !== 'all') {
            $query .= " AND s.major_id = ?";
        }
        
        $query .= " ORDER BY s.firstName, s.lastName, s.studentId, sub.subjectName";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Prepare failed for main query (all subjects): " . $conn->error);
        }
        
        // Build bind parameters dynamically
        $paramTypes = "sssi";
        $paramValues = [$selectedDate, $selectedDate, $selectedDate, $dayOfWeek];
        
        if ($selectedYear !== 'all') {
            $paramTypes .= "s";
            $paramValues[] = $selectedYear;
        }
        
        if ($selectedMajor !== 'all') {
            $paramTypes .= "s";
            $paramValues[] = $selectedMajor;
        }
        
        $stmt->bind_param($paramTypes, ...$paramValues);

    } else {
        // For specific subject - each student appears only once
        $query = "SELECT DISTINCT
            s.studentId, 
            s.firstName, 
            s.lastName,
            s.academicYear,
            ss.subjectCode,
            sub.subjectName,
            a1.attendanceStatus as session1_status,
            a2.attendanceStatus as session2_status,
            a3.attendanceStatus as session3_status
            FROM tblstudent s
            INNER JOIN tblstudentsubject ss ON s.studentId = ss.studentId
            INNER JOIN tblsubject sub ON ss.subjectCode = sub.subjectCode
            LEFT JOIN tblattendance a1 ON s.studentId = a1.studentId AND ss.subjectCode = a1.subjectCode 
                AND DATE(a1.markedAt) = ? AND a1.sessionId = 1
            LEFT JOIN tblattendance a2 ON s.studentId = a2.studentId AND ss.subjectCode = a2.subjectCode 
                AND DATE(a2.markedAt) = ? AND a2.sessionId = 2
            LEFT JOIN tblattendance a3 ON s.studentId = a3.studentId AND ss.subjectCode = a3.subjectCode 
                AND DATE(a3.markedAt) = ? AND a3.sessionId = 3
            WHERE s.isActive = 1 AND sub.isActive = 1 AND ss.subjectCode = ?
            AND (sub.scheduledDay IS NULL OR sub.scheduledDay = '' OR FIND_IN_SET(?, sub.scheduledDay) > 0)";
        
        // Add academic year filter if selected
        if ($selectedYear !== 'all') {
            $query .= " AND s.academicYear = ?";
        }
        
        // Add major filter if selected
        if ($selectedMajor !== 'all') {
            $query .= " AND s.major_id = ?";
        }
        
        $query .= " ORDER BY s.firstName, s.lastName, s.studentId";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Prepare failed for main query (specific subject): " . $conn->error);
        }
        
        // Build bind parameters dynamically
        $paramTypes = "ssssi";
        $paramValues = [$selectedDate, $selectedDate, $selectedDate, $selectedSubject, $dayOfWeek];
        
        if ($selectedYear !== 'all') {
            $paramTypes .= "s";
            $paramValues[] = $selectedYear;
        }
        
        if ($selectedMajor !== 'all') {
            $paramTypes .= "s";
            $paramValues[] = $selectedMajor;
        }
        
        $stmt->bind_param($paramTypes, ...$paramValues);
    }

    if (!$stmt->execute()) {
        die("Execute failed for main query: " . $stmt->error);
    }

    $rs = $stmt->get_result();
    $allRows = [];

    if ($rs && $rs->num_rows > 0) {
        while ($row = $rs->fetch_assoc()) {
            $allRows[] = $row;
        }
    }

    // Debug: Add this temporarily to see what's being returned
    echo "<!-- Debug: Found " . count($allRows) . " rows -->";
    foreach ($allRows as $index => $row) {
        echo "<!-- Row $index: Student {$row['studentId']}, Subject {$row['subjectCode']} -->";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Attendance V2</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Enhanced styling for better UX */
        .attendance-checkbox {
            transform: scale(1.2);
            margin: 0 auto;
            display: block;
        }

        .session-cell {
            text-align: center;
            position: relative;
        }

        .loading-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }

        /* Custom message box styles */
        .message-box {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            min-width: 300px;
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        .message-box.success {
            background-color: #28a745;
            border-left: 4px solid #1e7e34;
        }

        .message-box.error {
            background-color: #dc3545;
            border-left: 4px solid #c82333;
        }

        .message-box .close-btn {
            float: right;
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-left: 10px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Weekend notice styling */
        .weekend-notice, .schedule-notice {
            text-align: center;
            padding: 40px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            margin: 20px 0;
            color: #856404;
        }

        .weekend-notice i, .schedule-notice i {
            color: #856404;
        }

        /* Disabled checkbox styling */
        .attendance-checkbox:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Row highlighting for better readability */
        .student-row:nth-child(even) {
            background-color: #f8f9fa;
        }

        .student-row:hover {
            background-color: #e3f2fd;
        }

        /* Improved filter container alignment */
        .search-and-date-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: flex-start;
        }

        .subject-filter-container,
        .year-filter-container,
        .major-filter-container,
        .date-filter-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 120px;
            flex: 1;
        }

        .filter-label {
            font-weight: 500;
            color: #333;
            font-size: 12px;
            white-space: nowrap;
        }

        .filter-select,
        .filter-input {
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            width: 100%;
            min-width: 100px;
        }

        .search-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
            border: none;
        }

        select, input[type="text"] {
            padding: 8px 10px;
            border: 0.1px solid #ddd
        }

        .search-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .clear-search {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0;
            margin-left: 5px;
            font-size: 14px;
        }

        .clear-search:hover {
            color: #333;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .filter-container {
                gap: 8px;
            }
            
            .subject-filter-container,
            .year-filter-container,
            .major-filter-container,
            .date-filter-container {
                min-width: 110px;
            }
        }

        @media (max-width: 992px) {
            .filter-container {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .subject-filter-container,
            .year-filter-container,
            .major-filter-container,
            .date-filter-container {
                flex: 1;
                min-width: 140px;
            }
            
            .search-container {
                margin-left: 0;
                width: 100%;
                justify-content: flex-start;
            }
            
            .search-input {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .subject-filter-container,
            .year-filter-container,
            .major-filter-container,
            .date-filter-container {
                min-width: 100%;
            }
            
            .search-container {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include "Includes/topbar.php"; ?>
    <?php include "Includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="heading-content">
            <h1 class="page-title">Attendance</h1>
            <div class="breadcrumb"><a href="./">Home /</a> Attendance</div>
        </div>

        <!-- Message box for notifications -->
        <div id="messageBox" class="message-box">
            <button class="close-btn" onclick="closeMessage()">&times;</button>
            <span id="messageText"></span>
        </div>

        <div class="list-container">
            <?php if (!$showAttendance): ?>
                    <div class="weekend-notice">
                        <i class="fas fa-calendar-times" style="font-size: 24px; margin-bottom: 10px;"></i>
                        <p><strong>Weekend Notice</strong></p>
                        <p>Attendance cannot be marked on weekends (<?php echo $selectedDateTime->format('l, F j, Y'); ?>).
                            Please select a weekday to view attendance records.</p>
                    </div>
            <?php elseif (!$isValidSubjectForDate && $selectedSubject !== 'all'): ?>
                    <div class="schedule-notice">
                        <i class="fas fa-calendar-exclamation"
                            style="font-size: 24px; margin-bottom: 10px; color: #856404;"></i>
                        <p><strong>Subject Not Scheduled</strong></p>
                        <p>The selected subject is not scheduled for <?php echo $selectedDateTime->format('l, F j, Y'); ?>.
                            Please select "All Subjects" or choose a date when this subject is taught.</p>
                    </div>
            <?php endif; ?>

            <div class="searchbar">
                <div class="header-row">
                    <h2 class="list-title">Daily Attendance - <?php echo $selectedDateTime->format('l, F j, Y'); ?></h2>
                    <div class="search-and-date-container">
                        <div class="filter-container">
                            <div class="subject-filter-container">
                                <label for="subjectFilter" class="filter-label">
                                    <i class="fas fa-book"></i> Subject:
                                </label>
                                <select id="subjectFilter" class="filter-select">
                                    <option value="all" <?php echo ($selectedSubject === 'all') ? 'selected' : ''; ?>>All
                                        Subjects</option>
                                    <?php foreach ($availableSubjects as $subject): ?>
                                            <?php
                                            // Check if subject is scheduled for selected day
                                            $isScheduledForDay = true;
                                            if (isset($subject['scheduledDay']) && !empty($subject['scheduledDay'])) {
                                                $scheduledDays = explode(',', $subject['scheduledDay']);
                                                $isScheduledForDay = in_array($dayOfWeek, $scheduledDays);
                                            }

                                            $optionClass = $isScheduledForDay ? '' : 'not-scheduled';
                                            $optionTitle = $isScheduledForDay ? '' : 'Not scheduled for ' . $selectedDateTime->format('l');
                                            ?>
                                            <option value="<?php echo htmlspecialchars($subject['subjectCode']); ?>"
                                                class="<?php echo $optionClass; ?>" title="<?php echo $optionTitle; ?>" 
                                                <?php echo ($selectedSubject == $subject['subjectCode']) ? 'selected' : ''; ?>
                                                <?php echo !$isScheduledForDay ? 'disabled style="color: #ccc; font-style: italic;"' : ''; ?>>
                                                <?php echo htmlspecialchars($subject['subjectName']); ?>
                                                <?php echo !$isScheduledForDay ? ' (Not scheduled)' : ''; ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="year-filter-container">
                                <label for="yearFilter" class="filter-label">
                                    <i class="fas fa-graduation-cap"></i> Year:
                                </label>
                                <select id="yearFilter" class="filter-select">
                                    <option value="all" <?php echo ($selectedYear === 'all') ? 'selected' : ''; ?>>All Years</option>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selectedYear == $year) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="major-filter-container">
                                <label for="majorFilter" class="filter-label">
                                    <i class="fas fa-building"></i> Major:
                                </label>
                                <select id="majorFilter" class="filter-select">
                                    <option value="all" <?php echo ($selectedMajor === 'all') ? 'selected' : ''; ?>>All Majors</option>
                                    <?php foreach ($majors as $major): ?>
                                        <option value="<?php echo htmlspecialchars($major['major_id']); ?>" <?php echo ($selectedMajor == $major['major_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($major['major_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="date-filter-container">
                                <label for="dateFilter" class="filter-label">
                                    <i class="fas fa-calendar-alt"></i> Date:
                                </label>
                                <input type="date" id="dateFilter" class="filter-input"
                                    value="<?php echo $selectedDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="search-filter-container">
                                <label for="studentSearch" class="filter-label">
                                    <i class="fas fa-search"></i> Search:
                                </label>
                                <div class="search-input-container">
                                    <input type="text" id="studentSearch" class="filter-input search-input"
                                        placeholder="Student name or ID...">
                                    <button type="button" id="clearSearch" class="clear-search" title="Clear search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($showAttendance && $isValidSubjectForDate): ?>
                        <div class="table-wrapper">
                            <table class="student-table" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Academic Year</th>
                                        <th>Subject</th>
                                        <th>Session 1</th>
                                        <th>Session 2</th>
                                        <th>Session 3</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($allRows)) {
                                        foreach ($allRows as $row) {
                                            if (!$row || !isset($row['studentId']) || empty($row['studentId'])) {
                                                continue;
                                            }

                                            $studentId = htmlspecialchars($row['studentId']);
                                            $firstName = htmlspecialchars($row['firstName'] ?? 'Unknown');
                                            $lastName = htmlspecialchars($row['lastName'] ?? '');
                                            $fullName = trim($firstName . ' ' . $lastName);
                                            $academicYear = htmlspecialchars($row['academicYear'] ?? 'N/A');
                                            $subjectName = htmlspecialchars($row['subjectName'] ?? 'N/A');
                                            $subjectCode = htmlspecialchars($row['subjectCode'] ?? '');

                                            // Get attendance status for each session (default to 'present' if null)
                                            $session1Status = $row['session1_status'] ?? 'present';
                                            $session2Status = $row['session2_status'] ?? 'present';
                                            $session3Status = $row['session3_status'] ?? 'present';

                                            $isEditable = ($selectedDate === date('Y-m-d')) ? '' : 'disabled';

                                            echo "<tr class='student-row' data-student-id='{$studentId}' data-student-name='" . strtolower($fullName) . "'>
                                            <td>{$studentId}</td>
                                            <td>{$fullName}</td>
                                            <td>{$academicYear}</td>
                                            <td>{$subjectName}</td>";

                                            // Session 1
                                            $session1Checked = ($session1Status === 'absent') ? 'checked' : '';
                                            echo "<td class='session-cell'>
                                                <input type='checkbox' 
                                                       class='attendance-checkbox' 
                                                       data-student-id='{$studentId}' 
                                                       data-subject-code='{$subjectCode}'
                                                       data-session='1' 
                                                       {$session1Checked} {$isEditable}
                                                       title='Mark as absent'>
                                                <div class='loading-indicator'>
                                                    <i class='fas fa-spinner fa-spin'></i>
                                                </div>
                                            </td>";

                                            // Session 2
                                            $session2Checked = ($session2Status === 'absent') ? 'checked' : '';
                                            echo "<td class='session-cell'>
                                                <input type='checkbox' 
                                                       class='attendance-checkbox' 
                                                       data-student-id='{$studentId}' 
                                                       data-subject-code='{$subjectCode}'
                                                       data-session='2' 
                                                       {$session2Checked} {$isEditable}
                                                       title='Mark as absent'>
                                                <div class='loading-indicator'>
                                                    <i class='fas fa-spinner fa-spin'></i>
                                                </div>
                                            </td>";

                                            // Session 3
                                            $session3Checked = ($session3Status === 'absent') ? 'checked' : '';
                                            echo "<td class='session-cell'>
                                                <input type='checkbox' 
                                                       class='attendance-checkbox' 
                                                       data-student-id='{$studentId}' 
                                                       data-subject-code='{$subjectCode}'
                                                       data-session='3' 
                                                       {$session3Checked} {$isEditable}
                                                       title='Mark as absent'>
                                                <div class='loading-indicator'>
                                                    <i class='fas fa-spinner fa-spin'></i>
                                                </div>
                                            </td>";

                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr class='no-data'><td colspan='7' style='text-align:center; padding:20px; color:#666;'>
                                    No students found for the selected criteria on " . date('F j, Y', strtotime($selectedDate)) . ".
                                </td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <div id="noResults" class="no-results" style="display: none;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i>
                                <p>No students found matching your search.</p>
                            </div>

                            <!-- Pagination -->
                            <div class="table-pagination">
                                <div class="pagination-info">
                                    <div class="show-entries">
                                        <label>Show
                                            <select id="entriesPerPage" class="entries-select">
                                                <option value="5">5</option>
                                                <option value="10" selected>10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                            entries
                                        </label>
                                    </div>
                                    <div class="results-count">
                                        <span id="resultsCount">Showing 0 results</span>
                                    </div>
                                </div>
                                <div class="pagination-controls">
                                    <button class="pagination-btn" id="prevBtn" disabled>
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <span class="page-info" id="pageInfo">Page 1 of 1</span>
                                    <button class="pagination-btn" id="nextBtn" disabled>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Message box functions
        function showMessage(message, type = 'success') {
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');

            messageText.textContent = message;
            messageBox.className = 'message-box ' + type;
            messageBox.style.display = 'block';

            // Auto hide after 4 seconds
            setTimeout(() => {
                closeMessage();
            }, 4000);
        }

        function closeMessage() {
            const messageBox = document.getElementById('messageBox');
            messageBox.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                messageBox.style.display = 'none';
                messageBox.style.animation = 'slideIn 0.3s ease-out';
            }, 300);
        }

        // Filter change handlers
        document.getElementById('dateFilter').addEventListener('change', function () {
            updateURLWithFilters();
        });

        document.getElementById('subjectFilter').addEventListener('change', function () {
            updateURLWithFilters();
        });

        document.getElementById('yearFilter').addEventListener('change', function () {
            updateURLWithFilters();
        });

        document.getElementById('majorFilter').addEventListener('change', function () {
            updateURLWithFilters();
        });

        function updateURLWithFilters() {
            const selectedDate = document.getElementById('dateFilter').value;
            const selectedSubject = document.getElementById('subjectFilter').value;
            const selectedYear = document.getElementById('yearFilter').value;
            const selectedMajor = document.getElementById('majorFilter').value;
            
            let url = `attendance.php?date=${selectedDate}&subject=${selectedSubject}`;
            
            if (selectedYear !== 'all') {
                url += `&year=${selectedYear}`;
            }
            
            if (selectedMajor !== 'all') {
                url += `&major=${selectedMajor}`;
            }
            
            window.location.href = url;
        }

        $(document).ready(function () {
            // --- Pagination Variables ---
            let rowsPerPage = parseInt($('#entriesPerPage').val()) || 10;
            let currentPage = 1;
            let allRows = [];
            let filteredRows = [];

            // Initialize rows array
            function initializeRows() {
                allRows = $('#attendanceTable tbody tr.student-row').toArray();
                filteredRows = allRows.slice(); // Copy all rows initially
            }

            function paginateTable() {
                // Hide all rows first
                $('#attendanceTable tbody tr.student-row').hide();
                $('#attendanceTable tbody tr.no-data').hide();

                let totalRows = filteredRows.length;
                let totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));

                // Ensure currentPage is within valid range
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                if (totalRows === 0) {
                    // Show no results message
                    $('#noResults').show();
                    $('#resultsCount').text('Showing 0 results');
                    $('#pageInfo').text('Page 0 of 0');
                } else {
                    // Hide no results message
                    $('#noResults').hide();

                    // Calculate range
                    let startIndex = (currentPage - 1) * rowsPerPage;
                    let endIndex = Math.min(startIndex + rowsPerPage, totalRows);

                    // Show rows for current page
                    for (let i = startIndex; i < endIndex; i++) {
                        if (filteredRows[i]) {
                            $(filteredRows[i]).show();
                        }
                    }

                    // Update pagination info
                    let showingFrom = totalRows > 0 ? startIndex + 1 : 0;
                    let showingTo = endIndex;
                    $('#resultsCount').text(`Showing ${showingFrom} to ${showingTo} of ${totalRows} results`);
                    $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
                }

                // Update button states
                $('#prevBtn').prop('disabled', currentPage <= 1);
                $('#nextBtn').prop('disabled', currentPage >= totalPages || totalRows === 0);
            }

            // --- Event Handlers ---
            $('#entriesPerPage').change(function () {
                let newRowsPerPage = parseInt($(this).val());
                if (newRowsPerPage && newRowsPerPage > 0) {
                    rowsPerPage = newRowsPerPage;
                    currentPage = 1;
                    paginateTable();
                }
            });

            $('#prevBtn').click(function () {
                if (currentPage > 1) {
                    currentPage--;
                    paginateTable();
                }
            });

            $('#nextBtn').click(function () {
                let totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
                if (currentPage < totalPages) {
                    currentPage++;
                    paginateTable();
                }
            });

            // --- Search Functionality ---
            $('#studentSearch').on('input', function () {
                let searchVal = $(this).val().toLowerCase().trim();

                if (searchVal === '') {
                    // Show all rows
                    filteredRows = allRows.slice();
                } else {
                    // Filter rows based on search
                    filteredRows = allRows.filter(function (row) {
                        let $row = $(row);
                        let studentId = ($row.data('student-id') || '').toString().toLowerCase();
                        let studentName = ($row.data('student-name') || '').toString().toLowerCase();
                        return studentId.includes(searchVal) || studentName.includes(searchVal);
                    });
                }

                currentPage = 1;
                paginateTable();
            });

            $('#clearSearch').click(function () {
                $('#studentSearch').val('').trigger('input');
            });

            // --- Enhanced Attendance Update (AJAX) ---
            $(document).on('change', '.attendance-checkbox', function () {
                let $checkbox = $(this);
                let $loadingIndicator = $checkbox.siblings('.loading-indicator');

                // Get data attributes
                let studentId = $checkbox.data('student-id');
                let subjectCode = $checkbox.data('subject-code');
                let sessionId = $checkbox.data('session');
                let isAbsent = $checkbox.is(':checked') ? 1 : 0;
                let date = $('#dateFilter').val();

                // Validation
                if (!studentId || !subjectCode || !sessionId) {
                    showMessage('Missing required data. Please refresh the page and try again.', 'error');
                    $checkbox.prop('checked', !$checkbox.prop('checked')); // Revert
                    return;
                }

                // Show loading indicator
                $checkbox.prop('disabled', true);
                $loadingIndicator.show();

                // Prepare data for AJAX
                let ajaxData = {
                    studentId: studentId,
                    subjectCode: subjectCode,
                    sessionId: sessionId,
                    isAbsent: isAbsent,
                    date: date,
                    action: 'update_attendance'
                };

                $.ajax({
                    url: 'update_attendance.php',
                    type: 'POST',
                    data: ajaxData,
                    dataType: 'json',
                    timeout: 10000, // 10 second timeout
                    success: function (response) {
                        if (response && response.success) {
                            // Show success message
                            let statusText = isAbsent ? 'marked as absent' : 'marked as present';
                            let studentName = $checkbox.closest('tr').find('td:nth-child(2)').text();
                            showMessage(`${studentName} - Session ${sessionId} ${statusText}`, 'success');
                        } else {
                            // Server returned error
                            let errorMsg = response && response.error ? response.error : 'Unknown server error';
                            showMessage('Failed to update attendance: ' + errorMsg, 'error');
                            $checkbox.prop('checked', !$checkbox.prop('checked')); // Revert
                        }
                    },
                    error: function (xhr, status, error) {
                        let errorMsg = 'Failed to update attendance. ';
                        if (status === 'timeout') {
                            errorMsg += 'Request timed out.';
                        } else if (status === 'parsererror') {
                            errorMsg += 'Invalid server response.';
                        } else if (xhr.status === 404) {
                            errorMsg += 'Update script not found.';
                        } else if (xhr.status === 500) {
                            errorMsg += 'Server error.';
                        } else {
                            errorMsg += 'Please try again.';
                        }

                        showMessage(errorMsg, 'error');
                        $checkbox.prop('checked', !$checkbox.prop('checked')); // Revert
                    },
                    complete: function () {
                        // Always hide loading and re-enable checkbox
                        $loadingIndicator.hide();
                        $checkbox.prop('disabled', false);
                    }
                });
            });

            // --- Initialize ---
            initializeRows();
            paginateTable();

            // Re-initialize if table content changes (e.g., after AJAX updates)
            if (window.MutationObserver) {
                let observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'childList') {
                            initializeRows();
                            paginateTable();
                        }
                    });
                });

                let tableBody = document.querySelector('#attendanceTable tbody');
                if (tableBody) {
                    observer.observe(tableBody, { childList: true });
                }
            }
        });
    </script>
    
    <?php include '../Includes/logout_confirmation.php'; ?>
</body>

</html>
