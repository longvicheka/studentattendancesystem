<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
  header("Location: ../index.php");
  exit();
}

// include '../Includes/session.php';
include '../Includes/db.php';

// Set timezone
date_default_timezone_set('Asia/Phnom_Penh');
$today = date('Y-m-d');

// Get total active students
$totalStudentsQuery = "SELECT COUNT(*) as total FROM tblstudent WHERE isActive = 1";
$totalStudentsResult = $conn->query($totalStudentsQuery);
$totalStudents = $totalStudentsResult ? $totalStudentsResult->fetch_assoc()['total'] : 0;

// Get today's attendance statistics
$presentQuery = "SELECT COUNT(DISTINCT a.studentId) as present 
                 FROM tblattendance a 
                 INNER JOIN tblstudent s ON a.studentId = s.studentId 
                 WHERE DATE(a.markedAt) = ? AND s.isActive = 1 AND a.attendanceStatus = 'present'";
$presentStmt = $conn->prepare($presentQuery);
if (!$presentStmt) {
  die("Prepare failed: " . $conn->error);
}
$presentStmt->bind_param("s", $today);
$presentStmt->execute();
$presentResult = $presentStmt->get_result();
$totalPresent = $presentResult ? $presentResult->fetch_assoc()['present'] : 0;

$absentQuery = "SELECT COUNT(DISTINCT a.studentId) as absent 
                FROM tblattendance a 
                INNER JOIN tblstudent s ON a.studentId = s.studentId 
                WHERE DATE(a.markedAt) = ? AND s.isActive = 1 AND a.attendanceStatus = 'absent'";
$absentStmt = $conn->prepare($absentQuery);
$absentStmt->bind_param("s", $today);
$absentStmt->execute();
$absentResult = $absentStmt->get_result();
$totalAbsent = $absentResult ? $absentResult->fetch_assoc()['absent'] : 0;

$lateQuery = "SELECT COUNT(DISTINCT a.studentId) as late 
              FROM tblattendance a 
              INNER JOIN tblstudent s ON a.studentId = s.studentId 
              WHERE DATE(a.markedAt) = ? AND s.isActive = 1 AND a.attendanceStatus = 'late'";
$lateStmt = $conn->prepare($lateQuery);
$lateStmt->bind_param("s", $today);
$lateStmt->execute();
$lateResult = $lateStmt->get_result();
$totalLate = $lateResult ? $lateResult->fetch_assoc()['late'] : 0;

// Get session-wise attendance for today's graph
$sessionAttendanceQuery = "
    SELECT 
        a.sessionId,
        a.attendanceStatus,
        COUNT(*) as count
    FROM tblattendance a
    INNER JOIN tblstudent s ON a.studentId = s.studentId
    WHERE DATE(a.markedAt) = ? AND s.isActive = 1
    GROUP BY a.sessionId, a.attendanceStatus
    ORDER BY a.sessionId, a.attendanceStatus";

$sessionStmt = $conn->prepare($sessionAttendanceQuery);
$sessionStmt->bind_param("s", $today);
$sessionStmt->execute();
$sessionResult = $sessionStmt->get_result();

// Initialize session data
$sessionData = [
  1 => ['present' => 0, 'absent' => 0, 'late' => 0],
  2 => ['present' => 0, 'absent' => 0, 'late' => 0],
  3 => ['present' => 0, 'absent' => 0, 'late' => 0]
];

// Process session attendance data
if ($sessionResult) {
  while ($row = $sessionResult->fetch_assoc()) {
    $sessionId = $row['sessionId'];
    $status = $row['attendanceStatus'];
    $count = $row['count'];

    if (isset($sessionData[$sessionId]) && isset($sessionData[$sessionId][$status])) {
      $sessionData[$sessionId][$status] = $count;
    }
  }
}

// Calculate percentage changes (comparing to yesterday)
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Yesterday's present count
$yesterdayPresentQuery = "SELECT COUNT(DISTINCT a.studentId) as present 
                         FROM tblattendance a 
                         INNER JOIN tblstudent s ON a.studentId = s.studentId 
                         WHERE DATE(a.markedAt) = ? AND s.isActive = 1 AND a.attendanceStatus = 'present'";
$yesterdayPresentStmt = $conn->prepare($yesterdayPresentQuery);
$yesterdayPresentStmt->bind_param("s", $yesterday);
$yesterdayPresentStmt->execute();
$yesterdayPresentResult = $yesterdayPresentStmt->get_result();
$yesterdayPresent = $yesterdayPresentResult ? $yesterdayPresentResult->fetch_assoc()['present'] : 0;

// Yesterday's absent count
$yesterdayAbsentQuery = "SELECT COUNT(DISTINCT a.studentId) as absent 
                        FROM tblattendance a 
                        INNER JOIN tblstudent s ON a.studentId = s.studentId 
                        WHERE DATE(a.markedAt) = ? AND s.isActive = 1 AND a.attendanceStatus = 'absent'";
$yesterdayAbsentStmt = $conn->prepare($yesterdayAbsentQuery);
$yesterdayAbsentStmt->bind_param("s", $yesterday);
$yesterdayAbsentStmt->execute();
$yesterdayAbsentResult = $yesterdayAbsentStmt->get_result();
$yesterdayAbsent = $yesterdayAbsentResult ? $yesterdayAbsentResult->fetch_assoc()['absent'] : 0;

// Calculate percentage changes
function calculatePercentageChange($today, $yesterday)
{
  if ($yesterday == 0) {
    return $today > 0 ? 100 : 0;
  }
  return round((($today - $yesterday) / $yesterday) * 100, 1);
}

$presentChange = calculatePercentageChange($totalPresent, $yesterdayPresent);
$absentChange = calculatePercentageChange($totalAbsent, $yesterdayAbsent);

// Get last week's total students for comparison
$lastWeek = date('Y-m-d', strtotime('-7 days'));
$lastWeekStudentsQuery = "SELECT COUNT(*) as total FROM tblstudent WHERE isActive = 1 AND createdAt <= ?";
$lastWeekStudentsStmt = $conn->prepare($lastWeekStudentsQuery);
$lastWeekStudentsStmt->bind_param("s", $lastWeek);
$lastWeekStudentsStmt->execute();
$lastWeekStudentsResult = $lastWeekStudentsStmt->get_result();
$lastWeekTotal = $lastWeekStudentsResult ? $lastWeekStudentsResult->fetch_assoc()['total'] : $totalStudents;

$studentChange = calculatePercentageChange($totalStudents, $lastWeekTotal);

// Convert session data to JSON for JavaScript
$sessionDataJson = json_encode($sessionData);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Dashboard</title>
  <link rel="stylesheet" href="../style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <?php include "Includes/topbar.php"; ?>
  <?php include "Includes/sidebar.php"; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Dashboard Content -->
    <div class="heading-content">
      <h1 class="page-title">Dashboard</h1>
      <div class="breadcrumb"><a href="./">Dashboard</a></div>
    </div>

    <!-- Stats Cards -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Present</div>
          <div class="stat-period">Today</div>
        </div>
        <div class="stat-value"><?php echo $totalPresent; ?></div>
        <div class="stat-change <?php echo $presentChange >= 0 ? 'positive' : 'negative'; ?>">
          <?php echo abs($presentChange); ?>% <?php echo $presentChange >= 0 ? 'increase' : 'decrease'; ?>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Absent</div>
          <div class="stat-period">Today</div>
        </div>
        <div class="stat-value"><?php echo $totalAbsent; ?></div>
        <div class="stat-change <?php echo $absentChange >= 0 ? 'negative' : 'positive'; ?>">
          <?php echo abs($absentChange); ?>% <?php echo $absentChange >= 0 ? 'increase' : 'decrease'; ?>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Total Student</div>
          <div class="stat-period">Active</div>
        </div>
        <div class="stat-value"><?php echo $totalStudents; ?></div>
        <div class="stat-change <?php echo $studentChange >= 0 ? 'positive' : 'negative'; ?>">
          <?php echo abs($studentChange); ?>% <?php echo $studentChange >= 0 ? 'increase' : 'decrease'; ?>
        </div>
      </div>
    </div>

    <!-- Recent Activities -->
    <div class="chart-grid">
      <div class="chart-card">
        <div class="chart-header">
          <div class="chart-title">Attendance Report</div>
          <div class="chart-period">Today - Session Wise</div>
        </div>
        <div class="chart-container">
          <canvas id="lineChart"></canvas>
        </div>
      </div>

      <div>
        <!-- Activity Card -->
        <div class="activities-card">
          <div class="activities-header">
            <div class="activities-title">Today's Summary</div>
            <div class="chart-period"><?php echo date('M j, Y'); ?></div>
          </div>
          <div class="activity-item">
            <div class="activity-time">
              <i class="fas fa-check-circle" style="color: #10b981;"></i>
            </div>
            <div class="activity-text"><?php echo $totalPresent; ?> students present</div>
          </div>
          <div class="activity-item">
            <div class="activity-time">
              <i class="fas fa-times-circle" style="color: #ef4444;"></i>
            </div>
            <div class="activity-text"><?php echo $totalAbsent; ?> students absent</div>
          </div>
          <div class="activity-item">
            <div class="activity-time">
              <i class="fas fa-clock" style="color: #f59e0b;"></i>
            </div>
            <div class="activity-text"><?php echo $totalLate; ?> students late</div>
          </div>
          <div class="activity-item">
            <div class="activity-time">
              <i class="fas fa-users" style="color: #6366f1;"></i>
            </div>
            <div class="activity-text">
              <?php echo round(($totalPresent / max($totalStudents, 1)) * 100, 1); ?>% attendance rate
            </div>
          </div>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-title">Attendance Breakdown</div>
            <div class="chart-period">Today's Sessions</div>
          </div>
          <div class="radar-chart-container">
            <canvas id="doughnutChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div id="logoutConfirmation" class="logout-confirmation" style="display: none;">
      <h2 class="confirmation-title">Logout Confirmation</h2>
      <p class="confirmation-message">Are you sure you want to logout?</p>
      <div class="confirmation-buttons">
        <button id="confirmLogout" class="btn btn-primary">Yes, Logout</button>
        <button id="cancelLogout" class="btn btn-secondary">Cancel</button>
      </div>
    </div>
  </div>

  <script>
    // Get session data from PHP
    const sessionData = <?php echo $sessionDataJson; ?>;

    // Prepare data for line chart
    const sessions = ['Session 1', 'Session 2', 'Session 3'];
    const presentData = [];
    const absentData = [];
    const lateData = [];

    for (let i = 1; i <= 3; i++) {
      presentData.push(sessionData[i].present);
      absentData.push(sessionData[i].absent);
      lateData.push(sessionData[i].late);
    }

    // Line Chart for Session-wise Attendance
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    const lineChart = new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: sessions,
        datasets: [{
          label: 'Present',
          data: presentData,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.4,
          fill: true,
          borderWidth: 3,
          pointBackgroundColor: '#10b981',
          pointBorderColor: '#10b981',
          pointBorderWidth: 2,
          pointRadius: 6
        }, {
          label: 'Absent',
          data: absentData,
          borderColor: '#ef4444',
          backgroundColor: 'rgba(239, 68, 68, 0.1)',
          tension: 0.4,
          fill: true,
          borderWidth: 3,
          pointBackgroundColor: '#ef4444',
          pointBorderColor: '#ef4444',
          pointBorderWidth: 2,
          pointRadius: 6
        }, {
          label: 'Late',
          data: lateData,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245, 158, 11, 0.1)',
          tension: 0.4,
          fill: true,
          borderWidth: 3,
          pointBackgroundColor: '#f59e0b',
          pointBorderColor: '#f59e0b',
          pointBorderWidth: 2,
          pointRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: 'bold'
              }
            }
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: 'white',
            bodyColor: 'white',
            borderColor: 'rgba(255, 255, 255, 0.2)',
            borderWidth: 1
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: '#f1f5f9',
              drawBorder: false
            },
            ticks: {
              stepSize: 1,
              font: {
                size: 11
              }
            }
          },
          x: {
            grid: {
              color: '#f1f5f9',
              drawBorder: false
            },
            ticks: {
              font: {
                size: 11
              }
            }
          }
        },
        interaction: {
          mode: 'nearest',
          intersect: false
        }
      }
    });

    // Doughnut Chart for Overall Attendance Breakdown
    const totalPresent = <?php echo $totalPresent; ?>;
    const totalAbsent = <?php echo $totalAbsent; ?>;
    const totalLate = <?php echo $totalLate; ?>;

    const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
    const doughnutChart = new Chart(doughnutCtx, {
      type: 'doughnut',
      data: {
        labels: ['Present', 'Absent', 'Late'],
        datasets: [{
          data: [totalPresent, totalAbsent, totalLate],
          backgroundColor: [
            '#10b981',
            '#ef4444',
            '#f59e0b'
          ],
          borderColor: [
            '#10b981',
            '#ef4444',
            '#f59e0b'
          ],
          borderWidth: 2,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 15,
              font: {
                size: 12,
                weight: 'bold'
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const total = totalPresent + totalAbsent + totalLate;
                const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
              }
            }
          }
        },
        cutout: '60%'
      }
    });

    setTimeout(function () {
      location.reload();
    }, 300000);

  </script>
</body>

</html>