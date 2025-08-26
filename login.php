<?php
session_start();
include 'Includes/db.php';

$error = "";

if (isset($_POST['login'])) {
    $userType = $_POST['userType'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($userType == "Administrator") {
        $query = "SELECT * FROM tbladmin WHERE username = ? OR emailAddress = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs && $rs->num_rows > 0) {
            $row = $rs->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['userId'] = $row['Id'];
                $_SESSION['adminId'] = $row['Id']; // Add adminId for admin management files
                $_SESSION['firstName'] = $row['firstName'];
                $_SESSION['lastName'] = $row['lastName'];
                $_SESSION['adminName'] = $row['firstName'] . ' ' . $row['lastName']; // Add adminName for admin management files
                $_SESSION['emailAddress'] = $row['emailAddress'];
                $_SESSION['username'] = $row['username']; // Add username for admin management files
                $_SESSION['userType'] = 'Administrator';

                header("Location: Admin/index.php");
                exit();
            } else {
                $error = "<div class='alert alert-danger'>Invalid Username / Password!</div>";
            }
        } else {
            $error = "<div class='alert alert-danger'>Invalid Username / Password!</div>";
        }
    } elseif ($userType == "Student") { // Use elseif instead of if
        $query = "SELECT * FROM tblstudent WHERE studentId = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs && $rs->num_rows > 0) {
            $row = $rs->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["studentId"] = $row["studentId"];
                $_SESSION["firstName"] = $row["firstName"];
                $_SESSION["lastName"] = $row["lastName"];
                $_SESSION["email"] = $row["email"];
                $_SESSION["userType"] = "Student";

                header("Location: Student/dashboard.php");
                exit();
            } else {
                $error = "<div class='alert alert-danger'>Invalid Username / Password!</div>";
            }
        } else {
            $error = "<div class='alert alert-danger'>Invalid Username / Password!</div>";
        }
    } else {
        $error = "<div class='alert alert-danger'>Please select a user type!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>

<body>
    <header>
        <div>
            <img src="image/logo/EAMU.png" alt="EAMU Logo" class="logo" />
        </div>
    </header>
    <main>
        <section class="intro">
            <h1>East Asia Management University</h1>
            <h2>Student Attendance</h2>
        </section>

        <form method="post">
            <label for="userType">Select your role</label>
            <select id="userType" name="userType" required>
                <option value="">Select your role</option>
                <option value="Administrator">Administrator</option>
                <option value="Student">Student</option>
            </select>

            <label for="username">Username / Email</label>
            <input id="username" name="username" type="text" placeholder="Username or Email" required />

            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="Password" required />

            <button type="submit" name="login">Sign in</button>

            <?php if (!empty($error))
                echo $error; ?>
        </form>
    </main>
</body>

</html>