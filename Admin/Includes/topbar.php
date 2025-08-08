<?php 
  $query = "SELECT * FROM tbladmin WHERE Id = ".$_SESSION['userId']."";
  $rs = $conn->query($query);
  $num = $rs->num_rows;
  $rows = $rs->fetch_assoc();
  $fullName = $rows['firstName']." ".$rows['lastName'];
  $userType = $rows['userType'];

?>

<!DOCTYPE html>
<html lang="en">
  <body>
    <header>
      <div class="header-content">
        <a href=".">
          <img
          src="../image/logo/EAMU.png"
          alt="EAMU Logo"
          class="logo"
          aria-hidden="true"
        />
        </a>
      </div>
      <div class="user-info">
        <span class="user-role"><?php echo $userType;?></span>
        <span class="user-name"><?php echo $fullName;?></span>
      </div>
    </header>
</body>
</html>