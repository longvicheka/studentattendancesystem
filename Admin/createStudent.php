<?php
include '../Includes/db.php';
include '../Includes/session.php';

if (isset($_POST['save'])) {

    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $otherName = $_POST['otherName'];

    $admissionNumber = $_POST['admissionNumber'];
    $classId = $_POST['classId'];
    $classArmId = $_POST['classArmId'];
    $dateCreated = date("Y-m-d");

    $query = mysqli_query($conn, "select * from tblstudents where admissionNumber ='$admissionNumber'");
    $ret = mysqli_fetch_array($query);

    if ($ret > 0) {

        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>This Email Address Already Exists!</div>";
    } else {

        $query = mysqli_query($conn, "insert into tblstudents(firstName,lastName,otherName,admissionNumber,password,classId,classArmId,dateCreated) 
    value('$firstName','$lastName','$otherName','$admissionNumber','12345','$classId','$classArmId','$dateCreated')");

        if ($query) {

            $statusMsg = "<div class='alert alert-success'  style='margin-right:700px;'>Created Successfully!</div>";

        } else {
            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
        }
    }
}
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
        <h1 class="page-title">Student</h1>
        <div class="breadcrumb"><a href="./">Home /</a> <a href="Admin/student.php"> Student /</a>Create Student
        </div>
    </div>

    <div class="container-fluid" id="container-wrapper">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Create Students</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="./">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Students</li>
            </ol>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <!-- Form Basic -->
                <div class="card mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Create Students</h6>
                        <?php echo $statusMsg; ?>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group row mb-3">
                                <div class="col-xl-6">
                                    <label class="form-control-label">Firstname<span
                                            class="text-danger ml-2">*</span></label>
                                    <input type="text" class="form-control" name="firstName"
                                        value="<?php echo $row['firstName']; ?>" id="exampleInputFirstName">
                                </div>
                                <div class="col-xl-6">
                                    <label class="form-control-label">Lastname<span
                                            class="text-danger ml-2">*</span></label>
                                    <input type="text" class="form-control" name="lastName"
                                        value="<?php echo $row['lastName']; ?>" id="exampleInputFirstName">
                                </div>
                            </div>
                            <div class="form-group row mb-3">
                                <div class="col-xl-6">
                                    <label class="form-control-label">Other Name<span
                                            class="text-danger ml-2">*</span></label>
                                    <input type="text" class="form-control" name="otherName"
                                        value="<?php echo $row['otherName']; ?>" id="exampleInputFirstName">
                                </div>
                                <div class="col-xl-6">
                                    <label class="form-control-label">Admission Number<span
                                            class="text-danger ml-2">*</span></label>
                                    <input type="text" class="form-control" required name="admissionNumber"
                                        value="<?php echo $row['admissionNumber']; ?>" id="exampleInputFirstName">
                                </div>
                            </div>
                            <div class="form-group row mb-3">
                                <div class="col-xl-6">
                                    <label class="form-control-label">Select Class<span
                                            class="text-danger ml-2">*</span></label>
                                    <?php
                                    $qry = "SELECT * FROM tblclass ORDER BY className ASC";
                                    $result = $conn->query($qry);
                                    $num = $result->num_rows;
                                    if ($num > 0) {
                                        echo ' <select required name="classId" onchange="classArmDropdown(this.value)" class="form-control mb-3">';
                                        echo '<option value="">--Select Class--</option>';
                                        while ($rows = $result->fetch_assoc()) {
                                            echo '<option value="' . $rows['Id'] . '" >' . $rows['className'] . '</option>';
                                        }
                                        echo '</select>';
                                    }
                                    ?>
                                </div>
                                <div class="col-xl-6">
                                    <label class="form-control-label">Class Arm<span
                                            class="text-danger ml-2">*</span></label>
                                    <?php
                                    echo "<div id='txtHint'></div>";
                                    ?>
                                </div>
                            </div>
                            <?php
                            if (isset($Id)) {
                                ?>
                                <button type="submit" name="update" class="btn btn-warning">Update</button>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <?php
                            } else {
                                ?>
                                <button type="submit" name="save" class="btn btn-primary">Save</button>
                                <?php
                            }
                            ?>
                        </form>
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


        </script>
</body>

</html>