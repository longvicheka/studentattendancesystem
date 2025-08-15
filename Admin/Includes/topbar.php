<?php

// Use the correct session variable for admin ID
$adminId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
$fullName = '';
$userType = '';

if ($adminId) {
  $query = "SELECT firstName, lastName, userType FROM tbladmin WHERE Id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $adminId);
  $stmt->execute();
  $rs = $stmt->get_result();
  if ($rs && $rs->num_rows > 0) {
    $rows = $rs->fetch_assoc();
    $fullName = $rows['firstName'] . " " . $rows['lastName'];
    $userType = $rows['userType'];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <header>
    <div class="header-content">
      <a href=".">
        <img src="../image/logo/EAMU.png" alt="EAMU Logo" class="logo" aria-hidden="true" />
      </a>
    </div>
    <div class="user-info">
      <span class="user-role"><?php echo ($userType); ?></span>
      <span class="user-name"><?php echo ($fullName); ?></span>
    </div>
  </header>
</body>

</html>