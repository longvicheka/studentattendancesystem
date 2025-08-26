<?php
session_start();
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student') {
    header("Location: ../login.php");
    exit();
}
include '../Includes/db.php';

// Initialize variables
$message = '';
$studentId = $_SESSION['studentId'] ?? '';

// Initialize stats array with default values
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $studentId = $_SESSION['studentId'] ?? '';
    $studentName = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

    if (empty($startDate) || empty($endDate) || empty($reason)) {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    } else {
        // Insert new request
        $sql = "INSERT INTO tblabsentrequest (studentId, studentName, startDate, endDate, reason, status, createdAt) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssss", $studentId, $studentName, $startDate, $endDate, $reason);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Your absence request has been submitted successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error submitting request: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}

// Get statistics
if (!empty($studentId)) {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM tblabsentrequest 
            WHERE studentId = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row) {
                $stats = $row;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Permission - Student Portal</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .grid-container {
            margin: 24px 64px 0 64px;
        }

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

        .page-header p {
            margin: 5px 0 0 0;
            color: #666;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card h2 {
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

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
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
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
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

        .request-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .request-table th,
        .request-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .request-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .request-table tr:hover {
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

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            color: #333;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .total-request {
            color: #8A0054
        }

        .pending {
            color: #FFC107;
        }

        .approved {
            color: #28A745;
        }

        .rejected {
            color: #DC3545;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .reason-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .form-row {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .request-table {
                font-size: 12px;
            }

            .request-table th,
            .request-table td {
                padding: 8px;
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
            <h1 class="page-title">Request Permission</h1>
            <p>Submit absence requests and track their status</p>
        </div>
        <!-- Statistics -->
        <div class="grid-container">
            <div class="stats-grid">
                <div class="stat-card total-request">
                    <div class="stat-number">
                        <?php echo isset($stats['total']) ? $stats['total'] : 0; ?>
                    </div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo isset($stats['pending']) ? $stats['pending'] : 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card approved">
                    <div class="stat-number"><?php echo isset($stats['approved']) ? $stats['approved'] : 0; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card rejected">
                    <div class="stat-number"><?php echo isset($stats['rejected']) ? $stats['rejected'] : 0; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>

            <?php echo isset($message) ? $message : ''; ?>

            <!-- Submit New Request -->
            <div class="card">
                <h2><i class="fa-solid fa-plus"></i> Submit New Request</h2>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">
                                <i class="fa-solid fa-calendar-days"></i> Start Date
                            </label>
                            <input type="date" id="startDate" name="startDate" min="<?php echo date('Y-m-d'); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="endDate">
                                <i class="fa-solid fa-calendar-days"></i> End Date
                            </label>
                            <input type="date" id="endDate" name="endDate" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason">
                            <i class="fa-solid fa-message"></i> Reason for Absence
                        </label>
                        <textarea id="reason" name="reason"
                            placeholder="Please provide a detailed reason for your absence request. Include any relevant information such as medical appointments, family emergencies, etc."
                            required></textarea>
                    </div>

                    <button type="submit" name="submit_request" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Submit Request
                    </button>
                </form>
            </div>

            <!-- My Requests -->
            <div class="card">
                <h2><i class="fa-solid fa-list"></i> My Requests</h2>
                <?php
                if (!empty($studentId)) {
                    $query = "SELECT * FROM tblabsentrequest WHERE studentId = ? ORDER BY createdAt DESC";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("s", $studentId);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result && $result->num_rows > 0) {
                            echo '<div style="overflow-x: auto;">
                                    <table class="request-table">
                                        <thead>
                                            <tr>
                                                <th>Request Date</th>
                                                <th>Absence Period</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Admin Response</th>
                                                <th>Days</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                            while ($row = $result->fetch_assoc()) {
                                $startDate = new DateTime($row['startDate']);
                                $endDate = new DateTime($row['endDate']);
                                $days = $startDate->diff($endDate)->days + 1;

                                $period = $startDate->format('M d, Y');
                                if ($row['startDate'] != $row['endDate']) {
                                    $period .= ' - ' . $endDate->format('M d, Y');
                                }

                                echo '<tr>
                                        <td>' . date('M d, Y', strtotime($row['createdAt'])) . '</td>
                                        <td>' . $period . '</td>
                                        <td>
                                            <div class="reason-preview" title="' . htmlspecialchars($row['reason']) . '">
                                                ' . htmlspecialchars(substr($row['reason'], 0, 50)) . '...
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-' . $row['status'] . '">
                                                ' . ucfirst($row['status']) . '
                                            </span>
                                        </td>
                                        <td>' . (isset($row['adminResponse']) && $row['adminResponse'] ? htmlspecialchars($row['adminResponse']) : '<em>No response yet</em>') . '</td>
                                        <td>' . $days . ' day' . ($days > 1 ? 's' : '') . '</td>
                                      </tr>';
                            }
                            echo '</tbody></table></div>';
                        } else {
                            echo '<div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <h3>No Requests Yet</h3>
                                    <p>You haven\'t submitted any absence requests yet. Use the form above to submit your first request.</p>
                                  </div>';
                        }
                        $stmt->close();
                    } else {
                        echo '<div class="empty-state">
                                <i class="fa-solid fa-exclamation-triangle"></i>
                                <h3>Database Error</h3>
                                <p>Unable to load your requests. Please try again later.</p>
                              </div>';
                    }
                } else {
                    echo '<div class="empty-state">
                            <i class="fa-solid fa-user-circle"></i>
                            <h3>Please Login</h3>
                            <p>Please login to view your absence requests.</p>
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-set end date when start date is selected
        document.getElementById('startDate').addEventListener('change', function () {
            const startDate = this.value;
            const endDateInput = document.getElementById('endDate');

            if (startDate && !endDateInput.value) {
                endDateInput.value = startDate;
            }
            endDateInput.min = startDate;
        });

        // Character counter for reason textarea
        const reasonTextarea = document.getElementById('reason');
        reasonTextarea.addEventListener('input', function () {
            const charCount = this.value.length;
            const minChars = 10;

            if (charCount < minChars) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#28a745';
            }
        });
    </script>
</body>

</html>