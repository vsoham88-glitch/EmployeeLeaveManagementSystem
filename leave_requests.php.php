<?php

session_start();

// Only logged-in admin can access this page
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once __DIR__ . '/config/database.php';

// Get all leave requests with employee names
$sql = "
    SELECT
        lr.id,
        lr.employee_id,
        lr.leave_type,
        lr.start_date,
        lr.end_date,
        lr.total_days,
        lr.reason,
        lr.status,
        lr.applied_at,
        e.name AS employee_name,
        e.employee_id AS employee_code
    FROM leave_requests lr
    LEFT JOIN employees e
        ON lr.employee_id = e.id
    ORDER BY lr.id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Leave Requests - ELMS Corporate</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #17233c;
}

.navbar {
    background: white;
    padding: 18px 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
}

.logo {
    color: #ed1c2e;
    font-size: 24px;
    font-weight: bold;
}

.navbar a {
    text-decoration: none;
    color: #44546a;
    margin-left: 25px;
}

.container {
    width: 94%;
    max-width: 1400px;
    margin: 35px auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-header h1 {
    margin-bottom: 8px;
}

.page-header p {
    color: #64748b;
    margin: 0;
}

.btn-new {
    background: #ed1c2e;
    color: white;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 7px;
    font-weight: bold;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f4f6fa;
    color: #53627a;
    padding: 14px;
    text-align: left;
    font-size: 14px;
}

td {
    padding: 15px 14px;
    border-bottom: 1px solid #e8ecf2;
    vertical-align: middle;
}

.employee-name {
    font-weight: bold;
}

.employee-code {
    color: #64748b;
    font-size: 12px;
    margin-top: 4px;
}

.reason {
    max-width: 220px;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: bold;
}

.pending {
    background: #fff6cc;
    color: #a06d00;
}

.approved {
    background: #dcfce7;
    color: #15803d;
}

.rejected {
    background: #fee2e2;
    color: #dc2626;
}

.actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 15px;
}

.approve-btn {
    background: #dcfce7;
    color: #15803d;
}

.reject-btn {
    background: #fee2e2;
    color: #dc2626;
}

.processed {
    color: #64748b;
    font-size: 13px;
}

.empty {
    padding: 40px;
    text-align: center;
    color: #64748b;
}

</style>

</head>

<body>

<div class="navbar">

    <div class="logo">
        <i class="fa-solid fa-layer-group"></i>
        ELMS Corporate
    </div>

    <div>

        <a href="index.php">
            Dashboard
        </a>

        <a href="employees.php">
            Employees
        </a>

        <a href="submit_leave.php">
            Request Time Off
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</div>

<div class="container">

    <div class="page-header">

        <div>

            <h1>Leave Requests</h1>

            <p>
                Review and manage employee leave applications.
            </p>

        </div>

        <a href="submit_leave.php"
           class="btn-new">

            <i class="fa-solid fa-plus"></i>
            New Leave Request

        </a>

    </div>

    <div class="card">

        <table>

            <thead>

            <tr>

                <th>Employee</th>

                <th>Leave Type</th>

                <th>Start Date</th>

                <th>End Date</th>

                <th>Days</th>

                <th>Reason</th>

                <th>Status</th>

                <th>Actions</th>

            </tr>

            </thead>

            <tbody>

            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>

                    <tr id="request-<?php echo $row['id']; ?>">

                        <td>

                            <div class="employee-name">

                                <?php
                                echo htmlspecialchars(
                                    $row['employee_name']
                                    ?? 'Unknown Employee'
                                );
                                ?>

                            </div>

                            <div class="employee-code">

                                <?php
                                echo htmlspecialchars(
                                    $row['employee_code']
                                    ?? 'ID: ' . $row['employee_id']
                                );
                                ?>

                            </div>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['leave_type']
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['start_date']
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['end_date']
                            );
                            ?>

                        </td>

                        <td>

                            <strong>

                                <?php
                                echo (int)$row['total_days'];
                                ?>

                            </strong>

                        </td>

                        <td class="reason">

                            <?php
                            echo htmlspecialchars(
                                $row['reason'] ?? ''
                            );
                            ?>

                        </td>

                        <td>

                            <span
                                id="status-<?php echo $row['id']; ?>"
                                class="badge <?php
                                    echo strtolower(
                                        $row['status']
                                    );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $row['status']
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <div
                                class="actions"
                                id="actions-<?php echo $row['id']; ?>"
                            >

                                <?php if ($row['status'] === 'Pending'): ?>

                                    <button
                                        class="action-btn approve-btn"
                                        title="Approve"
                                        onclick="updateLeaveStatus(
                                            <?php echo $row['id']; ?>,
                                            'Approved'
                                        )"
                                    >

                                        <i class="fa-solid fa-check"></i>

                                    </button>

                                    <button
                                        class="action-btn reject-btn"
                                        title="Reject"
                                        onclick="updateLeaveStatus(
                                            <?php echo $row['id']; ?>,
                                            'Rejected'
                                        )"
                                    >

                                        <i class="fa-solid fa-xmark"></i>

                                    </button>

                                <?php else: ?>

                                    <span class="processed">
                                        Processed
                                    </span>

                                <?php endif; ?>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="8"
                        class="empty">

                        No leave requests found.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<script>

function updateLeaveStatus(id, status) {

    const formData = new FormData();

    formData.append("id", id);
    formData.append("status", status);

    fetch("update_leave_status.php", {

        method: "POST",
        body: formData

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            const statusBadge =
                document.getElementById(
                    "status-" + id
                );

            const actions =
                document.getElementById(
                    "actions-" + id
                );

            statusBadge.textContent = status;

            statusBadge.className =
                "badge " + status.toLowerCase();

            actions.innerHTML =
                '<span class="processed">' +
                'Processed' +
                '</span>';

            alert(
                "Leave request " +
                status.toLowerCase() +
                " successfully."
            );

        } else {

            alert(
                data.message ||
                "Unable to update leave request."
            );

        }

    })

    .catch(error => {

        console.error(error);

        alert(
            "Something went wrong while updating the request."
        );

    });

}

</script>

</body>

</html>