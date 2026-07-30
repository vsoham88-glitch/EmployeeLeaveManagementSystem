<?php

session_start();

if (!isset($_SESSION["employee_id"])) {
    header("Location: employee_login.php");
    exit();
}

if (
    !isset($_SESSION["camera_verified"]) ||
    $_SESSION["camera_verified"] !== true
) {
    header("Location: employee_camera_auth.php");
    exit();
}

require_once __DIR__ . "/database.php";

$employeeId = (int) $_SESSION["employee_id"];

/* Employee information */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        employee_id,
        name
     FROM employees
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);

$employeeResult = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($employeeResult);

mysqli_stmt_close($stmt);

if (!$employee) {
    die("Employee account not found.");
}

/* Leave notifications */
$stmtLeave = mysqli_prepare(
    $conn,
    "SELECT
        id,
        leave_type,
        start_date,
        end_date,
        total_days,
        status,
        applied_at
     FROM leave_requests
     WHERE employee_id = ?
     ORDER BY id DESC
     LIMIT 20"
);

mysqli_stmt_bind_param(
    $stmtLeave,
    "i",
    $employeeId
);

mysqli_stmt_execute($stmtLeave);

$leaveResult = mysqli_stmt_get_result($stmtLeave);

/* Attendance notifications */
$stmtAttendance = mysqli_prepare(
    $conn,
    "SELECT
        attendance_date,
        check_in,
        check_out,
        status
     FROM attendance
     WHERE employee_id = ?
     ORDER BY attendance_date DESC, id DESC
     LIMIT 20"
);

mysqli_stmt_bind_param(
    $stmtAttendance,
    "i",
    $employeeId
);

mysqli_stmt_execute($stmtAttendance);

$attendanceResult =
    mysqli_stmt_get_result($stmtAttendance);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Employee Notifications - ELMS</title>

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
}

.logo {
    color: #eb2535;
    font-size: 23px;
    font-weight: bold;
}

.navbar a {
    text-decoration: none;
    color: #475569;
    margin-left: 20px;
}

.container {
    width: 92%;
    max-width: 1050px;
    margin: 35px auto;
}

.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    margin-bottom: 8px;
}

.page-header p {
    color: #64748b;
}

.section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 25px;
}

.section h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.notification {
    display: flex;
    gap: 15px;
    align-items: flex-start;
    padding: 17px 0;
    border-bottom: 1px solid #e2e8f0;
}

.notification:last-child {
    border-bottom: none;
}

.icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
    font-weight: bold;
}

.icon-approved {
    background: #dcfce7;
    color: #166534;
}

.icon-rejected {
    background: #fee2e2;
    color: #b91c1c;
}

.icon-pending {
    background: #fef3c7;
    color: #92400e;
}

.icon-attendance {
    background: #dbeafe;
    color: #1d4ed8;
}

.notification-content h3 {
    margin: 0 0 7px;
    font-size: 16px;
}

.notification-content p {
    margin: 0 0 7px;
    color: #64748b;
    line-height: 1.5;
}

.date {
    color: #94a3b8;
    font-size: 13px;
}

.empty {
    padding: 25px;
    text-align: center;
    color: #64748b;
}

.back-btn {
    display: inline-block;
    background: #eb2535;
    color: white;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}

</style>

</head>

<body>

<div class="navbar">

    <div class="logo">
        ELMS Employee Portal
    </div>

    <div>

        <a href="employee_dashboard.php">
            Dashboard
        </a>

        <a href="employee_submit_leave.php">
            Request Leave
        </a>

        <a href="employee_attendance.php">
            Attendance
        </a>

        <a href="employee_logout.php">
            Logout
        </a>

    </div>

</div>

<div class="container">

    <div class="page-header">

        <h1>🔔 My Notifications</h1>

        <p>
            <?= htmlspecialchars($employee["name"]) ?>
            —
            <?= htmlspecialchars($employee["employee_id"]) ?>
        </p>

    </div>

    <div class="section">

        <h2>Leave Notifications</h2>

        <?php if (
            $leaveResult &&
            mysqli_num_rows($leaveResult) > 0
        ): ?>

            <?php while (
                $leave = mysqli_fetch_assoc($leaveResult)
            ): ?>

                <?php

                $status =
                    strtolower($leave["status"]);

                if ($status === "approved") {

                    $iconClass = "icon-approved";
                    $iconText = "✓";
                    $title =
                        "Your leave request was approved";

                } elseif ($status === "rejected") {

                    $iconClass = "icon-rejected";
                    $iconText = "×";
                    $title =
                        "Your leave request was rejected";

                } else {

                    $iconClass = "icon-pending";
                    $iconText = "!";
                    $title =
                        "Your leave request is pending";

                }

                ?>

                <div class="notification">

                    <div class="icon <?= $iconClass ?>">
                        <?= $iconText ?>
                    </div>

                    <div class="notification-content">

                        <h3>
                            <?= htmlspecialchars($title) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars(
                                $leave["leave_type"]
                            ) ?>

                            leave from

                            <?= htmlspecialchars(
                                $leave["start_date"]
                            ) ?>

                            to

                            <?= htmlspecialchars(
                                $leave["end_date"]
                            ) ?>.

                            Total:

                            <?= (int) $leave["total_days"] ?>

                            day(s).
                        </p>

                        <span class="date">
                            Applied:
                            <?= htmlspecialchars(
                                $leave["applied_at"]
                            ) ?>
                        </span>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                No leave notifications found.
            </div>

        <?php endif; ?>

    </div>

    <div class="section">

        <h2>Attendance Notifications</h2>

        <?php if (
            $attendanceResult &&
            mysqli_num_rows($attendanceResult) > 0
        ): ?>

            <?php while (
                $attendance =
                mysqli_fetch_assoc($attendanceResult)
            ): ?>

                <div class="notification">

                    <div class="icon icon-attendance">
                        A
                    </div>

                    <div class="notification-content">

                        <h3>
                            Attendance marked
                            <?= htmlspecialchars(
                                $attendance["status"]
                            ) ?>
                        </h3>

                        <p>
                            Your attendance for

                            <?= htmlspecialchars(
                                $attendance["attendance_date"]
                            ) ?>

                            was marked as

                            <strong>
                                <?= htmlspecialchars(
                                    $attendance["status"]
                                ) ?>
                            </strong>.

                            Check-in:

                            <?= htmlspecialchars(
                                $attendance["check_in"] ?? "-"
                            ) ?>,

                            Check-out:

                            <?= htmlspecialchars(
                                $attendance["check_out"] ?? "-"
                            ) ?>.
                        </p>

                        <span class="date">
                            <?= htmlspecialchars(
                                $attendance["attendance_date"]
                            ) ?>
                        </span>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                No attendance notifications found.
            </div>

        <?php endif; ?>

    </div>

    <a
        href="employee_dashboard.php"
        class="back-btn"
    >
        ← Back to Dashboard
    </a>

</div>

</body>

</html>