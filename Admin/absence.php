<?php
include '../Includes/db.php';
include '../Includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Absence</title>
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
        <div class="heading-content">
            <h1 class="page-title">Absence</h1>
            <div class="breadcrumb"><a href="./">Home /</a> Absence</div>
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

    <script>
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
</body>

</html>