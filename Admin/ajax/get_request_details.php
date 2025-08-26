<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

include '../../Includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit();
}

$requestId = intval($_GET['id']);

$sql = "SELECT ar.*, s.email, s.phoneNumber 
        FROM tblabsentrequest ar 
        LEFT JOIN tblstudent s ON ar.studentId = s.userId 
        WHERE ar.id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$stmt->bind_param("i", $requestId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit();
}

$request = $result->fetch_assoc();
$stmt->close();

echo json_encode(['success' => true, 'request' => $request]);
?>
