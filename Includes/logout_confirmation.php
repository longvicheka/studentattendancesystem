<script>
function showLogoutConfirmation(event) {
    event.preventDefault(); 
    document.getElementById('logoutOverlay').style.display = 'block'; 
    document.getElementById('logoutConfirmation').style.display = 'block'; 
}

function confirmLogout() {
    window.location.href = '../login.php';
}

function cancelLogout() {
    document.getElementById('logoutOverlay').style.display = 'none'; 
    document.getElementById('logoutConfirmation').style.display = 'none'; 
}
</script>

<!-- Overlay to prevent clicking other elements -->
<div id="logoutOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;"></div>

<div id="logoutConfirmation" class="logout-confirmation" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); z-index: 1000; min-width: 350px; text-align: center;">
    <h2 class="confirmation-title" style="margin: 0 0 15px 0; font-size: 24px; font-weight: 600;">Logout Confirmation</h2>
    <p class="confirmation-message" style="color: #666; margin: 0 0 25px 0; font-size: 16px; line-height: 1.5;">Are you sure you want to logout?</p>
    <div class="confirmation-buttons" style="display: flex; gap: 15px; justify-content: center;">
        <button onclick="confirmLogout()" class="btn btn-primary" style="background: #8a0054; border: none; color: white; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s;">Yes, Logout</button>
        <button onclick="cancelLogout()" class="btn btn-secondary" style="background: #6c757d; border: none; color: white; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s;">Cancel</button>
    </div>
</div>
