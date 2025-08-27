<div class="container">
    <div class="sidebar">
        <div class="nav-section">
            <a href="dashboard.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'dashboard.php')
                echo 'active'; ?>">
                <div class="nav-icon"><i class="fa-solid fa-chart-column"></i></div>
                <div class="nav-text">Dashboard</div>
            </a>
        </div>

        <div class="nav-section">
            <div class="section-title">Manage</div>
            <a href="absent_request.php" class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'absent_request.php')
                echo 'active'; ?>">
                <div class="nav-icon">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>
                <div class="nav-text">Request Permission</div>
            </a>
        </div>

        <div class="nav-section">
            <div class="section-title">System</div>
<a href="#" class="nav-item logout-link <?php if (basename($_SERVER['PHP_SELF']) == 'logout.php')
                echo 'active'; ?>" onclick="showLogoutConfirmation(event)">
                <div class="nav-icon">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </div>
                <div class="nav-text">Logout</div>
            </a>
        </div>
    </div>