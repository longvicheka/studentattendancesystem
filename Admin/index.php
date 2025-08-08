<?php
include '../Includes/db.php';
include '../Includes/session.php';


//     $query = "SELECT tblclass.className,tblclassarms.classArmName 
//     FROM tblclassteacher
//     INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
//     INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
//     Where tblclassteacher.Id = '$_SESSION[userId]'";

//     $rs = $conn->query($query);
//     $num = $rs->num_rows;
//     $rrw = $rs->fetch_assoc();


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
        <div class="stat-value">120</div>
        <div class="stat-change positive">11% increase</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Absent</div>
          <div class="stat-period">Today</div>
        </div>
        <div class="stat-value">10</div>
        <div class="stat-change positive">9% increase</div>
      </div>

      <!-- <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Late</div>
          <div class="stat-period">Today</div>
        </div>
        <div class="stat-value">5</div>
        <div class="stat-change positive">2% increase</div>
      </div> -->

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Total Student</div>
          <div class="stat-period">This Year</div>
        </div>
        <div class="stat-value">145</div>
        <div class="stat-change positive">5% increase</div>
      </div>

      <!-- <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Total Lecturer</div>
          <div class="stat-period">This Year</div>
        </div>
        <div class="stat-value">11</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-title">Total Subject</div>
          <div class="stat-period">This Year</div>
        </div>
        <div class="stat-value">24</div>
      </div> -->
    </div>

    <!-- Recent Activities -->
    <div class="chart-grid">
      <div class="chart-card">
        <div class="chart-header">
          <div class="chart-title">Reports</div>
          <div class="chart-period">Today</div>
        </div>
        <div class="chart-container">
          <canvas id="lineChart"></canvas>
        </div>
      </div>

      <!-- New flex row for activities and attendance report -->
      <div>
        <!-- Activity Card -->
        <div class="activities-card">
          <div class="activities-header">
            <div class="activities-title">Recent Activities</div>
            <div class="chart-period">Today</div>
          </div>
          <div class="activity-item">
            <div class="activity-time">38 min</div>
            <div class="activity-text">New lecturer added</div>
          </div>
          <div class="activity-item">
            <div class="activity-time">42 min</div>
            <div class="activity-text">ISB checked</div>
          </div>
          <div class="activity-item">
            <div class="activity-time">1hr</div>
            <div class="activity-text">Absence application</div>
          </div>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-title">Attendance Report</div>
            <div class="chart-period">This Month</div>
          </div>
          <div class="radar-chart-container">
            <canvas id="radarChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="modal-overlay" id="logoutModal" style="display:none;">
      <div class="modal-box">
        <h2 style="margin-bottom: 12px">Confirm</h2>
        <p>Are you sure you want to logout?</p>
        <div class="modal-buttons">
          <button class="modal-btn yes" id="yesBtn">Yes</button>
          <button class="modal-btn cancel" id="cancelBtn">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</body>

</html>

<script>
  const lineCtx = document.getElementById('lineChart').getContext('2d');
  const lineChart = new Chart(lineCtx, {
    type: 'line',
    data: {
      labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
      datasets: [{
        label: 'Present',
        data: [30, 35, 40, 45, 50, 80],
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true
      }, {
        label: 'Absent',
        data: [15, 12, 30, 35, 18, 22],
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
        tension: 0.4,
        fill: true
      }, {
        label: 'Late',
        data: [12, 25, 35, 30, 32, 50],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: '#f1f5f9'
          }
        },
        x: {
          grid: {
            color: '#f1f5f9'
          }
        }
      }
    }
  });

  const radarCtx = document.getElementById('radarChart').getContext('2d');
  const radarChart = new Chart(radarCtx, {
    type: 'radar',
    data: {
      labels: ['BM', 'Accounting', 'A&F', 'H&T', 'LSC'],
      datasets: [{
        label: 'Attendance',
        data: [80, 90, 70, 85, 95],
        backgroundColor: 'rgba(59, 130, 246, 0.2)',
        borderColor: '#3b82f6',
        borderWidth: 2,
        pointBackgroundColor: '#3b82f6'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        r: {
          beginAtZero: true
        }
      }
    }
  });

  document.querySelectorAll('.logout-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById('logoutModal').style.display = 'flex';
    });
  });

  document.getElementById('yesBtn').onclick = function () {
    window.location.href = "../login.php";
  };
  document.getElementById('cancelBtn').onclick = function () {
    document.getElementById('logoutModal').style.display = 'none';
  };
</script>