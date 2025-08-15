<?php
session_start();
include '../Includes/db.php';

// (Optional) helpful during debugging:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$cacheDir = '../cache/reports/';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$studentsQuery = "SELECT studentId, firstName, lastName FROM tblstudent ORDER BY firstName, lastName";
$studentsResult = $conn->query($studentsQuery);
if (!$studentsResult) {
    echo "Error in students query: " . $conn->error;
}

$subjectsQuery = "SELECT DISTINCT subjectCode, subjectName FROM tblsubject ORDER BY subjectCode";
$subjectsResult = $conn->query($subjectsQuery);
if (!$subjectsResult) {
    echo "Error in subjects query: " . $conn->error;
}

function loadCache($cacheFile)
{
    if (!file_exists($cacheFile)) return null;
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    if (!$cacheData) return null;
    // expire after 1 hour
    if (isset($cacheData['timestamp']) && (time() - $cacheData['timestamp']) > 3600) return null;
    return $cacheData;
}

function saveCache($cacheFile, $data, $hash)
{
    $cacheData = [
        'timestamp' => time(),
        'hash' => $hash,
        'data' => $data
    ];
    file_put_contents($cacheFile, json_encode($cacheData));
}

function getDatabaseHash($conn, $studentId = 'all', $subjectCode = 'all', $startDate = '', $endDate = '')
{
    $sql = "SELECT COUNT(*) as total_records, MAX(a.markedAt) as last_modified
            FROM tblstudent s
            LEFT JOIN tblattendance a ON s.studentId = a.studentId";
    $conds = [];
    $params = [];
    $types = '';

    if ($studentId !== 'all') {
        $conds[] = "s.studentId = ?";
        $params[] = $studentId;
        $types .= 's';
    }
    if ($subjectCode !== 'all') {
        $conds[] = "a.subjectCode = ?";
        $params[] = $subjectCode;
        $types .= 's';
    }
    if (!empty($startDate)) {
        $conds[] = "(a.markedAt IS NULL OR DATE(a.markedAt) >= ?)";
        $params[] = $startDate;
        $types .= 's';
    }
    if (!empty($endDate)) {
        $conds[] = "(a.markedAt IS NULL OR DATE(a.markedAt) <= ?)";
        $params[] = $endDate;
        $types .= 's';
    }

    if ($conds) {
        $sql .= " WHERE " . implode(" AND ", $conds);
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) return false;

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return md5(($row['total_records'] ?? '0') . ($row['last_modified'] ?? ''));
}

function fetchDatabaseData($conn, $studentId = 'all', $subjectCode = 'all', $startDate = '', $endDate = '')
{
    // Keep date filters in JOIN so LEFT JOIN remains left
    $sql = "
        SELECT
            s.studentId,
            s.firstName,
            s.lastName,
            ss.subjectCode,
            sub.subjectName,
            DATE(a.markedAt) AS attendance_date,
            a.sessionId,
            a.attendanceStatus
        FROM tblstudent s
        LEFT JOIN tblstudentsubject ss
            ON s.studentId = ss.studentId
        LEFT JOIN tblsubject sub
            ON ss.subjectCode = sub.subjectCode
        LEFT JOIN tblattendance a
            ON s.studentId = a.studentId
           AND ss.subjectCode = a.subjectCode
           AND (? = '' OR DATE(a.markedAt) >= ?)
           AND (? = '' OR DATE(a.markedAt) <= ?)
        WHERE (? = 'all' OR s.studentId = ?)
          AND (? = 'all' OR ss.subjectCode = ?)
        ORDER BY s.studentId, ss.subjectCode, attendance_date, a.sessionId
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("fetchDatabaseData(): prepare failed: " . $conn->error);
        return [];
    }

    $stmt->bind_param(
        "ssssssss",
        $startDate, $startDate,
        $endDate,   $endDate,
        $studentId, $studentId,
        $subjectCode, $subjectCode
    );

    if (!$stmt->execute()) {
        error_log("fetchDatabaseData(): execute failed: " . $stmt->error);
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();

    // First pass: bucket sessions per (student, subject, date)
    $buckets = []; 
    while ($row = $result->fetch_assoc()) {
        $sid  = $row['studentId'];
        $sc   = $row['subjectCode'];
        $date = $row['attendance_date'];
        $sess = $row['sessionId'];
        $stat = $row['attendanceStatus']; 

        if (empty($sid)) continue;

        // Only consider a subject when it actually exists
        if (empty($sc)) continue;

        if (!isset($buckets[$sid])) {
            $buckets[$sid] = [
                'student_info' => [
                    'studentId' => $sid,
                    'name' => trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')),
                ],
                'subjects' => []
            ];
        }

        if (!isset($buckets[$sid]['subjects'][$sc])) {
            $buckets[$sid]['subjects'][$sc] = [
                'subjectName' => $row['subjectName'],
                'days' => [] // per date
            ];
        }

        // If attendance row is missing a date or session id, skip
        if (!$date || !$sess) {
            continue;
        }

        if (!isset($buckets[$sid]['subjects'][$sc]['days'][$date])) {
            $buckets[$sid]['subjects'][$sc]['days'][$date] = [
                'sessions' => []
            ];
        }

        // Normalize status: treat anything not "present" as "absent"
        $normalized = (strtolower((string)$stat) === 'present') ? 'present' : 'absent';
        $buckets[$sid]['subjects'][$sc]['days'][$date]['sessions'][(int)$sess] = $normalized;
    }
    $stmt->close();

    // Second pass: compute final per-day status
    $reportData = []; 
    foreach ($buckets as $sid => $sdata) {
        $reportData[$sid] = [
            'student_info' => $sdata['student_info'],
            'subjects' => [],
            'overall_stats' => [
                'total_present' => 0,   
                'total_absent'  => 0,
                'total_late'    => 0,
                'total_sessions'=> 0, 
                'overall_percentage' => 0
            ]
        ];

        foreach ($sdata['subjects'] as $code => $info) {
            $presentDays = 0; // includes late
            $absentDays  = 0;
            $lateDays    = 0;
            $totalDays   = 0;

            foreach ($info['days'] as $date => $day) {
                $totalDays++;

                // Extract three sessions (may be missing some)
                $s1 = $day['sessions'][1] ?? null;
                $s2 = $day['sessions'][2] ?? null;
                $s3 = $day['sessions'][3] ?? null;

                $vals = array_values(array_filter([$s1, $s2, $s3], fn($v) => $v !== null));
                // If there are no session rows at all for that day, skip counting this "day"
                if (count($vals) === 0) {
                    $totalDays--;
                    continue;
                }

                $allPresent = (in_array('present', $vals) && !in_array('absent', $vals) && count($vals) === 3);
                $allAbsent  = (in_array('absent', $vals)  && !in_array('present', $vals) && count($vals) === 3);

                $isLate = (!$allAbsent) && (isset($s1) && $s1 === 'absent') && (in_array('present', $vals));

                if ($allPresent) {
                    $presentDays++;
                } elseif ($allAbsent) {
                    $absentDays++;
                } elseif ($isLate) {
                    $lateDays++;
                    $presentDays++; 
                } else {
                    $presentDays++;
                }
            }

            $pct = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

            $reportData[$sid]['subjects'][$code] = [
                'subjectName'   => $info['subjectName'],
                'present_count' => $presentDays,
                'absent_count'  => $absentDays,
                'late_count'    => $lateDays,
                'total_days'    => $totalDays,
                'percentage'    => $pct
            ];

            // Accumulate overall
            $reportData[$sid]['overall_stats']['total_present'] += $presentDays;
            $reportData[$sid]['overall_stats']['total_absent']  += $absentDays;
            $reportData[$sid]['overall_stats']['total_late']    += $lateDays;
            $reportData[$sid]['overall_stats']['total_sessions']+= $totalDays; // "sessions" in UI = days
        }

        // overall percentage = presentDays / totalDays
        $td = $reportData[$sid]['overall_stats']['total_sessions'];
        $tp = $reportData[$sid]['overall_stats']['total_present'];
        $reportData[$sid]['overall_stats']['overall_percentage'] = $td > 0 ? round(($tp / $td) * 100, 1) : 0;
    }

    return $reportData;
}

$reportData = [];
$reportGenerated = false;
$dataSource = '';

if (isset($_POST['generate_report'])) {
    $studentId   = $_POST['studentId']   ?? 'all';
    $subjectCode = $_POST['subjectCode'] ?? 'all';
    $startDate   = $_POST['start_date']  ?? '';
    $endDate     = $_POST['end_date']    ?? '';

    $cacheKey = md5($studentId . '_' . $subjectCode . '_' . $startDate . '_' . $endDate);
    $specificCacheFile = $cacheDir . 'attendance_cache_' . $cacheKey . '.json';

    $currentHash = getDatabaseHash($conn, $studentId, $subjectCode, $startDate, $endDate);

    if ($currentHash === false) {
        $reportData = fetchDatabaseData($conn, $studentId, $subjectCode, $startDate, $endDate);
        $dataSource = 'database (hash error)';
    } else {
        $cachedData = loadCache($specificCacheFile);
        if ($cachedData && isset($cachedData['hash']) && $cachedData['hash'] === $currentHash && empty($_POST['force_refresh'])) {
            $reportData = $cachedData['data'];
            $dataSource = 'cache (up to date)';
        } else {
            $reportData = fetchDatabaseData($conn, $studentId, $subjectCode, $startDate, $endDate);
            $dataSource = 'database (fresh fetch)';
            if ($reportData !== false) {
                saveCache($specificCacheFile, $reportData, $currentHash);
            }
        }
    }

    $reportGenerated = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Attendance Report</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

    <!-- Export libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <!-- <style>
        .high-attendance { color: #1a7f37; font-weight: 600; }
        .medium-attendance { color: #b58900; font-weight: 600; }
        .low-attendance { color: #b00020; font-weight: 600; }
        .overall-column { background: #f0f0f0; }
        .no-subject { text-align:center; color:#888; }
        .percentage-value { text-align:center; }
        .count-column { text-align:center; }
        .percentage-column { text-align:center; }
        .generate-btn, .action-btn { cursor:pointer; }
    </style> -->
</head>
<body>
    <?php include "Includes/topbar.php"; ?>
    <?php include "Includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="heading-content">
            <h1 class="page-title">Detailed Attendance Report</h1>
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
                                        <option value="<?php echo htmlspecialchars($student['studentId']); ?>"
                                            <?php echo (isset($_POST['studentId']) && $_POST['studentId'] == $student['studentId']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No students found</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="subjectCode">Subject</label>
                            <select name="subjectCode" id="subjectCode">
                                <option value="all">All Subjects</option>
                                <?php if ($subjectsResult && $subjectsResult->num_rows > 0): ?>
                                    <?php while ($subject = $subjectsResult->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($subject['subjectCode']); ?>"
                                            <?php echo (isset($_POST['subjectCode']) && $_POST['subjectCode'] == $subject['subjectCode']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subjectCode'] . ' - ' . $subject['subjectName']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No subjects found</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                   value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                        </div>

                        <div class="filter-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                   value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
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
                    <h2 class="report-title">Detailed Attendance Report</h2>
                    <p class="report-subtitle">
                        Generated on <?php echo date('F j, Y'); ?>
                        <?php if (!empty($_POST['start_date']) || !empty($_POST['end_date'])): ?>
                            | Period: <?php echo $_POST['start_date'] ?: 'Start'; ?> to <?php echo $_POST['end_date'] ?: 'End'; ?>
                        <?php endif; ?>
                        <?php if (!empty($_POST['subjectCode']) && $_POST['subjectCode'] !== 'all'): ?>
                            | Subject: <?php echo htmlspecialchars($_POST['subjectCode']); ?>
                        <?php endif; ?>
                        <?php if (!empty($dataSource)): ?>
                            | Source: <?php echo htmlspecialchars($dataSource); ?>
                        <?php endif; ?>
                    </p>
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

                <?php if (!empty($reportData)): ?>
                    <div class="table-container">
                        <table class="attendance-table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th rowspan="2">Student ID</th>
                                    <th rowspan="2">Student Name</th>
                                    <?php
                                    // Determine all unique subjects present in data
                                    $allSubjects = [];
                                    foreach ($reportData as $studentData) {
                                        foreach ($studentData['subjects'] as $subjectCode => $subjectInfo) {
                                            if (!isset($allSubjects[$subjectCode])) {
                                                $allSubjects[$subjectCode] = $subjectInfo['subjectName'];
                                            }
                                        }
                                    }
                                    foreach ($allSubjects as $subjectCode => $subjectName): ?>
                                        <th class="percentage-column"><?php echo htmlspecialchars($subjectCode); ?></th>
                                    <?php endforeach; ?>
                                    <th colspan="5" class="overall-column">Overall</th>
                                </tr>
                                <tr>
                                    <?php foreach ($allSubjects as $subjectCode => $subjectName): ?>
                                        <th class="percentage-column">%</th>
                                    <?php endforeach; ?>
                                    <th class="count-column">Total P</th>
                                    <th class="count-column">Total A</th>
                                    <th class="count-column">Total L</th>
                                    <th class="count-column">Total Sessions</th>
                                    <th class="percentage-column">Overall %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $studentId => $studentData): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($studentData['student_info']['studentId']); ?></td>
                                        <td><?php echo htmlspecialchars($studentData['student_info']['name']); ?></td>

                                        <?php foreach ($allSubjects as $subjectCode => $subjectName): ?>
                                            <?php if (isset($studentData['subjects'][$subjectCode])): ?>
                                                <?php 
                                                    $percentage = $studentData['subjects'][$subjectCode]['percentage'];
                                                    $cssClass = ($percentage >= 75) ? 'high-attendance' : (($percentage >= 50) ? 'medium-attendance' : 'low-attendance');
                                                ?>
                                                <td class="percentage-value <?php echo $cssClass; ?>"><?php echo $percentage; ?>%</td>
                                            <?php else: ?>
                                                <td class="no-subject">-</td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                        <td class="count-column overall-column"><?php echo (int)$studentData['overall_stats']['total_present']; ?></td>
                                        <td class="count-column overall-column"><?php echo (int)$studentData['overall_stats']['total_absent']; ?></td>
                                        <td class="count-column overall-column"><?php echo (int)$studentData['overall_stats']['total_late']; ?></td>
                                        <td class="count-column overall-column"><?php echo (int)$studentData['overall_stats']['total_sessions']; ?></td>
                                        <?php 
                                            $overallPercentage = $studentData['overall_stats']['overall_percentage'];
                                            $overallCssClass = ($overallPercentage >= 75) ? 'high-attendance' : (($overallPercentage >= 50) ? 'medium-attendance' : 'low-attendance');
                                        ?>
                                        <td class="percentage-value overall-column <?php echo $overallCssClass; ?>"><?php echo $overallPercentage; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="report-legend" style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                        <h4>Legend:</h4>
                        <p><strong>%</strong> = Attendance Percentage for each subject (Late counts as Present)</p>
                        <p>
                            <span class="high-attendance">■ High Attendance (75%+)</span> |
                            <span class="medium-attendance">■ Medium Attendance (50–74%)</span> |
                            <span class="low-attendance">■ Low Attendance (&lt; 50%)</span>
                        </p>
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

        function refreshReport() {
            const form = document.getElementById('reportForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'force_refresh';
            input.value = Date.now();
            form.appendChild(input);
            form.submit();
        }

        function exportToExcel() {
            const table = document.getElementById('attendanceTable');
            if (!table) {
                alert('No table found to export');
                return;
            }
            const wb = XLSX.utils.table_to_book(table, { sheet: "Detailed Attendance Report" });
            const filename = 'detailed_attendance_report_' + new Date().toISOString().split('T')[0] + '.xlsx';
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
            doc.text('Detailed Attendance Report', 20, 20);
            doc.setFontSize(12);
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 20, 30);

            // Build headers and rows
            const rows = [];
            const headerCells = [];
            const subHeaderCells = [];

            const theadRows = table.querySelectorAll('thead tr');
            theadRows[1].querySelectorAll('th').forEach(th => {
                subHeaderCells.push(th.textContent.trim());
            });

            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => row.push(td.textContent.trim()));
                rows.push(row);
            });

            doc.autoTable({
                head: [subHeaderCells],
                body: rows,
                startY: 40,
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [248, 249, 250], textColor: [0, 0, 0] },
                columnStyles: {
                    0: { cellWidth: 20 },
                    1: { cellWidth: 30 }
                }
            });

            const filename = 'detailed_attendance_report_' + new Date().toISOString().split('T')[0] + '.pdf';
            doc.save(filename);
        }

        function printReport() {
            const table = document.getElementById('attendanceTable');
            if (!table) {
                alert('No table found to print');
                return;
            }

            const columnCount = table.querySelector('thead tr:last-child').children.length;
            let fontSize = '8px';
            let cellPadding = '3px 2px';

            if (columnCount > 20) {
                fontSize = '6px';
                cellPadding = '2px 1px';
            } else if (columnCount > 15) {
                fontSize = '7px';
                cellPadding = '2px 1px';
            } else if (columnCount > 10) {
                fontSize = '8px';
                cellPadding = '3px 2px';
            } else {
                fontSize = '10px';
                cellPadding = '4px 3px';
            }

            const printStyles = `
                <style type="text/css" media="print">
                    @page { size: A4 landscape; margin: 10mm; }
                    body * { visibility: hidden; }
                    .report-container, .report-container * { visibility: visible; }
                    .report-container { position: absolute; left: 0; top: 0; width: 100% !important; margin: 0; padding: 5px; }
                    .filter-section, .report-actions, .breadcrumb, .heading-content { display: none !important; }
                    .attendance-table {
                        width: 100% !important; font-size: ${fontSize} !important;
                        border-collapse: collapse; table-layout: fixed;
                    }
                    .attendance-table th, .attendance-table td {
                        padding: ${cellPadding} !important; border: 1px solid #333 !important;
                        word-wrap: break-word; font-size: ${fontSize} !important; line-height: 1.1 !important; overflow: hidden;
                    }
                    .attendance-table th:first-child, .attendance-table td:first-child { width: 8% !important; }
                    .attendance-table th:nth-child(2), .attendance-table td:nth-child(2) { width: 12% !important; }
                    .percentage-column, .count-column { width: auto !important; text-align: center !important; }
                    .report-title { font-size: 14px !important; margin-bottom: 3px !important; }
                    .report-subtitle { font-size: 9px !important; margin-bottom: 8px !important; }
                    .report-legend { font-size: 7px !important; margin-top: 8px !important; padding: 3px !important; }
                    .table-container { overflow: visible !important; }
                    .high-attendance { font-weight: bold !important; }
                    .low-attendance { text-decoration: underline !important; }
                    .overall-column { background-color: #e8e8e8 !important; -webkit-print-color-adjust: exact; }
                </style>
            `;

            const head = document.head;
            const printStyleElement = document.createElement('div');
            printStyleElement.innerHTML = printStyles;
            head.appendChild(printStyleElement.firstElementChild);

            window.print();

            setTimeout(() => {
                const addedStyle = head.querySelector('style[media="print"]:last-of-type');
                if (addedStyle) head.removeChild(addedStyle);
            }, 1000);
        }
    </script>
</body>
</html>
