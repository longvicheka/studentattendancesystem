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

      <!-- <a href="absence.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'absence.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div class="nav-text">Absence</div>
      </a> -->

      <a href="report.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'report.php')
        echo 'active'; ?>">
        <div class="nav-icon"><i class="fa-solid fa-file-lines"></i></div>
        <div class="nav-text">Report</div>
      </a>
    </div>

    <!-- <div class="nav-section">
      <div class="section-title">Users</div>
      <a href="lecturer.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'lecturer.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-chalkboard-user"></i>
        </div>
        <div class="nav-text">Lecturer</div>
      </a>
      <a href="student.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'student.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="nav-text">Student</div>
      </a>
      <a href="subject.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'subject.php')
        echo 'active'; ?>">
        <div class="nav-icon"><i class="fa-solid fa-book"></i></div>
        <div class="nav-text">Subject</div>
      </a>
    </div> -->

    <div class="nav-section">
      <div class="section-title">System</div>
      <a href="Admin/logout.php" class="nav-item logout-link <?php if (basename($_SERVER['PHP_SELF']) == 'logout.php')
        echo 'active'; ?>">
        <div class="nav-icon">
          <i class="fa-solid fa-right-from-bracket"></i>
        </div>
        <div class="nav-text">Logout</div>
      </a>
    </div>
  </div>