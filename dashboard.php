<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: loginpage.html");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
    <a href="logout.php">Logout</a>
</body>
</html>

<script>
    const lineCtx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00'],
                datasets: [{
                    label: 'Present',
                    data: [30, 35, 40, 45, 50, 80, 55],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Absent',
                    data: [15, 12, 30, 35, 18, 22, 12],
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Late',
                    data: [12, 25, 35, 30, 32, 50, 40],
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

        </script>