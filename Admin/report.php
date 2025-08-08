<?php
include '../Includes/db.php';
include '../Includes/session.php';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get students for dropdown
$studentsQuery = "SELECT * FROM tblstudent ORDER BY firstName, lastName";
$studentsResult = $conn->query($studentsQuery);

if (!$studentsResult) {
    echo "Error in students query: " . $conn->error;
}

// Handle report generation
$reportData = [];
$reportGenerated = false;

if (isset($_POST['generate_report'])) {
    $studentId = $_POST['table_id'] ?? 'all';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    // FIXED: Modified LEFT JOIN to ensure all students appear
    $query = "SELECT DISTINCT
                s.Id as table_id,
                s.userId,
                s.firstName,
                s.lastName,
                a.sessionId,
                a.markedAt,
                a.attendanceStatus
              FROM tblstudent s
              LEFT JOIN tblattendance a ON s.userId = a.studentId";

    $conditions = [];
    $params = [];

    if ($studentId !== 'all') {
        $conditions[] = "s.Id = ?";
        $params[] = $studentId;
    }

    // FIXED: Modified date conditions to not exclude students without attendance
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

    // Debug output
    echo "<!-- Debug Query: " . $query . " -->";
    if (!empty($params)) {
        echo "<!-- Debug Parameters: " . implode(', ', $params) . " -->";
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // FIXED: Improved deduplication logic
    $reportData = [];
    $seenStudents = []; // Track students we've seen
    
    while ($row = $result->fetch_assoc()) {
        $studentKey = $row['userId'];
        
        // Always include the student record (even with null attendance)
        if (!isset($seenStudents[$studentKey])) {
            $seenStudents[$studentKey] = [
                'table_id' => $row['table_id'],
                'userId' => $row['userId'],
                'firstName' => $row['firstName'],
                'lastName' => $row['lastName']
            ];
        }
        
        // For attendance records, use more robust deduplication
        if ($row['markedAt'] !== null && $row['sessionId'] !== null) {
            $attendanceKey = $row['userId'] . '_' . date('Y-m-d', strtotime($row['markedAt'])) . '_' . $row['sessionId'];
            
            // Check if we already have this attendance record
            $isDuplicate = false;
            foreach ($reportData as $existingRow) {
                if ($existingRow['userId'] == $row['userId'] && 
                    $existingRow['markedAt'] !== null &&
                    date('Y-m-d', strtotime($existingRow['markedAt'])) == date('Y-m-d', strtotime($row['markedAt'])) &&
                    $existingRow['sessionId'] == $row['sessionId']) {
                    $isDuplicate = true;
                    break;
                }
            }
            
            if (!$isDuplicate) {
                $reportData[] = $row;
            }
        } else {
            // For null attendance records, only add one per student
            $hasNullRecord = false;
            foreach ($reportData as $existingRow) {
                if ($existingRow['userId'] == $row['userId'] && $existingRow['markedAt'] === null) {
                    $hasNullRecord = true;
                    break;
                }
            }
            
            if (!$hasNullRecord) {
                $reportData[] = $row;
            }
        }
    }
    
    // FIXED: Ensure all students appear, even those with no attendance records
    foreach ($seenStudents as $studentKey => $studentInfo) {
        $hasRecord = false;
        foreach ($reportData as $row) {
            if ($row['userId'] == $studentInfo['userId']) {
                $hasRecord = true;
                break;
            }
        }
        
        if (!$hasRecord) {
            $reportData[] = [
                'table_id' => $studentInfo['table_id'],
                'userId' => $studentInfo['userId'],
                'firstName' => $studentInfo['firstName'],
                'lastName' => $studentInfo['lastName'],
                'sessionId' => null,
                'markedAt' => null,
                'attendanceStatus' => null
            ];
        }
    }

    echo "<!-- Debug: Report data count after deduplication: " . count($reportData) . " -->";
    echo "<!-- Debug: Students found: " . count($seenStudents) . " -->";
    $reportGenerated = true;
}

// Process data for daily attendance calculation
$processedData = [];
if (!empty($reportData)) {
    // Debug: Show sample data
    echo "<!-- Debug: Sample report data: " . json_encode(array_slice($reportData, 0, 2)) . " -->";

    foreach ($reportData as $row) {
        // Use userId as the primary key to avoid duplicates
        $studentKey = $row['userId'];

        // Skip rows with null attendance data but still include the student
        if ($row['markedAt'] === null) {
            if (!isset($processedData[$studentKey])) {
                $processedData[$studentKey] = [
                    'student_info' => [
                        'name' => $row['firstName'] . ' ' . $row['lastName'],
                        'id' => $row['userId'],
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
                    'id' => $row['userId'],
                    'table_id' => $row['table_id']
                ],
                'daily_attendance' => []
            ];
        }

        // Initialize day data if not exists
        if (!isset($processedData[$studentKey]['daily_attendance'][$date])) {
            $processedData[$studentKey]['daily_attendance'][$date] = [
                'sessions' => [],
                'status' => 'Unknown',
                'late' => false
            ];
        }

        // Store session data - ensure attendanceStatus is properly formatted
        $attendanceStatus = $row['attendanceStatus'];
        if ($attendanceStatus === '1' || strtolower($attendanceStatus) === 'present') {
            $attendanceStatus = 'present';
        } else {
            $attendanceStatus = 'absent';
        }

        $processedData[$studentKey]['daily_attendance'][$date]['sessions'][$row['sessionId']] = $attendanceStatus;
    }

    // Debug first student
    if (!empty($processedData)) {
        $firstStudent = array_values($processedData)[0];
        echo "<!-- Debug: First student processed data: " . json_encode($firstStudent) . " -->";
    }

    // Calculate daily attendance status
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
                // Check if first session was missed (assuming session 1 is the first)
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

// Get unique dates for table headers
$allDates = [];
foreach ($processedData as $student) {
    $allDates = array_merge($allDates, array_keys($student['daily_attendance']));
}
$allDates = array_unique($allDates);
sort($allDates);

// If we have a date range specified, include all dates in that range
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
                            <label for="table_id">Student</label>
                            <select name="table_id" id="table_id">
                                <option value="all">All Students</option>
                                <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                                    <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $student['Id']; ?>" <?php echo (isset($_POST['table_id']) && $_POST['table_id'] == $student['Id']) ? 'selected' : ''; ?>>
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
                    </p>
                </div>

                <!-- Attendance Legend -->
                <div class="attendance-legend">
                    <div class="legend-item">
                        <div class="legend-color present"></div>
                        <span><strong>P</strong> - Present (Attended all sessions)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color absent"></div>
                        <span><strong>A</strong> - Absent (Missed all sessions)</span>
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
                                                $totalPresent++; // Late is still considered present
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

    <!-- Logout Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <h2>Confirm</h2>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button class="modal-btn yes" id="yesBtn">Yes</button>
                <button class="modal-btn cancel" id="cancelBtn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Logout functionality
            const logoutElements = document.querySelectorAll('[data-logout="true"], .logout-btn, a[href*="logout.php"]');

            logoutElements.forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.getElementById('logoutModal').style.display = 'flex';
                });
            });

            // Modal handlers
            const yesBtn = document.getElementById('yesBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const modal = document.getElementById('logoutModal');

            if (yesBtn) {
                yesBtn.onclick = () => window.location.href = "../login.php";
            }

            if (cancelBtn) {
                cancelBtn.onclick = () => modal.style.display = 'none';
            }

            if (modal) {
                modal.onclick = (e) => {
                    if (e.target === modal) modal.style.display = 'none';
                };
            }

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

        function exportToPDF() {
            const table = document.getElementById('attendanceTable');
            if (!table) {
                alert('No table found to export');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');

            doc.setFontSize(18);
            doc.text('Attendance Report', 20, 20);
            doc.setFontSize(12);
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 20, 30);
            doc.text('P = Present, A = Absent, L = Late, Partial = Partial, - = No Record', 20, 35);

            const rows = [];
            const headers = [];

            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });

            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    row.push(td.textContent.trim());
                });
                rows.push(row);
            });

            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 45,
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [248, 249, 250], textColor: [0, 0, 0] },
                columnStyles: { 0: { cellWidth: 50 }, 1: { cellWidth: 30 } }
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