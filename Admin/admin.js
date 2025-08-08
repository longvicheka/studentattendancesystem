// Show modal when logout is clicked
  document.getElementById('logoutLink').onclick = function (e) {
    e.preventDefault();
    document.getElementById('logoutModal').style.display = 'flex';
  };
  // Yes: go to logout.php
  document.getElementById('yesBtn').onclick = function () {
    window.location.href = "../login.php";
  };
  // Cancel: hide modal
  document.getElementById('cancelBtn').onclick = function () {
    document.getElementById('logoutModal').style.display = 'none';
  };