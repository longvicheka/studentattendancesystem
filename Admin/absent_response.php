<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}
include '../Includes/db.php';

$message = '';
$adminName = $_SESSION['adminName'] ?? ($_SESSION['firstName'] . ' ' . $_SESSION['lastName'] ?? 'Administrator');

// Handle AJAX requests for getting request details
if (isset($_GET['action']) && $_GET['action'] === 'get_request') {
    $requestId = $_GET['id'] ?? '';
    if (!empty($requestId)) {
        $sql = "SELECT * FROM tblabsentrequest WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            header('Content-Type: application/json');
            echo json_encode($row);
            exit();
        }
    }
    header('HTTP/1.1 404 Not Found');
    exit();
}

// Handle request status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $requestId = $_POST['request_id'] ?? '';
        $newStatus = $_POST['status'] ?? '';
        $adminResponse = $_POST['admin_response'] ?? '';

        if (!empty($requestId) && !empty($newStatus)) {
            $sql = "UPDATE tblabsentrequest SET status = ?, adminResponse = ?, approvedBy = ?, updatedAt = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sssi", $newStatus, $adminResponse, $adminName, $requestId);
                if ($stmt->execute()) {
                    // Return JSON response for AJAX
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Request status updated successfully!']);
                        exit();
                    }
                    $message = '<div class="alert alert-success">Request status updated successfully!</div>';
                } else {
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Error updating request: ' . $stmt->error]);
                        exit();
                    }
                    $message = '<div class="alert alert-danger">Error updating request: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            }
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';

// Build the query based on filters
$whereConditions = [];
$params = [];
$types = '';

if ($statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($dateFilter === 'today') {
    $whereConditions[] = "DATE(createdAt) = CURDATE()";
} elseif ($dateFilter === 'week') {
    $whereConditions[] = "createdAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($dateFilter === 'month') {
    $whereConditions[] = "createdAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM tblabsentrequest";

$statsResult = $conn->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Permission - Administrative Portal</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid;
        }

        .stat-card.total {
            border-left-color: #8A0054;
        }

        .stat-card.pending {
            border-left-color: #FFC107;
        }

        .stat-card.approved {
            border-left-color: #28A745;
        }

        .stat-card.rejected {
            border-left-color: #DC3545;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filters-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            flex: 1;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
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
        }

        .requests-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .requests-table th,
        .requests-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .requests-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .requests-table tr:hover {
            background-color: #f5f5f5;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
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
            max-width: 600px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group select,
        .form-group textarea,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .request-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #495057;
        }

        .detail-value {
            flex: 1;
            color: #333;
        }

        .grid-container {
            margin: 24px 64px;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
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
            .filters-row {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .requests-table {
                font-size: 12px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .grid-container {
                margin: 20px;
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
            <h1 class="page-title">Manage Absent Requests</h1>
            <p>Review and respond to student absence requests</p>
        </div>

        <?php echo $message; ?>

        <!-- Statistics -->
        <div class="grid-container">
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card approved">
                    <div class="stat-number"><?php echo $stats['approved']; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card rejected">
                    <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" id="filterForm">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status
                                </option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>
                                    Pending
                                </option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>
                                    Approved
                                </option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>
                                    Rejected
                                </option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="date">Date Range</label>
                            <select name="date" id="date">
                                <option value="all" <?php echo $dateFilter === 'all' ? 'selected' : ''; ?>>All Time
                                </option>
                                <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today
                                </option>
                                <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>Last 7 Days
                                </option>
                                <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>Last 30
                                    Days
                                </option>
                            </select>
                        </div>

                        <!-- <div class="filter-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-filter"></i> Filter
                            </button>
                        </div> -->
                    </div>
                </form>
            </div>

            <!-- Requests Table -->
            <div class="requests-card">
                <h2>Absence Requests</h2>
                <?php
                // Get filtered requests
                $query = "SELECT * FROM tblabsentrequest $whereClause ORDER BY 
                      CASE 
                          WHEN status = 'pending' THEN 1 
                          WHEN status = 'approved' THEN 2 
                          ELSE 3 
                      END,
                      createdAt DESC";

                $stmt = $conn->prepare($query);
                if ($stmt && !empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }

                if ($stmt) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query($query);
                }

                if ($result && $result->num_rows > 0) {
                    echo '<div style="overflow-x: auto;">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Request Date</th>
                                    <th>Absence Period</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="requestsTableBody">';

                    while ($row = $result->fetch_assoc()) {
                        $startDate = new DateTime($row['startDate']);
                        $endDate = new DateTime($row['endDate']);
                        $days = $startDate->diff($endDate)->days + 1;

                        $period = $startDate->format('M d, Y');
                        if ($row['startDate'] != $row['endDate']) {
                            $period .= ' - ' . $endDate->format('M d, Y');
                        }

                        echo '<tr id="row-' . $row['id'] . '">
                            <td>' . htmlspecialchars($row['studentId']) . '</td>
                            <td>' . htmlspecialchars($row['studentName']) . '</td>
                            <td>' . date('M d, Y', strtotime($row['createdAt'])) . '</td>
                            <td>' . $period . '</td>
                            <td>' . $days . ' day' . ($days > 1 ? 's' : '') . '</td>
                            <td>' . htmlspecialchars($row['reason']) . '</td>
                            <td>
                                <span class="status-badge status-' . $row['status'] . '" id="status-' . $row['id'] . '">
                                    ' . ucfirst($row['status']) . '
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewRequest(' . $row['id'] . ')" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>';

                        if ($row['status'] === 'pending') {
                            echo '<button onclick="quickApprove(' . $row['id'] . ')" class="btn btn-success btn-sm" id="approve-btn-' . $row['id'] . '">
                                <i class="fa-solid fa-check"></i> Approve
                              </button>
                              <button onclick="quickReject(' . $row['id'] . ')" class="btn btn-danger btn-sm" id="reject-btn-' . $row['id'] . '">
                                <i class="fa-solid fa-times"></i> Reject
                              </button>';
                        }

                        echo '</div>
                          </td>
                        </tr>';
                    }
                    echo '</tbody></table></div>';
                } else {
                    echo '<div style="text-align: center; padding: 40px; color: #6c757d;">
                        <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <h3>No Requests Found</h3>
                        <p>No absence requests match your current filters.</p>
                      </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal for viewing/updating requests -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2 id="modalTitle">Request Details</h2>
            </div>
            <div id="modalBody">
                <div class="loading">
                    <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;

        // View request details
        function viewRequest(requestId) {
            currentRequestId = requestId;
            document.getElementById('requestModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Request Details';
            document.getElementById('modalBody').innerHTML = '<div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

            // Fetch request details
            fetch(`?action=get_request&id=${requestId}`)
                .then(response => response.json())
                .then(data => {
                    displayRequestDetails(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalBody').innerHTML = '<div class="alert alert-danger">Error loading request details.</div>';
                });
        }

        // Display request details in modal
        function displayRequestDetails(request) {
            const startDate = new Date(request.startDate);
            const endDate = new Date(request.endDate);
            const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

            const period = startDate.toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });
            const periodEnd = request.startDate !== request.endDate ?
                ' - ' + endDate.toLocaleDateString('en-US', {
                    year: 'numeric', month: 'short', day: 'numeric'
                }) : '';

            let modalContent = `
                <div class="request-details">
                    <div class="detail-row">
                        <div class="detail-label">Student ID:</div>
                        <div class="detail-value">${escapeHtml(request.studentId)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Student Name:</div>
                        <div class="detail-value">${escapeHtml(request.studentName)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">${escapeHtml(request.email)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value">${escapeHtml(request.phone)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Absence Period:</div>
                        <div class="detail-value">${period}${periodEnd}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Duration:</div>
                        <div class="detail-value">${days} day${days > 1 ? 's' : ''}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Reason:</div>
                        <div class="detail-value">${escapeHtml(request.reason)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Current Status:</div>
                        <div class="detail-value">
                            <span class="status-badge status-${request.status}">
                                ${request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                            </span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Submitted:</div>
                        <div class="detail-value">${new Date(request.createdAt).toLocaleString()}</div>
                    </div>`;

            if (request.adminResponse) {
                modalContent += `
                    <div class="detail-row">
                        <div class="detail-label">Admin Response:</div>
                        <div class="detail-value">${escapeHtml(request.adminResponse)}</div>
                    </div>`;
            }

            if (request.approvedBy) {
                modalContent += `
                    <div class="detail-row">
                        <div class="detail-label">Processed By:</div>
                        <div class="detail-value">${escapeHtml(request.approvedBy)}</div>
                    </div>`;
            }

            if (request.updatedAt && request.updatedAt !== request.createdAt) {
                modalContent += `
                    <div class="detail-row">
                        <div class="detail-label">Last Updated:</div>
                        <div class="detail-value">${new Date(request.updatedAt).toLocaleString()}</div>
                    </div>`;
            }

            modalContent += `</div>`;

            // Add action form if request is pending
            if (request.status === 'pending') {
                modalContent += `
                    <form id="updateStatusForm">
                        <input type="hidden" name="request_id" value="${request.id}">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="ajax" value="1">
                        
                        <div class="form-group">
                            <label for="status">Decision</label>
                            <select name="status" id="modalStatus" required>
                                <option value="">Select Decision</option>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_response">Response/Comments (Optional)</label>
                            <textarea name="admin_response" id="modalResponse" 
                                placeholder="Add any comments or feedback for the student..."></textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fa-solid fa-save"></i> Update Status
                            </button>
                        </div>
                    </form>`;
            } else {
                modalContent += `
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">Close</button>
                    </div>`;
            }

            document.getElementById('modalBody').innerHTML = modalContent;

            // Add form submit handler if form exists
            const form = document.getElementById('updateStatusForm');
            if (form) {
                form.addEventListener('submit', handleStatusUpdate);
            }
        }

        // Quick approve function
        function quickApprove(requestId) {
            if (confirm('Are you sure you want to approve this absence request?')) {
                updateRequestStatus(requestId, 'approved', '');
            }
        }

        // Quick reject function
        function quickReject(requestId) {
            const reason = prompt('Please provide a reason for rejection (optional):');
            if (reason !== null) { // User didn't cancel
                updateRequestStatus(requestId, 'rejected', reason);
            }
        }

        // Handle status update from modal form
        function handleStatusUpdate(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const requestId = formData.get('request_id');
            const status = formData.get('status');
            const response = formData.get('admin_response');

            if (!status) {
                alert('Please select a decision.');
                return;
            }

            // Disable submit button
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';

            updateRequestStatus(requestId, status, response);
        }

        // Update request status via AJAX
        function updateRequestStatus(requestId, status, adminResponse) {
            const formData = new FormData();
            formData.append('request_id', requestId);
            formData.append('status', status);
            formData.append('admin_response', adminResponse);
            formData.append('update_status', '1');
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the table row
                        updateTableRow(requestId, status);

                        // Update statistics
                        updateStatistics();

                        // Close modal
                        closeModal();

                        // Show success message
                        showMessage(data.message, 'success');
                    } else {
                        showMessage(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred while updating the request.', 'danger');
                })
                .finally(() => {
                    // Re-enable submit button if modal is still open
                    const submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Update Status';
                    }
                });
        }

        // Update table row after status change
        function updateTableRow(requestId, newStatus) {
            const row = document.getElementById(`row-${requestId}`);
            if (!row) return;

            // Update status badge
            const statusBadge = document.getElementById(`status-${requestId}`);
            if (statusBadge) {
                statusBadge.className = `status-badge status-${newStatus}`;
                statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            }

            // Remove action buttons if no longer pending
            if (newStatus !== 'pending') {
                const approveBtn = document.getElementById(`approve-btn-${requestId}`);
                const rejectBtn = document.getElementById(`reject-btn-${requestId}`);

                if (approveBtn) approveBtn.remove();
                if (rejectBtn) rejectBtn.remove();
            }
        }

        // Update statistics after status change
        function updateStatistics() {
            fetch('?action=get_stats')
                .then(response => response.json())
                .then(data => {
                    // Update stat cards
                    document.querySelector('.stat-card.total .stat-number').textContent = data.total || 0;
                    document.querySelector('.stat-card.pending .stat-number').textContent = data.pending || 0;
                    document.querySelector('.stat-card.approved .stat-number').textContent = data.approved || 0;
                    document.querySelector('.stat-card.rejected .stat-number').textContent = data.rejected || 0;
                })
                .catch(error => {
                    console.error('Error updating statistics:', error);
                });
        }

        // Show message
        function showMessage(message, type) {
            // Remove existing messages
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            // Create new message
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = message;

            // Insert after heading content
            const headingContent = document.querySelector('.heading-content');
            headingContent.parentNode.insertBefore(alertDiv, headingContent.nextSibling);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Close modal
        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
            currentRequestId = null;
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('requestModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Escape HTML function
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Auto-submit filter form when filters change
        document.getElementById('status').addEventListener('change', function () {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('date').addEventListener('change', function () {
            document.getElementById('filterForm').submit();
        });

        // Refresh page periodically to show new requests (every 5 minutes)
        setInterval(function () {
            // Only refresh if no modal is open
            if (document.getElementById('requestModal').style.display !== 'block') {
                updateStatistics();
            }
        }, 300000); // 5 minutes
    </script>

</body>

</html>

<?php
// Handle AJAX request for statistics update
if (isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM tblabsentrequest";

    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult ? $statsResult->fetch_assoc() : [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];

    header('Content-Type: application/json');
    echo json_encode($stats);
    exit();
}
?>