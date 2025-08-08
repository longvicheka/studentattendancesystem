<?php
include '../Includes/db.php';
include '../Includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Student</title>
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
            <div class="heading">
                <h1 class="page-title">Student</h1>
                <div class="breadcrumb"><a href="./">Home /</a> Student</div>
            </div>
            <div class="heading headingBtn">
                <button class="createBtn" id="createBtn">Create Student</button>
            </div>

        </div>

        <div class="list-container">
            <div class="searchbar">
                <h2 class="list-title">All Students</h2>
                <!-- <form action="" method="get" class="student-search-form" autocomplete="off">
                    <input type="text" name="search" class="student-search-input" placeholder="Search by name or ID"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                    <button type="submit" class="student-search-btn"><i class="fa fa-search"></i></i></button>
                </form> -->
                <div class="table-wrapper">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Firstname</th>
                                <th>Lastname</th>
                                <th>Student ID</th>
                                <th>Academic Year</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
                            $where = '';
                            if ($search !== '') {
                                $search_esc = $conn->real_escape_string($search);
                                $where = "WHERE firstName LIKE '%$search_esc%' OR lastName LIKE '%$search_esc%' OR studentId LIKE '%$search_esc%'";
                            }
                            $query = "SELECT Id, firstName, lastName, userId AS studentId, academicYear, email FROM tblstudent";
                            $rs = $conn->query($query);
                            if ($rs && $rs->num_rows > 0) {
                                while ($row = $rs->fetch_assoc()) {
                                    echo "<tr>
                            <td>{$row['firstName']}</td>
                            <td>{$row['lastName']}</td>
                            <td>{$row['studentId']}</td>
                            <td>Year {$row['academicYear']}</td>
                            <td>{$row['email']}</td>
                            <td><a href='?action=edit&Id=" . $row['Id'] . "'><i class='fas fa-fw fa-edit'></i></a><a href='?action=delete&Id=" . $rows['Id'] . "'><i class='fas fa-fw fa-trash'></i></a></td>
                            </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='no-record'>No students found.</td</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
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

        document.getElementById('createBtn').onclick = function () {
            window.location.href = "createStudent.php";
        };
    </script>
</body>

</html>