<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/db.php';
include '../Includes/session.php';

// Function to check if today is weekend
function isWeekend()
{
    $dayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)
    return ($dayOfWeek >= 6); // 6=Saturday, 7=Sunday
}

date_default_timezone_set('Asia/Phnom_Penh');
$showAttendance = !isWeekend();

$rs = null;

if ($showAttendance) {
    // Check if attendance records exist for today
    $checkQuery = "SELECT COUNT(*) as count FROM tblAttendance WHERE DATE(markedAt) = CURDATE()";
    $checkResult = $conn->query($checkQuery);

    if (!$checkResult) {
        // Handle error silently or log it
    } else {
        $row = $checkResult->fetch_assoc();

        if ($row['count'] == 0) {
            // Fixed: Use NOW() instead of CURDATE() for timestamp
            $insertQuery = "INSERT INTO tblAttendance (studentId, sessionId, attendanceStatus, markedAt)
                SELECT s.userId, sessions.sessionId, 'present', NOW()
                FROM tblstudent s
                CROSS JOIN (SELECT 1 AS sessionId UNION ALL SELECT 2 UNION ALL SELECT 3) sessions
                WHERE s.isActive = 1";

            $conn->query($insertQuery);
        }
    }

    // Select attendance data for display, including academic year
    $query = "SELECT 
        s.userId AS studentId, 
        s.firstName, 
        s.lastName,
        s.academicYear,
        a.sessionId, 
        a.attendanceStatus, 
        a.markedAt
        FROM tblstudent s
        LEFT JOIN tblAttendance a ON s.userId = a.studentId AND DATE(a.markedAt) = CURDATE()
        WHERE s.isActive = 1
        ORDER BY s.firstName, s.userId, a.sessionId";

    $rs = $conn->query($query);
    $allRows = [];

    if ($rs && $rs->num_rows > 0) {
        // Convert mysqli_result to array
        while ($row = $rs->fetch_assoc()) {
            $allRows[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Attendance</title>
    <link rel="stylesheet" href="../style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* .search-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-input {
            padding: 4px 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 250px;
            transition: border-color 0.3s ease;
        }

        input[type="text"] {
            font-size: 11px;
        }

        .search-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .search-icon {
            color: #666;
            font-size: 16px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .clear-search {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0;
            margin-left: 5px;
            font-size: 14px;
        }

        .clear-search:hover {
            color: #333;
        }

        .no-results {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        } */
    </style>
</head>

<body>
    <?php include "Includes/topbar.php"; ?>
    <?php include "Includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="heading-content">
            <h1 class="page-title">Attendance</h1>
            <div class="breadcrumb"><a href="./">Home /</a> Attendance</div>
        </div>

        <div class="list-container">
            <?php if (!$showAttendance): ?>
                <p>Attendance cannot be marked on weekends.</p>
            <?php else: ?>
                <div class="searchbar">
                    <div class="header-row">
                        <h2 class="list-title">Daily Attendance</h2>
                        <div class="search-container">
                            <input type="text" id="studentSearch" class="search-input"
                                placeholder="Search by student name or ID...">
                            <button type="button" id="clearSearch" class="clear-search" title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="student-table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Academic Year</th>
                                    <th colspan="3" style="text-align:center;">Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($allRows)) {
                                    $studentSessions = [];

                                    // Process all rows
                                    foreach ($allRows as $row) {
                                        if (!$row || !isset($row['studentId']) || empty($row['studentId'])) {
                                            continue;
                                        }

                                        $studentId = $row['studentId'];
                                        $firstName = isset($row['firstName']) ? $row['firstName'] : 'Unknown';
                                        $lastName = isset($row['lastName']) ? $row['lastName'] : '';
                                        $academicYear = isset($row['academicYear']) ? $row['academicYear'] : 'N/A';

                                        $studentSessions[$studentId]['name'] = trim($firstName . ' ' . $lastName);
                                        $studentSessions[$studentId]['academicYear'] = $academicYear;

                                        // Initialize all sessions as present if not set
                                        if (!isset($studentSessions[$studentId]['sessions'])) {
                                            $studentSessions[$studentId]['sessions'] = [
                                                1 => 'present',
                                                2 => 'present',
                                                3 => 'present'
                                            ];
                                        }

                                        // Update with actual attendance if exists
                                        $sessionId = isset($row['sessionId']) ? $row['sessionId'] : null;
                                        $attendanceStatus = isset($row['attendanceStatus']) ? $row['attendanceStatus'] : null;

                                        if ($sessionId && $attendanceStatus) {
                                            $studentSessions[$studentId]['sessions'][$sessionId] = $attendanceStatus;
                                        }
                                    }

                                    // Display the data
                                    if (empty($studentSessions)) {
                                        echo "<tr class='no-data'><td colspan='6' style='text-align:center; padding:20px; color:#666;'>
                                            No student data available.
                                        </td></tr>";
                                    } else {
                                        foreach ($studentSessions as $studentId => $data) {
                                            echo "<tr class='student-row' data-student-id='" . htmlspecialchars($studentId) . "' data-student-name='" . htmlspecialchars(strtolower($data['name'])) . "'>
                                                <td>" . htmlspecialchars($studentId) . "</td>
                                                <td>" . htmlspecialchars($data['name']) . "</td>
                                                <td>" . htmlspecialchars($data['academicYear']) . "</td>";

                                            for ($i = 1; $i <= 3; $i++) {
                                                $status = isset($data['sessions'][$i]) ? $data['sessions'][$i] : 'present';
                                                $isAbsent = ($status === 'absent') ? 'checked' : '';
                                                echo "<td style='text-align:center;'>
                                                    <input type='checkbox' 
                                                           class='absence-checkbox' 
                                                           data-student='" . htmlspecialchars($studentId) . "' 
                                                           data-session='{$i}' 
                                                           {$isAbsent}>
                                                </td>";
                                            }
                                            echo "</tr>";
                                        }
                                    }
                                } else {
                                    echo "<tr class='no-data'><td colspan='6' style='text-align:center; padding:20px; color:#666;'>
                                        No data available.
                                    </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <div id="noResults" class="no-results" style="display: none;">
                            <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>No students found matching your search.</p>
                        </div>

                        <!-- Pagination moved to bottom -->
                        <div class="table-pagination">
                            <div class="pagination-info">
                                <div class="show-entries">
                                    <label>Show
                                        <select id="entriesPerPage" class="entries-select">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        entries
                                    </label>
                                </div>
                                <div class="results-count">
                                    <span id="resultsCount">Showing 0 results</span>
                                </div>
                            </div>
                            <div class="pagination-controls">
                                <button class="pagination-btn" id="prevBtn" disabled>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <span class="page-info" id="pageInfo">Page 1 of 1</span>
                                <button class="pagination-btn" id="nextBtn" disabled>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-overlay" id="logoutModal" style="display:none;">
        <div class="modal-box">
            <h2 style="margin-bottom: 12px">Confirm</h2>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button class="modal-btn yes" id="yesBtn">Yes</button>
                <button class="modal-btn cancel" id="cancelBtn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Logout modal handlers
        document.querySelectorAll('.logout-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('logoutModal').style.display = 'flex';
            });
        });

        document.getElementById('yesBtn').onclick = function () {
            window.location.href = "../login.php";
        };
        document.getElementById('cancelBtn').onclick = function () {
            document.getElementById('logoutModal').style.display = 'none';
        };

        $(document).ready(function () {
            // Pagination variables
            let currentPage = 1;
            let rowsPerPage = 10;
            let totalRows = 0;
            let filteredRows = [];

            // Function to show styled message box
            function showMessage(message, type = 'success', duration = 3000) {
                $('.attendance-message').remove();

                const messageBox = $(`
                    <div class="attendance-message ${type}" style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 15px 20px;
                        border-radius: 8px;
                        color: white;
                        font-weight: 500;
                        z-index: 10000;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        max-width: 400px;
                        animation: slideInRight 0.3s ease;
                        ${type === 'success' ? 'background: linear-gradient(135deg, #4CAF50, #45a049);' : ''}
                        ${type === 'error' ? 'background: linear-gradient(135deg, #f44336, #d32f2f);' : ''}
                        ${type === 'info' ? 'background: linear-gradient(135deg, #2196F3, #1976D2);' : ''}
                    ">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="message-text" style="flex: 1; margin-right: 10px;">${message}</span>
                            <button class="message-close" style="
                                background: none;
                                border: none;
                                color: white;
                                font-size: 18px;
                                cursor: pointer;
                                padding: 0;
                                line-height: 1;
                            ">&times;</button>
                        </div>
                    </div>
                `);

                if (!$('#messageAnimations').length) {
                    $('head').append(`
                        <style id="messageAnimations">
                            @keyframes slideInRight {
                                from { transform: translateX(100%); opacity: 0; }
                                to { transform: translateX(0); opacity: 1; }
                            }
                            @keyframes slideOutRight {
                                from { transform: translateX(0); opacity: 1; }
                                to { transform: translateX(100%); opacity: 0; }
                            }
                        </style>
                    `);
                }

                $('body').append(messageBox);

                if (duration > 0) {
                    setTimeout(() => {
                        messageBox.css('animation', 'slideOutRight 0.3s ease');
                        setTimeout(() => messageBox.remove(), 300);
                    }, duration);
                }

                messageBox.find('.message-close').click(function () {
                    messageBox.css('animation', 'slideOutRight 0.3s ease');
                    setTimeout(() => messageBox.remove(), 300);
                });

                return messageBox;
            }

            // Search functionality
            function filterTable() {
                const searchTerm = $('#studentSearch').val().toLowerCase().trim();
                const allRows = $('.student-row');

                if (searchTerm === '') {
                    filteredRows = allRows;
                    $('#noResults').hide();
                } else {
                    filteredRows = allRows.filter(function () {
                        const studentId = $(this).data('student-id').toString().toLowerCase();
                        const studentName = $(this).data('student-name');
                        return studentId.includes(searchTerm) || studentName.includes(searchTerm);
                    });

                    if (filteredRows.length === 0) {
                        $('#noResults').show();
                    } else {
                        $('#noResults').hide();
                    }
                }

                currentPage = 1;
                updatePagination();
            }

            // Search input handler
            $('#studentSearch').on('input', function () {
                filterTable();
            });

            // Clear search button
            $('#clearSearch').click(function () {
                $('#studentSearch').val('');
                filterTable();
            });

            // Function to retry failed requests
            function retryAttendanceUpdate(checkbox, studentId, sessionId, status, studentName, retryCount = 0) {
                const maxRetries = 2;

                if (retryCount > 0) {
                    showMessage(`Retrying... Attempt ${retryCount + 1}/${maxRetries + 1}`, 'info', 2000);
                }

                $.ajax({
                    url: 'saveAttendance.php',
                    method: 'POST',
                    timeout: 10000,
                    data: {
                        studentId: studentId,
                        sessionId: sessionId,
                        status: status,
                        date: new Date().toISOString().slice(0, 10)
                    },
                    success: function (response) {
                        checkbox.prop('disabled', false);

                        let parsedResponse;
                        try {
                            parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error('Invalid JSON response:', response);
                            parsedResponse = { success: false, message: 'Invalid server response' };
                        }

                        if (parsedResponse.success) {
                            const statusText = status === 'absent' ? 'ABSENT' : 'PRESENT';
                            showMessage(`${studentName} marked as ${statusText} for Session ${sessionId}`, 'success');
                            console.log(`Database updated: ${studentName} (${studentId}), Session ${sessionId}: ${status}`);
                        } else {
                            checkbox.prop('checked', !checkbox.is(':checked'));
                            showMessage(`Error: ${parsedResponse.message}`, 'error');
                            console.error('Server error:', parsedResponse.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error Details:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            readyState: xhr.readyState,
                            statusCode: xhr.status
                        });

                        if (retryCount < maxRetries && (
                            status === 'timeout' ||
                            xhr.status === 0 ||
                            xhr.status >= 500
                        )) {
                            setTimeout(() => {
                                retryAttendanceUpdate(checkbox, studentId, sessionId, status, studentName, retryCount + 1);
                            }, 1000 * (retryCount + 1));
                        } else {
                            checkbox.prop('disabled', false);
                            checkbox.prop('checked', !checkbox.is(':checked'));

                            let errorMessage = `Failed to update attendance for ${studentName}.`;

                            if (status === 'timeout') {
                                errorMessage += ' Request timed out. Check your connection.';
                            } else if (xhr.status === 0) {
                                errorMessage += ' Network error. Check your internet connection.';
                            } else if (xhr.status === 404) {
                                errorMessage += ' Save file not found. Contact administrator.';
                            } else if (xhr.status >= 500) {
                                errorMessage += ' Server error. Try again later.';
                            } else if (xhr.responseText) {
                                try {
                                    const errorResponse = JSON.parse(xhr.responseText);
                                    if (errorResponse.message) {
                                        errorMessage += ' ' + errorResponse.message;
                                    }
                                } catch (e) {
                                    errorMessage += ' Please try again.';
                                }
                            }

                            showMessage(errorMessage, 'error', 5000);
                        }
                    }
                });
            }

            // Pagination functions
            function updatePagination() {
                const rows = filteredRows.length > 0 ? filteredRows : $('.student-row:not(.no-data)');
                totalRows = rows.length;
                const totalPages = Math.ceil(totalRows / rowsPerPage);

                // Update results count
                const startIndex = (currentPage - 1) * rowsPerPage + 1;
                const endIndex = Math.min(currentPage * rowsPerPage, totalRows);
                $('#resultsCount').text(`Showing ${totalRows > 0 ? startIndex + '-' + endIndex : '0'} of ${totalRows} results`);

                // Update page info
                $('#pageInfo').text(`Page ${currentPage} of ${Math.max(1, totalPages)}`);

                // Show/hide rows
                $('.student-row').hide();
                if (rows.length > 0) {
                    rows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage).show();
                }

                // Update pagination buttons
                $('#prevBtn').prop('disabled', currentPage <= 1);
                $('#nextBtn').prop('disabled', currentPage >= totalPages);
            }

            // Entries per page change handler
            $('#entriesPerPage').change(function () {
                rowsPerPage = parseInt($(this).val());
                currentPage = 1;
                updatePagination();
            });

            // Pagination button handlers
            $('#prevBtn').click(function () {
                if (currentPage > 1) {
                    currentPage--;
                    updatePagination();
                }
            });

            $('#nextBtn').click(function () {
                const totalPages = Math.ceil(totalRows / rowsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    updatePagination();
                }
            });

            // Initialize
            filteredRows = $('.student-row');
            setTimeout(updatePagination, 100);

            // Main checkbox handler
            $('.absence-checkbox').change(function () {
                var checkbox = $(this);
                var studentId = checkbox.data('student');
                var sessionId = checkbox.data('session');
                var isAbsent = checkbox.is(':checked');
                var status = isAbsent ? 'absent' : 'present';

                var studentName = checkbox.closest('tr').find('td:nth-child(2)').text().trim();

                if (!studentId || !sessionId || !studentName) {
                    showMessage('Invalid data. Please refresh the page and try again.', 'error');
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    return;
                }

                checkbox.prop('disabled', true);
                showMessage(`Updating attendance for ${studentName}...`, 'info', 0);
                retryAttendanceUpdate(checkbox, studentId, sessionId, status, studentName);
            });

            $(document).ajaxError(function (event, xhr, settings, thrownError) {
                if (settings.url && settings.url.includes('saveAttendance.php')) {
                    console.error('Global AJAX error handler caught:', {
                        url: settings.url,
                        status: xhr.status,
                        error: thrownError,
                        response: xhr.responseText
                    });
                }
            });
        });
    </script>
</body>

</html>