<?php

session_start();

/* Check employee login */
if (!isset($_SESSION["employee_id"])) {
    header("Location: employee_login.php");
    exit();
}

/* Check camera verification */
if (
    !isset($_SESSION["camera_verified"]) ||
    $_SESSION["camera_verified"] !== true
) {
    header("Location: employee_camera_verify.php");
    exit();
}

/* Database connection */
require_once __DIR__ . "/database.php";

$employeeId = (int) $_SESSION["employee_id"];

/* Get employee information */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        employee_id,
        name,
        email,
        leave_balance,
        status
     FROM employees
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to prepare employee query.");
}

mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$employee) {
    session_destroy();
    die("Employee account not found.");
}

/* Get employee leave requests */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        leave_type,
        start_date,
        end_date,
        total_days,
        reason,
        status,
        applied_at
     FROM leave_requests
     WHERE employee_id = ?
     ORDER BY id DESC"
);

if (!$stmt) {
    die("Unable to prepare leave request query.");
}

mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);

$leaveResult = mysqli_stmt_get_result($stmt);

/* Get employee notification count */
$stmtNotification = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS notification_count
     FROM leave_requests
     WHERE employee_id = ?"
);

if (!$stmtNotification) {
    die("Unable to prepare notification query.");
}

mysqli_stmt_bind_param(
    $stmtNotification,
    "i",
    $employeeId
);

mysqli_stmt_execute($stmtNotification);

$notificationResult = mysqli_stmt_get_result(
    $stmtNotification
);

$notificationData = mysqli_fetch_assoc(
    $notificationResult
);

$notificationCount = (int) (
    $notificationData["notification_count"] ?? 0
);

mysqli_stmt_close($stmtNotification);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Employee Dashboard - ELMS</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f8fafc;
    color: #17233c;
}

.navbar {
    background: white;
    padding: 18px 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    color: #eb2535;
    font-size: 23px;
    font-weight: bold;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
}

.navbar a {
    text-decoration: none;
    color: #475569;
    font-weight: 600;
}

.navbar a:hover {
    color: #eb2535;
}

.logout-link {
    color: #eb2535 !important;
}

.container {
    width: 92%;
    max-width: 1200px;
    margin: 35px auto;
}

.welcome {
    margin-bottom: 30px;
}

.welcome h1 {
    margin-bottom: 8px;
}

.welcome p {
    color: #64748b;
}

.cards {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
}

.card p {
    color: #64748b;
    margin-top: 0;
}

.card h2 {
    font-size: 30px;
    margin-bottom: 0;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
}

.btn {
    display: inline-block;
    background: #eb2535;
    color: white;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.2s;
}

.btn:hover {
    background: #c81e2c;
    transform: translateY(-1px);
}

.notification-btn {
    position: relative;
    background: #17233c;
}

.notification-btn:hover {
    background: #263552;
}

.notification-count {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    margin-left: 6px;
    background: white;
    color: #eb2535;
    border-radius: 20px;
    font-size: 12px;
}

.table-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 24px;
    overflow-x: auto;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
}

.table-card h2 {
    margin-top: 0;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f8fafc;
    text-align: left;
    padding: 13px;
    color: #64748b;
    white-space: nowrap;
}

td {
    padding: 14px 13px;
    border-bottom: 1px solid #e2e8f0;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: bold;
    text-transform: capitalize;
}

.pending {
    background: #fef3c7;
    color: #92400e;
}

.approved {
    background: #dcfce7;
    color: #166534;
}

.rejected {
    background: #fee2e2;
    color: #b91c1c;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #64748b;
}

@media (max-width: 800px) {

    .navbar {
        padding: 15px 20px;
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .nav-links {
        width: 100%;
        flex-wrap: wrap;
        gap: 12px;
    }

    .container {
        width: 94%;
        margin-top: 25px;
    }

}

</style>

</head>

<body>

<div class="navbar">

    <div class="logo">
        ELMS Employee Portal
    </div>

    <div class="nav-links">

        <a href="employee_dashboard.php">
            Dashboard
        </a>

        <a href="employee_submit_leave.php">
            Request Leave
        </a>

        <a href="attendance.php">
            Attendance
        </a>

        <a href="employee_notifications.php">
            Notifications
        </a>

        <a
            href="employee_logout.php"
            class="logout-link"
        >
            Logout
        </a>

    </div>

</div>

<div class="container">

    <div class="welcome">

        <h1>
            Welcome,
            <?= htmlspecialchars($employee["name"]) ?>
        </h1>

        <p>
            Employee ID:
            <?= htmlspecialchars($employee["employee_id"]) ?>
        </p>

    </div>

    <div class="cards">

        <div class="card">

            <p>Leave Balance</p>

            <h2>
                <?= (int) $employee["leave_balance"] ?>
            </h2>

        </div>

        <div class="card">

            <p>Account Status</p>

            <h2>
                <?= htmlspecialchars($employee["status"]) ?>
            </h2>

        </div>

        <div class="card">

            <p>Camera Verification</p>

            <h2>Verified</h2>

        </div>

        <div class="card">

            <p>Notifications</p>

            <h2>
                <?= $notificationCount ?>
            </h2>

        </div>

    </div>

    <div class="actions">

        <a
            href="employee_submit_leave.php"
            class="btn"
        >
            + Request New Leave
        </a>

        <a
            href="employee_leave_history.php"
            class="btn"
        >
            Leave History
        </a>

        <a
            href="attendance.php"
            class="btn"
        >
            My Attendance
        </a>

        <a
            href="employee_notifications.php"
            class="btn notification-btn"
        >
            🔔 Notifications

            <span class="notification-count">
                <?= $notificationCount ?>
            </span>
        </a>

    </div>

    <div class="table-card">

        <h2>My Leave Requests</h2>

        <table>

            <thead>

                <tr>
                    <th>Leave Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>

            </thead>

            <tbody>

            <?php if (
                $leaveResult &&
                mysqli_num_rows($leaveResult) > 0
            ): ?>

                <?php while (
                    $leave = mysqli_fetch_assoc($leaveResult)
                ): ?>

                    <?php
                    $leaveStatus = strtolower(
                        $leave["status"] ?? "pending"
                    );

                    $allowedStatuses = [
                        "pending",
                        "approved",
                        "rejected"
                    ];

                    if (!in_array(
                        $leaveStatus,
                        $allowedStatuses,
                        true
                    )) {
                        $leaveStatus = "pending";
                    }
                    ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars(
                                $leave["leave_type"]
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $leave["start_date"]
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $leave["end_date"]
                            ) ?>
                        </td>

                        <td>
                            <?= (int) $leave["total_days"] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $leave["reason"] ?? ""
                            ) ?>
                        </td>

                        <td>

                            <span
                                class="badge <?= $leaveStatus ?>"
                            >
                                <?= htmlspecialchars(
                                    $leave["status"]
                                ) ?>
                            </span>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="5"
                        class="empty"
                    >
                        No leave requests yet.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php mysqli_stmt_close($stmt); ?>
*
</body>

</html>