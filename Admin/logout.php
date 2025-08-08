<?php
session_start();
session_destroy(); // destroy session
echo "<script type = \"text/javascript\">
  window.location = (\"../index.php\");
  </script>";

?>

<script>
    document.querySelectorAll('click', function (e) {
        e.preventDefault();
        document.getElementById('logoutModal').style.display = 'flex';
    });

    document.getElementById('yesBtn').onclick = function () {
        window.location.href = "../login.php";
    };

    document.getElementById('cancelBtn').onclick = function () {
        document.getElementById('logoutModal').style.display = 'none';
    };
</script>