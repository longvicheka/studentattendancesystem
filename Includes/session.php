<?php
session_start();

if (!isset($_SESSION['studentId']) or !isset($_SESSION['userType'])) {
  echo "<script type = \"text/javascript\">
  window.location = (\"../Admin/index.php\");
  </script>";

}
?>