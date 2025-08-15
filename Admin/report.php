<?php
session_start();
include '../Includes/db.php';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Cache configuration
$cacheDir = '../cache/reports/';
$cacheFile = $cacheDir . 'attendance_cache.json';

// Ensure cache directory exists
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Get students for dropdown
$studentsQuery = "SELECT * FROM tblstudent ORDER BY firstName, lastName";
$studentsResult = $conn->query($studentsQuery);

if (!$studentsResult) {
    echo "Error in students query: " . $conn->error;
}

// Function to get database hash for change detection
function getDatabaseHash($conn, $studentId = 'all', $startDate = '', $endDate = '')
{
    // Use the same query logic as fetchDatabaseData to ensure consistency
    $query = "SELECT COUNT(*) as total_records, 
                     MAX(a.markedAt) as last_modified,
                     GROUP_CONCAT(CONCAT(COALESCE(a.studentId, s.studentId), '_', COALESCE(a.sessionId, 'null'), '_', COALESCE(DATE(a.markedAt), 'null'), '_', COALESCE(a.attendanceStatus, 'null')) ORDER BY s.studentId, a.markedAt, a.sessionId SEPARATOR '|') as data_signature
              FROM tblstudent s 
              LEFT JOIN tblattendance a ON s.studentId = a.studentId";

    $conditions = [];
    $params = [];

    if ($studentId !== 'all') {
        $conditions[] = "s.studentId = ?";
        $params[] = $studentId;
    }

    if (!empty($startDate)) {
        $conditions[] = "(a.markedAt IS NULL OR DATE(a.markedAt) >= ?)";
        $params[] = $startDate;
    }

    if (!empty($endDate)) {
        $conditions[] = "(a.markedAt IS NULL OR DATE(a.markedAt) <= ?)";
        $params[] = $endDate;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<!-- ERROR: Hash query prepare failed: " . $conn->error . " -->";
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $hashData = ($row['data_signature'] ?? '') . ($row['total_records'] ?? '0') . ($row['last_modified'] ?? '');
    echo "<!-- DEBUG: Hash data: " . substr($hashData, 0, 200) . "... -->";

    return md5($hashData);
}

// Function to fetch fresh data from database
function fetchDatabaseData($conn, $studentId = 'all', $startDate = '', $endDate = '')
{
    // DEBUGGING: First, let's get all students to ensure they exist
    $debugQuery = "SELECT Id, studentId, firstName, lastName FROM tblstudent ORDER BY firstName, lastName";
    $debugResult = $conn->query($debugQuery);
    $allStudentsDebug = [];
    if ($debugResult) {
        while ($row = $debugResult->fetch_assoc()) {
            $allStudentsDebug[] = $row;
        }
    }
    echo "<!-- DEBUG: All students in database: " . json_encode($allStudentsDebug) . " -->";

    $query = "SELECT 
                s.Id as table_id,
                s.studentId,
                s.firstName,
                s.lastName,
                a.sessionId,
                a.markedAt,
                a.attendanceStatus
              FROM tblstudent s
              LEFT JOIN tblattendance a ON s.studentId = a.studentId";

    $conditions = [];
    $params = [];

    if ($studentId !== 'all') {
        $conditions[] = "s.studentId = ?";
        $params[] = $studentId;
    }

    // FIXED: Modified date conditions to not filter out students without attendance
    if (!empty($startDate)) {
        $conditions[] = "(a.markedAt IS NULL OR DATE(a.markedAt) >= ?)";
        $params[] = $startDate;
    }

    if (!empty($endDate)) {
        $conditions[] = "(a.markedAt IS NULL OR DATE(a.markedAt) <= ?)";
        $params[] = $endDate;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY s.firstName, s.lastName, a.markedAt, a.sessionId";

    echo "<!-- DEBUG: Final query: " . $query . " -->";
    echo "<!-- DEBUG: Query parameters: " . json_encode($params) . " -->";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<!-- ERROR: Prepare failed: " . $conn->error . " -->";
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $reportData = [];
    $seenStudents = [];
    $rowCount = 0;

    echo "<!-- DEBUG: Starting to process query results -->";

    while ($row = $result->fetch_assoc()) {
        $rowCount++;
        $studentKey = $row['studentId'];

        echo "<!-- DEBUG Row $rowCount: " . json_encode($row) . " -->";

        // Track all students we encounter
        if (!isset($seenStudents[$studentKey])) {
            $seenStudents[$studentKey] = [
                'table_id' => $row['table_id'],
                'studentId' => $row['studentId'],
                'firstName' => $row['firstName'],
                'lastName' => $row['lastName']
            ];
            echo "<!-- DEBUG: Added student to seenStudents: " . $studentKey . " -->";
        }

        // Process attendance records
        if ($row['markedAt'] !== null && $row['sessionId'] !== null) {
            $attendanceKey = $row['studentId'] . '_' . date('Y-m-d', strtotime($row['markedAt'])) . '_' . $row['sessionId'];

            $isDuplicate = false;
            foreach ($reportData as $existingRow) {
                if (
                    $existingRow['studentId'] == $row['studentId'] &&
                    $existingRow['markedAt'] !== null &&
                    date('Y-m-d', strtotime($existingRow['markedAt'])) == date('Y-m-d', strtotime($row['markedAt'])) &&
                    $existingRow['sessionId'] == $row['sessionId']
                ) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $reportData[] = $row;
                echo "<!-- DEBUG: Added attendance record for studentId: " . $row['studentId'] . " -->";
            } else {
                echo "<!-- DEBUG: Skipped duplicate record for studentId: " . $row['studentId'] . " -->";
            }
        } else {
            // Handle students with no attendance records
            $hasNullRecord = false;
            foreach ($reportData as $existingRow) {
                if ($existingRow['studentId'] == $row['studentId'] && $existingRow['markedAt'] === null) {
                    $hasNullRecord = true;
                    break;
                }
            }

            if (!$hasNullRecord) {
                $reportData[] = $row;
                echo "<!-- DEBUG: Added null record for studentId: " . $row['studentId'] . " -->";
            }
        }
    }

    echo "<!-- DEBUG: Total rows from query: $rowCount -->";
    echo "<!-- DEBUG: Students seen: " . json_encode(array_keys($seenStudents)) . " -->";
    echo "<!-- DEBUG: Report data count before adding missing students: " . count($reportData) . " -->";

    // CRITICAL FIX: Ensure ALL students appear, even those with no records at all
    foreach ($seenStudents as $studentKey => $studentInfo) {
        $hasRecord = false;
        foreach ($reportData as $row) {
            if ($row['studentId'] == $studentInfo['studentId']) {
                $hasRecord = true;
                break;
            }
        }

        if (!$hasRecord) {
            $missingStudentRecord = [
                'table_id' => $studentInfo['table_id'],
                'studentId' => $studentInfo['studentId'],
                'firstName' => $studentInfo['firstName'],
                'lastName' => $studentInfo['lastName'],
                'sessionId' => null,
                'markedAt' => null,
                'attendanceStatus' => null
            ];
            $reportData[] = $missingStudentRecord;
            echo "<!-- DEBUG: Added missing student record: " . json_encode($missingStudentRecord) . " -->";
        }
    }

    echo "<!-- DEBUG: Final report data count: " . count($reportData) . " -->";
    echo "<!-- DEBUG: Final report data (first 3): " . json_encode(array_slice($reportData, 0, 3)) . " -->";

    return $reportData;
}

// Function to load cache
function loadCache($cacheFile)
{
    if (!file_exists($cacheFile)) {
        return null;
    }

    $cacheData = json_decode(file_get_contents($cacheFile), true);
    if (!$cacheData) {
        return null;
    }

    // Check if cache is older than 1 hour
    if (isset($cacheData['timestamp']) && (time() - $cacheData['timestamp']) > 3600) {
        return null;
    }

    return $cacheData;
}

// Function to save cache
function saveCache($cacheFile, $data, $hash)
{
    $cacheData = [
        'timestamp' => time(),
        'hash' => $hash,
        'data' => $data
    ];

    file_put_contents($cacheFile, json_encode($cacheData));
}

// Handle report generation
$reportData = [];
$reportGenerated = false;
$dataSource = '';

if (isset($_POST['generate_report'])) {
    $studentId = $_POST['studentId'] ?? 'all'; // use studentId, not table_id
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    // Generate cache key based on filters
    $cacheKey = md5($studentId . '_' . $startDate . '_' . $endDate);
    $specificCacheFile = $cacheDir . 'attendance_cache_' . $cacheKey . '.json';

    // Get current database hash
    $currentHash = getDatabaseHash($conn, $studentId, $startDate, $endDate);

    if ($currentHash === false) {
        echo "<!-- Error: Could not generate database hash -->";
        $reportData = fetchDatabaseData($conn, $studentId, $startDate, $endDate);
        $dataSource = 'database (hash error)';
    } else {
        // Try to load cached data
        $cachedData = loadCache($specificCacheFile);

        if ($cachedData && isset($cachedData['hash']) && $cachedData['hash'] === $currentHash) {
            // Cache is valid and up to date
            $reportData = $cachedData['data'];
            $dataSource = 'cache (up to date)';
            echo "<!-- Debug: Using cached data (hash match) -->";
        } else {
            // Cache is invalid or data has changed - fetch fresh data
            $reportData = fetchDatabaseData($conn, $studentId, $startDate, $endDate);
            $dataSource = 'database (fresh fetch)';

            if ($reportData !== false) {
                // Save new cache
                saveCache($specificCacheFile, $reportData, $currentHash);
                echo "<!-- Debug: Fetched fresh data and updated cache -->";

                if ($cachedData) {
                    echo "<!-- Debug: Cache was outdated (hash mismatch) -->";
                } else {
                    echo "<!-- Debug: No cache found, created new cache -->";
                }
            } else {
                echo "<!-- Error: Could not fetch database data -->";
            }
        }
    }

    echo "<!-- Debug: Data source: " . $dataSource . " -->";
    echo "<!-- Debug: Current hash: " . $currentHash . " -->";
    echo "<!-- Debug: Report data count: " . count($reportData) . " -->";

    $reportGenerated = true;
}

// Process data for daily attendance calculation (same as original)
$processedData = [];
if (!empty($reportData)) {
    foreach ($reportData as $row) {
        $studentKey = $row['studentId'];

        if ($row['markedAt'] === null) {
            if (!isset($processedData[$studentKey])) {
                $processedData[$studentKey] = [
                    'student_info' => [
                        'name' => $row['firstName'] . ' ' . $row['lastName'],
                        'id' => $row['studentId'],
                        'table_id' => $row['table_id']
                    ],
                    'daily_attendance' => []
                ];
            }
            continue;
        }

        $date = date('Y-m-d', strtotime($row['markedAt']));

        if (!isset($processedData[$studentKey])) {
            $processedData[$studentKey] = [
                'student_info' => [
                    'name' => $row['firstName'] . ' ' . $row['lastName'],
                    'id' => $row['studentId'],
                    'table_id' => $row['table_id']
                ],
                'daily_attendance' => []
            ];
        }

        if (!isset($processedData[$studentKey]['daily_attendance'][$date])) {
            $processedData[$studentKey]['daily_attendance'][$date] = [
                'sessions' => [],
                'status' => 'Unknown',
                'late' => false
            ];
        }

        $attendanceStatus = $row['attendanceStatus'];
        if ($attendanceStatus === '1' || strtolower($attendanceStatus) === 'present') {
            $attendanceStatus = 'present';
        } else {
            $attendanceStatus = 'absent';
        }

        $processedData[$studentKey]['daily_attendance'][$date]['sessions'][$row['sessionId']] = $attendanceStatus;
    }

    // Calculate daily attendance status (same as original)
    foreach ($processedData as $studentKey => &$studentData) {
        foreach ($studentData['daily_attendance'] as $date => &$dayData) {
            $sessions = $dayData['sessions'];

            $presentSessions = array_filter($sessions, function ($status) {
                return strtolower(trim($status)) === 'present';
            });

            $presentCount = count($presentSessions);
            $totalSessions = count($sessions);

            if ($totalSessions === 0) {
                $dayData['status'] = '-';
                $dayData['late'] = false;
            } elseif ($presentCount === 0) {
                $dayData['status'] = 'A';
                $dayData['late'] = false;
            } elseif ($presentCount === $totalSessions) {
                $dayData['status'] = 'P';
                $dayData['late'] = false;
            } else {
                $firstSessionPresent = isset($sessions['1']) && strtolower($sessions['1']) === 'present';

                if (!$firstSessionPresent && $presentCount >= 1) {
                    $dayData['status'] = 'L';
                    $dayData['late'] = true;
                } elseif ($presentCount >= ceil($totalSessions / 2)) {
                    $dayData['status'] = 'P';
                    $dayData['late'] = false;
                } else {
                    $dayData['status'] = 'Partial';
                    $dayData['late'] = false;
                }
            }
        }
    }
}

// Get unique dates for table headers (same as original)
$allDates = [];
foreach ($processedData as $student) {
    $allDates = array_merge($allDates, array_keys($student['daily_attendance']));
}
$allDates = array_unique($allDates);
sort($allDates);

if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
    $start = new DateTime($_POST['start_date']);
    $end = new DateTime($_POST['end_date']);
    $dateRange = [];

    for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
        $dateRange[] = $date->format('Y-m-d');
    }

    $allDates = array_unique(array_merge($allDates, $dateRange));
    sort($allDates);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

    <!-- Export libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>

<body>
    <?php include "Includes/topbar.php"; ?>
    <?php include "Includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="heading-content">
            <h1 class="page-title">Attendance Report</h1>
            <div class="breadcrumb">
                <a href="./">Home</a> / Report
            </div>
        </div>

        <div class="report-container">
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="POST" action="" id="reportForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="studentId">Student</label>
                            <select name="studentId" id="studentId">
                                <option value="all">All Students</option>
                                <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                                    <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $student['studentId']; ?>" <?php echo (isset($_POST['studentId']) && $_POST['studentId'] == $student['studentId']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No students found</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : ''; ?>">
                        </div>

                        <div class="filter-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
                        </div>

                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" name="generate_report" class="generate-btn">
                                <i class="fas fa-chart-bar"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Report Display -->
            <?php if ($reportGenerated): ?>
                <div class="report-header">
                    <h2 class="report-title">Attendance Report</h2>
                    <p class="report-subtitle">
                        Generated on <?php echo date('F j, Y'); ?>
                        <?php if (!empty($_POST['start_date']) || !empty($_POST['end_date'])): ?>
                            | Period: <?php echo $_POST['start_date'] ?? 'Start'; ?> to
                            <?php echo $_POST['end_date'] ?? 'End'; ?>
                        <?php endif; ?>
                        <?php if (!empty($dataSource)): ?>
                            | Data Source: <?php echo ucfirst(explode(' ', $dataSource)[0]); ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Attendance Legend -->
                <div class="attendance-legend">
                    <div class="legend-item">
                        <div class="legend-color present"></div>
                        <span><strong>P</strong> - Present</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color absent"></div>
                        <span><strong>A</strong> - Absent</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color late"></div>
                        <span><strong>L</strong> - Late</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color partial"></div>
                        <span><strong>Partial</strong> - Partial (Left early)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color no-record"></div>
                        <span><strong>-</strong> - No Record</span>
                    </div>
                </div>

                <div class="report-actions">
                    <button class="action-btn refresh-btn" onclick="refreshReport()">
                        <i class="fas fa-sync-alt"></i> Refresh Data
                    </button>
                    <button class="action-btn export-excel" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <button class="action-btn export-pdf" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button class="action-btn print-btn" onclick="printReport()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>

                <?php if (!empty($processedData)): ?>
                    <div class="table-container">
                        <table class="attendance-table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th class="student-info">Student Name</th>
                                    <th class="student-info">Student ID</th>
                                    <?php foreach ($allDates as $date): ?>
                                        <th><?php echo date('M j', strtotime($date)); ?></th>
                                    <?php endforeach; ?>
                                    <th>Total Present</th>
                                    <th>Total Absent</th>
                                    <th>Total Late</th>
                                    <th>Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processedData as $studentId => $studentData): ?>
                                    <?php
                                    $totalPresent = 0;
                                    $totalAbsent = 0;
                                    $totalLate = 0;
                                    $totalDays = 0;

                                    foreach ($allDates as $date) {
                                        $dayStatus = $studentData['daily_attendance'][$date]['status'] ?? '-';
                                        if ($dayStatus !== '-') {
                                            $totalDays++;
                                            if ($dayStatus === 'P') {
                                                $totalPresent++;
                                            } elseif ($dayStatus === 'A') {
                                                $totalAbsent++;
                                            } elseif ($dayStatus === 'L') {
                                                $totalLate++;
                                                $totalPresent++;
                                            } elseif ($dayStatus === 'Partial') {
                                                $totalPresent++;
                                            }
                                        }
                                    }

                                    $attendancePercentage = $totalDays > 0 ? round(($totalPresent / $totalDays) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td class="student-info">
                                            <?php echo htmlspecialchars($studentData['student_info']['name']); ?>
                                        </td>
                                        <td class="student-info">
                                            <?php echo htmlspecialchars($studentData['student_info']['id']); ?>
                                        </td>
                                        <?php foreach ($allDates as $date): ?>
                                            <?php
                                            $dayData = $studentData['daily_attendance'][$date] ?? null;
                                            $displayText = '-';
                                            $cellClass = 'no-record';

                                            if ($dayData) {
                                                $displayText = $dayData['status'];
                                                if ($dayData['status'] === 'P') {
                                                    $cellClass = 'present';
                                                } elseif ($dayData['status'] === 'A') {
                                                    $cellClass = 'absent';
                                                } elseif ($dayData['status'] === 'L') {
                                                    $cellClass = 'late';
                                                } elseif ($dayData['status'] === 'Partial') {
                                                    $cellClass = 'partial';
                                                }
                                            }
                                            ?>
                                            <td class="<?php echo $cellClass; ?>"
                                                title="<?php echo $dayData && $dayData['status'] === 'L' ? 'Late - Missed 1st session but attended others' : ''; ?>">
                                                <?php echo $displayText; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="total-present"><?php echo $totalPresent; ?></td>
                                        <td class="total-absent"><?php echo $totalAbsent; ?></td>
                                        <td class="total-late"><?php echo $totalLate; ?></td>
                                        <td class="attendance-percentage"><?php echo $attendancePercentage; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox fa-3x"></i>
                        <h3>No Data Found</h3>
                        <p>No attendance records found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-bar fa-3x"></i>
                    <h3>Generate Your Report</h3>
                    <p>Select your filters above and click "Generate Report" to view attendance data.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-set end date when start date is selected
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', function () {
                    if (this.value && !endDateInput.value) {
                        const start = new Date(this.value);
                        const end = new Date(start.getTime() + (30 * 24 * 60 * 60 * 1000));
                        endDateInput.value = end.toISOString().split('T')[0];
                    }
                });
            }
        });

        // Refresh report function
        function refreshReport() {
            // Add a parameter to force refresh
            const form = document.getElementById('reportForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'force_refresh';
            input.value = Date.now();
            form.appendChild(input);
            form.submit();
        }

        // Export functions
        function exportToExcel() {
            const table = document.getElementById('attendanceTable');
            if (!table) {
                alert('No table found to export');
                return;
            }
            const wb = XLSX.utils.table_to_book(table, { sheet: "Attendance Report" });
            const filename = 'attendance_report_' + new Date().toISOString().split('T')[0] + '.xlsx';
            XLSX.writeFile(wb, filename);
        }

        // function exportToPDF() {
        //     const table = document.getElementById('attendanceTable');
        //     if (!table) {
        //         alert('No table found to export');
        //         return;
        //     }

        //     const { jsPDF } = window.jspdf;
        //     const doc = new jsPDF('l', 'mm', 'a4');

        //     doc.setFontSize(18);
        //     doc.text('Attendance Report', 20, 20);
        //     doc.setFontSize(12);
        //     doc.text('Generated on: ' + new Date().toLocaleDateString(), 20, 30);
        //     doc.text('P = Present, A = Absent, L = Late, Partial = Partial, - = No Record', 20, 35);

        //     const rows = [];
        //     const headers = [];

        //     table.querySelectorAll('thead th').forEach(th => {
        //         headers.push(th.textContent.trim());
        //     });

        //     table.querySelectorAll('tbody tr').forEach(tr => {
        //         const row = [];
        //         tr.querySelectorAll('td').forEach(td => {
        //             row.push(td.textContent.trim());
        //         });
        //         rows.push(row);
        //     });

        //     doc.autoTable({
        //         head: [headers],
        //         body: rows,
        //         startY: 45,
        //         styles: { fontSize: 8, cellPadding: 2 },
        //         headStyles: { fillColor: [248, 249, 250], textColor: [0, 0, 0] },
        //         columnStyles: { 0: { cellWidth: 50 }, 1: { cellWidth: 30 } }
        //     });

        //     const filename = 'attendance_report_' + new Date().toISOString().split('T')[0] + '.pdf';
        //     doc.save(filename);
        // }

        function exportToPDF() {
            const table = document.getElementById('attendanceTable');
            if (!table) {
                alert('No table found to export');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');

            // Title and date
            doc.setFontSize(18);
            doc.text('Attendance Report', 20, 20);
            doc.setFontSize(12);
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 20, 30);
            doc.text('P = Present, A = Absent, L = Late, Partial = Partial, - = No Record', 20, 35);

            const rows = [];
            const headers = ['Student ID', 'Student Name', 'Total Present', 'Total Absent', 'Total Late', 'Attendance %'];

            // Collect data for the specified columns
            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                const studentId = tr.querySelector('td:nth-child(1)').textContent.trim(); // Student ID
                const studentName = tr.querySelector('td:nth-child(2)').textContent.trim(); // Name
                const totalPresent = tr.querySelector('td:nth-child(3)').textContent.trim(); // Total Present
                const totalAbsent = tr.querySelector('td:nth-child(4)').textContent.trim(); // Total Absent
                const totalLate = tr.querySelector('td:nth-child(5)').textContent.trim(); // Total Late
                const attendancePercent = tr.querySelector('td:nth-child(6)').textContent.trim(); // Attendance %

                row.push(studentId, studentName, totalPresent, totalAbsent, totalLate, attendancePercent);
                rows.push(row);
            });

            // Generate the PDF table
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 45,
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [248, 249, 250], textColor: [0, 0, 0] },
                columnStyles: {
                    0: { cellWidth: 30 }, // Student ID
                    1: { cellWidth: 50 }, // Name
                    2: { cellWidth: 30 }, // Total Present
                    3: { cellWidth: 30 }, // Total Absent
                    4: { cellWidth: 30 }, // Total Late
                    5: { cellWidth: 30 }  // Attendance %
                }
            });

            const filename = 'attendance_report_' + new Date().toISOString().split('T')[0] + '.pdf';
            doc.save(filename);
        }

        function printReport() {
            window.print();
        }
    </script>
</body>

</html>