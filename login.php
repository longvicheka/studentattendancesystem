<?php
session_start();
include 'Includes/db.php';

$error = "";

if(isset($_POST['login'])){
    $userType = $_POST['userType'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if($userType == "Administrator"){
        $query = "SELECT * FROM tbladmin WHERE username = '$username' OR emailAddress = '$username'";
        $rs = $conn->query($query);
        if($rs && $rs->num_rows > 0){
            $row = $rs->fetch_assoc();
            if(password_verify($password, $row['password'])){
                $_SESSION['userId'] = $row['Id'];
                $_SESSION['firstName'] = $row['firstName'];
                $_SESSION['lastName'] = $row['lastName'];
                $_SESSION['emailAddress'] = $row['emailAddress'];
                $_SESSION['userType'] = $row['userType'];
                header("Location: Admin/index.php");
                exit();
            }
        }
        $error = "<div class='alert alert-danger' role='alert'>Invalid Username / Password!</div>";
    }
    // Add similar logic for Lecturer/Student later
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <link rel="stylesheet" href="style.css" />
    <link
      href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
      rel="stylesheet"
    />
  </head>
  <body>
    <header>
      <div>
        <img src="image/logo/EAMU.png" alt="EAMU Logo" class="logo" aria-hidden="true" />
      </div>
    </header>
    <main>
      <section class="intro" aria-label="University description heading">
        <h1>East Asia Management University</h1>
        <h2>Student Attendance</h2>
      </section>
      <form method="post" aria-label="Login form for students and staff">
        <label for="role">Select your role</label>
        <select id="userType" name="userType" required aria-required="true">
          <option value="" disabled selected>Select your role</option>
          <option value="Administrator">Administrator</option>
          <option value="Lecturer">Lecturer</option>
          <option value="Student">Student</option>
        </select>

        <label for="username">Username / Email</label>
        <input
          id="username"
          name="username"
          type="text"
          placeholder="Username or Email"
          autocomplete="username"
          required
          aria-required="true"
        />

        <label for="password">Password</label>
        <input
          id="password"
          name="password"
          type="password"
          placeholder="Password"
          autocomplete="current-password"
          required
          aria-required="true"
        />

        <div class="checkbox-container">
          <input id="remember" name="remember" type="checkbox" />
          <label for="remember" style="margin: 0">Remember me</label>
        </div>

        <button type="submit" value="Login" name="login">Sign in</button>

        <p class="forgot-password" aria-live="polite">Forgot password?</p>

        <p class="register-text">
          Don&apos;t have an account?
          <a href="register.html" tabindex="0">Register here</a>
        </p>
        <?php if(!empty($error)) echo $error; ?>  
      </form>
    </main>
  </body>
</html>