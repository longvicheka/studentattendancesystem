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