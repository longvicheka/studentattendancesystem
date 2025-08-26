<?php
// admin_actions.php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}
include '../Includes/db.php';

$currentAdminId = $_SESSION['adminId'] ?? $_SESSION['userId'] ?? '';
$currentAdminName = $_SESSION['adminName'] ?? ($_SESSION['firstName'] . ' ' . $_SESSION['lastName'] ?? '');

// Check if current admin has super admin privileges
$isSuperAdmin = false;
if (isset($_SESSION['username'])) {
    $checkSuperAdmin = $conn->prepare("SELECT username FROM tbladmin WHERE username = ?");
    $checkSuperAdmin->bind_param("s", $_SESSION['username']);
    $checkSuperAdmin->execute();
    $result = $checkSuperAdmin->get_result();
    if ($row = $result->fetch_assoc()) {
        $isSuperAdmin = ($row['username'] === 'admin');
    }
}

// Handle GET requests (for fetching admin data)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_admin') {
        $username = $_GET['username'] ?? '';

        if (!empty($username)) {
            // Query using username as identifier
            $stmt = $conn->prepare("SELECT username, firstName, lastName, emailAddress, phone, createdAt FROM tbladmin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($admin = $result->fetch_assoc()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'admin' => $admin
                ]);
                exit();
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Admin not found'
        ]);
        exit();
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Update admin
        if (isset($_POST['update_admin'])) {
            $originalUsername = $_POST['original_username'] ?? '';
            $newUsername = trim($_POST['username']);
            $firstName = trim($_POST['firstName']);
            $lastName = trim($_POST['lastName']);
            $emailAddress = trim($_POST['emailAddress']);
            $phone = trim($_POST['phone']);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $currentUsername = $_SESSION['username'] ?? '';

            // Validation
            $errors = [];

            if (empty($originalUsername)) {
                $errors[] = "Invalid admin username";
            }

            // Prevent username changes
            if ($originalUsername !== $newUsername) {
                $errors[] = "Username cannot be changed. Please keep the original username.";
            }

            if (empty($newUsername)) {
                $errors[] = "Username is required";
            } elseif (strlen($newUsername) < 3) {
                $errors[] = "Username must be at least 3 characters";
            }

            if (empty($firstName) || empty($lastName)) {
                $errors[] = "First and last name are required";
            }

            if (empty($emailAddress)) {
                $errors[] = "Email is required";
            } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }

            // Prevent password changes for other admin accounts
            if (!empty($password) && $originalUsername !== $currentUsername) {
                $errors[] = "You can only change your own password. You cannot change other admin accounts' passwords.";
            }

            // Password validation (only if provided)
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $errors[] = "Password must be at least 6 characters";
                }

                if ($password !== $confirmPassword) {
                    $errors[] = "Passwords do not match";
                }
            }

            // Check if new username or email already exists (excluding current admin)
            if (empty($errors)) {
                $checkQuery = "SELECT username FROM tbladmin WHERE (username = ? OR emailAddress = ?) AND username != ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("sss", $newUsername, $emailAddress, $originalUsername);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    $errors[] = "Username or email already exists";
                }
            }

            if (empty($errors)) {
                // Build update query using username as identifier
                if (!empty($password)) {
                    // Update with password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $updateQuery = "UPDATE tbladmin SET username = ?, firstName = ?, lastName = ?, emailAddress = ?, phone = ?, password = ?, updatedAt = NOW(), updatedBy = ? WHERE username = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("ssssssss", $newUsername, $firstName, $lastName, $emailAddress, $phone, $hashedPassword, $currentAdminName, $originalUsername);
                } else {
                    // Update without password
                    $updateQuery = "UPDATE tbladmin SET username = ?, firstName = ?, lastName = ?, emailAddress = ?, phone = ?, updatedAt = NOW(), updatedBy = ? WHERE username = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("sssssss", $newUsername, $firstName, $lastName, $emailAddress, $phone, $currentAdminName, $originalUsername);
                }

                if ($updateStmt->execute()) {
                    // If updating current user's data, update session
                    if ($originalUsername == $_SESSION['username'] ?? '') {
                        $_SESSION['adminName'] = $firstName . ' ' . $lastName;
                        $_SESSION['username'] = $newUsername;
                    }

                    $_SESSION['success_message'] = 'Admin account updated successfully!';
                    header("Location: create_admin.php");
                    exit();
                } else {
                    $_SESSION['error_message'] = 'Error updating admin account: ' . $conn->error;
                    header("Location: create_admin.php");
                    exit();
                }
            } else {
                $_SESSION['error_message'] = implode('<br>', $errors);
                header("Location: create_admin.php");
                exit();
            }
    }

    // Delete admin
    if (isset($_POST['delete_admin'])) {
        $username = $_POST['admin_username'] ?? '';

        if (!$isSuperAdmin) {
            $_SESSION['error_message'] = 'Only super admin can delete admin accounts!';
            header("Location: create_admin.php");
            exit();
        }

        if (empty($username)) {
            $_SESSION['error_message'] = 'Invalid admin username';
            header("Location: create_admin.php");
            exit();
        }

        // Prevent deletion of super admin account
        if ($username === 'admin') {
            $_SESSION['error_message'] = 'Cannot delete super admin account!';
            header("Location: create_admin.php");
            exit();
        }

        // Prevent self-deletion
        if ($username == ($_SESSION['username'] ?? '')) {
            $_SESSION['error_message'] = 'Cannot delete your own account!';
            header("Location: create_admin.php");
            exit();
        }

        // Delete admin
        $deleteQuery = "DELETE FROM tbladmin WHERE username = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("s", $username);

        if ($deleteStmt->execute()) {
            $_SESSION['success_message'] = 'Admin account deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Error deleting admin account: ' . $conn->error;
        }

        header("Location: create_admin.php");
        exit();
    }
}

// If no valid action, redirect back
$_SESSION['error_message'] = 'Invalid request';
header("Location: create_admin.php");
exit();
?>