<?php
// create_admin.php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}
include '../Includes/db.php';

$message = '';
$adminName = $_SESSION['adminName'] ?? ($_SESSION['firstName'] . ' ' . $_SESSION['lastName'] ?? 'Administrator');
$currentUsername = $_SESSION['username'] ?? '';

// Check if current admin has super admin privileges (username 'admin')
$isSuperAdmin = false;
if ($currentUsername) {
$checkSuperAdmin = $conn->prepare("SELECT username FROM tbladmin WHERE username = ?");
$checkSuperAdmin->bind_param("s", $currentUsername);
    $checkSuperAdmin->execute();
    $result = $checkSuperAdmin->get_result();
    if ($row = $result->fetch_assoc()) {
        $isSuperAdmin = ($row['username'] === 'admin');
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_admin'])) {
        $username = trim($_POST['username']);
        $firstName = trim($_POST['firstName']);
        $lastName = trim($_POST['lastName']);
        $emailAddress = trim($_POST['emailAddress']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validation
        $errors = [];

        if (empty($username)) {
            $errors[] = "Username is required";
        } elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }

        if (empty($firstName)) {
            $errors[] = "First name is required";
        }

        if (empty($lastName)) {
            $errors[] = "Last name is required";
        }

        if (empty($emailAddress)) {
            $errors[] = "emailAddress is required";
        } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }

        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        }

        // Check if username or emailAddress already exists
        if (empty($errors)) {
            $checkQuery = "SELECT id FROM tbladmin WHERE username = ? OR emailAddress = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("ss", $username, $emailAddress);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $errors[] = "Username or emailAddress already exists";
            }
        }

        if (empty($errors)) {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new admin
            $insertQuery = "INSERT INTO tbladmin (username, firstName, lastName, emailAddress, phone, password, createdAt, createdBy) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("sssssss", $username, $firstName, $lastName, $emailAddress, $phone, $hashedPassword, $currentUsername);

            if ($insertStmt->execute()) {
                $message = '<div class="alert alert-success">Admin account created successfully!</div>';
                // Clear form data
                $_POST = [];
            } else {
                $message = '<div class="alert alert-danger">Error creating admin account: ' . $conn->error . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
        }
    }
}

// Get all admins for the table
$adminQuery = "SELECT * FROM tbladmin ORDER BY createdAt DESC";
$adminResult = $conn->query($adminQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Administrative Portal</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

    <style>
        .page-container {
            margin: 24px 64px 0 64px;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-card,
        .admins-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-card h2,
        .admins-card h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #8A0054;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #8A0054;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-primary {
            background: #8A0054;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .admin-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .admin-badge {
            background: #8A0054;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .super-admin-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .current-admin {
            background: #d4edda;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            border-bottom: 2px solid #8A0054;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }

        .close:hover {
            color: #000;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .page-container {
                margin: 20px;
            }

            .admin-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include "Includes/topbar.php"; ?>
    <?php include "Includes/sidebar.php"; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="heading-content">
            <h1 class="page-title">Admin Management</h1>
            <p>Create, edit, and manage administrator accounts</p>
        </div>

        <?php echo $message; ?>

        <div class="page-container">
            <div class="admin-grid">
                <!-- Create Admin Form -->
                <div class="form-card">
                    <h2><i class="fa-solid fa-user-plus"></i> Create New Admin</h2>

                    <form method="POST" id="createAdminForm">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="firstName">First Name *</label>
                            <input type="text" id="firstName" name="firstName" required
                                value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="lastName">Last Name *</label>
                            <input type="text" id="lastName" name="lastName" required
                                value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="emailAddress">Email *</label>
                            <input type="emailAddress" id="emailAddress" name="emailAddress" required
                                value="<?php echo htmlspecialchars($_POST['emailAddress'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required minlength="6">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    minlength="6">
                            </div>
                        </div>

                        <button type="submit" name="create_admin" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Create Admin Account
                        </button>
                    </form>
                </div>

                <!-- Admin List -->
                <div class="admins-card">
                    <h2><i class="fa-solid fa-users-gear"></i> Administrator Accounts</h2>

                    <?php if ($adminResult && $adminResult->num_rows > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Admin Details</th>
                                        <th>Contact</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($admin = $adminResult->fetch_assoc()): ?>
                                        <tr <?php echo ($admin['username'] == $currentUsername) ? 'class="current-admin"' : ''; ?>>
                                            <td>
                                                <div style="font-weight: 600;">
                                                    <?php echo htmlspecialchars($admin['username']); ?>
                                                    <?php if ($admin['username'] == $currentUsername): ?>
                                                        <span class="admin-badge">YOU</span>
                                                    <?php endif; ?>
                                                    <?php if ($admin['username'] === 'admin'): ?>
                                                        <span class="super-admin-badge">SUPER ADMIN</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 12px; color: #6c757d;">
                                            <td>
                                                <div><?php echo htmlspecialchars($admin['emailAddress']); ?></div>
                                                <?php if (!empty($admin['phone'])): ?>
                                                    <div style="font-size: 12px; color: #6c757d;">
                                                        <?php echo htmlspecialchars($admin['phone']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($admin['createdAt'])); ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button onclick="editAdmin('<?php echo $admin['username']; ?>')"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="fa-solid fa-edit"></i> Edit
                                                    </button>

                                                    <?php if ($isSuperAdmin && $admin['username'] != $currentUsername): ?>
                                                        <button
                                                            onclick="deleteAdmin('<?php echo htmlspecialchars($admin['username']); ?>', '<?php echo htmlspecialchars($admin['username']); ?>')"
                                                            class="btn btn-danger btn-sm">
                                                            <i class="fa-solid fa-trash"></i> Delete
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #6c757d;">
                            <i class="fa-solid fa-users" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <h3>No Administrators</h3>
                            <p>No administrator accounts found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div id="editAdminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <div class="modal-header">
                <h2>Edit Administrator</h2>
            </div>
            <div id="editModalBody">
                <div class="loading" style="text-align: center; padding: 20px;">
                    <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteAdminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <div class="modal-header">
                <h2>Delete Administrator</h2>
            </div>
            <div id="deleteModalBody">
                <p>Are you sure you want to delete this administrator account?</p>
                <div style="background: #f8d7da; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <strong>Warning:</strong> This action cannot be undone. The administrator will lose access
                    immediately.
                </div>
                <form method="POST" id="deleteAdminForm">
                    <input type="hidden" name="admin_username" id="deleteAdminId">
                    <input type="hidden" name="delete_admin" value="1">

                    <div class="modal-actions">
                        <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash"></i> Delete Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // JavaScript functions for create_admin.php

        // Edit admin function
        function editAdmin(username) {
            document.getElementById('editAdminModal').style.display = 'block';
            document.getElementById('editModalBody').innerHTML = 
                '<div class="loading" style="text-align: center; padding: 20px;">' +
                '<i class="fa-solid fa-spinner fa-spin"></i> Loading admin data...</div>';

            // Fetch admin data using username
            fetch(`admin_actions.php?action=get_admin&username=${encodeURIComponent(username)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.admin) {
                        displayEditForm(data.admin);
                    } else {
                        console.error('Error loading admin:', data.message || 'Unknown error');
                        document.getElementById('editModalBody').innerHTML =
                            '<div class="alert alert-danger">Error loading admin data: ' + 
                            (data.message || 'Admin not found') + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('editModalBody').innerHTML =
                        '<div class="alert alert-danger">Error loading admin data: ' + 
                        error.message + '</div>';
                });
        }

        // Display edit form
        function displayEditForm(admin) {
            const currentUsername = '<?php echo $currentUsername; ?>';
            const isOwnAccount = admin.username === currentUsername;
            
            let passwordFields = '';
            if (isOwnAccount) {
                passwordFields = `
                    <div class="form-group">
                        <label for="edit_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="edit_password" name="password" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="edit_confirm_password">Confirm New Password</label>
                        <input type="password" id="edit_confirm_password" name="confirm_password" minlength="6">
                    </div>
                `;
            } else {
                passwordFields = `
                    <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border-color: #bee5eb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                        <i class="fa-solid fa-info-circle"></i> 
                        <strong>Note:</strong> You can only change your own password. To update other admin accounts, contact the system administrator.
                    </div>
                `;
            }

            const formHtml = `
                <form method="POST" action="admin_actions.php" id="editAdminForm">
                    <input type="hidden" name="original_username" value="${escapeHtml(admin.username)}">
                    <input type="hidden" name="update_admin" value="1">
                    
                    <div class="form-group">
                        <label for="edit_username">Username *</label>
                        <input type="text" id="edit_username" name="username" required 
                            value="${escapeHtml(admin.username)}" readonly>
                        <div style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                            <i class="fa-solid fa-info-circle"></i> Username cannot be changed
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_firstName">First Name *</label>
                        <input type="text" id="edit_firstName" name="firstName" required 
                            value="${escapeHtml(admin.firstName || '')}">
                    </div>

                    <div class="form-group">
                        <label for="edit_lastName">Last Name *</label>
                        <input type="text" id="edit_lastName" name="lastName" required 
                            value="${escapeHtml(admin.lastName || '')}">
                    </div>

                    <div class="form-group">
                        <label for="edit_emailAddress">Email Address *</label>
                        <input type="email" id="edit_emailAddress" name="emailAddress" required 
                            value="${escapeHtml(admin.emailAddress)}">
                    </div>

                    <div class="form-group">
                        <label for="edit_phone">Phone Number</label>
                        <input type="tel" id="edit_phone" name="phone" 
                            value="${escapeHtml(admin.phone || '')}">
                    </div>

                    ${passwordFields}
                    
                    <div class="modal-actions">
                        <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-save"></i> Update Admin
                        </button>
                    </div>
                </form>
            `;

            document.getElementById('editModalBody').innerHTML = formHtml;

            // Add form validation
            document.getElementById('editAdminForm').addEventListener('submit', function (e) {
                const password = document.getElementById('edit_password')?.value || '';
                const confirmPassword = document.getElementById('edit_confirm_password')?.value || '';

                if (password && password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        }

        // Delete admin function
        function deleteAdmin(username, displayName) {
            document.getElementById('deleteAdminId').value = username;
            document.getElementById('deleteModalBody').querySelector('p').innerHTML =
                `Are you sure you want to delete the administrator account for <strong>${escapeHtml(displayName)}</strong>?`;
            document.getElementById('deleteAdminModal').style.display = 'block';
        }
        
        // Close modals
        function closeEditModal() {
            document.getElementById('editAdminModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteAdminModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function (event) {
            const editModal = document.getElementById('editAdminModal');
            const deleteModal = document.getElementById('deleteAdminModal');

            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        // Form validation for create admin
        document.getElementById('createAdminForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });

        // Escape HTML function
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>