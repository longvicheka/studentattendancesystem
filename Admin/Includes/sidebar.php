<div class="container">
  <div class="sidebar">
    <div class="nav-section">
      <a href="index.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'index.php')
        echo 'active'; ?>">
        <div class="nav-icon"><i class="fa-solid fa-chart-column"></i></div>
        <div class="nav-text">Dashboard</div>
      </a>
    </div>

    <div class="nav-section">
      <div class="section-title">Manage</div>
      <a href="attendance.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'attendance.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-clipboard-user"></i>
        </div>
        <div class="nav-text">Attendance</div>
      </a>

      <a href="report.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'report.php')
        echo 'active'; ?>">
        <div class="nav-icon"><i class="fa-solid fa-file-lines"></i></div>
        <div class="nav-text">Report</div>
      </a>

      <a href="absent_response.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'absent_response.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-bell"></i>
        </div>
        <div class="nav-text">Absent Request</div>
      </a>
    </div>

    <div class="nav-section">
      <div class="section-title">Create</div>
      <a href="create_admin.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'create_admin.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-circle-user"></i>
        </div>
        <div class="nav-text">Add Admin</div>
      </a>
    </div>

    <div class="nav-section">
      <div class="section-title">System</div>
      <a href="../login.php" class="nav-item logout-link <?php if (basename($_SERVER['PHP_SELF']) == 'logout.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-right-from-bracket"></i>
        </div>
        <div class="nav-text">Logout</div>
      </a>
    </div>
  </div>