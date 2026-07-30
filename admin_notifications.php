<?php

session_start();

require_once __DIR__ . "/database.php";

/* Pending leave requests */
$pendingResult = mysqli_query(
    $conn,
    "SELECT
        lr.id,
        lr.leave_type,
        lr.start_date,
        lr.end_date,
        lr.total_days,
        lr.status,
        lr.applied_at,
        e.name,
        e.employee_id
     FROM leave_requests lr
     INNER JOIN employees e
        ON e.id = lr.employee_id
     WHERE LOWER(lr.status) = 'pending'
     ORDER BY lr.id DESC"
);

/* Recent leave activity */
$recentLeaveResult = mysqli_query(
    $conn,
    "SELECT
        lr.id,
        lr.leave_type,
        lr.start_date,
        lr.end_date,
        lr.status,
        lr.applied_at,
        e.name,
        e.employee_id
     FROM leave_requests lr
     INNER JOIN employees e
        ON e.id = lr.employee_id
     ORDER BY lr.id DESC
     LIMIT 20"
);

/* Today's attendance */
$todayAttendanceResult = mysqli_query(
    $conn,
    "SELECT
        a.attendance_date,
        a.check_in,
        a.check_out,
        a.status,
        e.name,
        e.employee_id
     FROM attendance a
     INNER JOIN employees e
        ON e.id = a.employee_id
     WHERE a.attendance_date = CURDATE()
     ORDER BY a.id DESC"
);

$pendingCount = $pendingResult
    ? mysqli_num_rows($pendingResult)
    : 0;

$attendanceCount = $todayAttendanceResult
    ? mysqli_num_rows($todayAttendanceResult)
    : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Admin Notifications - ELMS</title>

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
    max-width: 1100px;
    margin: 35px auto;
}

.summary {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.card p {
    margin-top: 0;
    color: #64748b;
}

.card h2 {
    margin-bottom: 0;
    font-size: 32px;
}

.section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 25px;
}

.notification {
    display: flex;
    gap: 15px;
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
    background: #fef3c7;
    color: #92400e;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-weight: bold;
}

.icon-attendance {
    background: #dbeafe;
    color: #1d4ed8;
}

.icon-approved {
    background: #dcfce7;
    color: #166534;
}

.icon-rejected {
    background: #fee2e2;
    color: #b91c1c;
}

.content h3 {
    margin: 0 0 7px;
    font-size: 16px;
}

.content p {
    margin: 0 0 7px;
    color: #64748b;
    line-height: 1.5;
}

.date {
    color: #94a3b8;
    font-size: 13px;
}

.empty {
    text-align: center;
    padding: 25px;
    color: #64748b;
}

.btn {
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
        ELMS Corporate
    </div>

    <div>

        <a href="index.php">
            Dashboard
        </a>

        <a href="leave_requests.php">
            Leave Requests
        </a>

        <a href="admin_notifications.php">
            Notifications
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</div>

<div class="container">

    <h1>🔔 Admin Notifications</h1>

    <div class="summary">

        <div class="card">

            <p>Pending Leave Requests</p>

            <h2><?= $pendingCount ?></h2>

        </div>

        <div class="card">

            <p>Today's Attendance Records</p>

            <h2><?= $attendanceCount ?></h2>

        </div>

    </div>

    <div class="section">

        <h2>Pending Leave Alerts</h2>

        <?php if ($pendingCount > 0): ?>

            <?php while (
                $leave = mysqli_fetch_assoc($pendingResult)
            ): ?>

                <div class="notification">

                    <div class="icon">
                        !
                    </div>

                    <div class="content">

                        <h3>
                            <?= htmlspecialchars(
                                $leave["name"]
                            ) ?>

                            submitted a leave request
                        </h3>

                        <p>
                            Employee ID:

                            <?= htmlspecialchars(
                                $leave["employee_id"]
                            ) ?>

                            ·

                            <?= htmlspecialchars(
                                $leave["leave_type"]
                            ) ?>

                            ·

                            <?= htmlspecialchars(
                                $leave["start_date"]
                            ) ?>

                            to

                            <?= htmlspecialchars(
                                $leave["end_date"]
                            ) ?>

                            ·

                            <?= (int) $leave["total_days"] ?>

                            day(s)
                        </p>

                        <span class="date">
                            <?= htmlspecialchars(
                                $leave["applied_at"]
                            ) ?>
                        </span>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                No pending leave requests.
            </div>

        <?php endif; ?>

    </div>

    <div class="section">

        <h2>Recent Leave Activity</h2>

        <?php if (
            $recentLeaveResult &&
            mysqli_num_rows($recentLeaveResult) > 0
        ): ?>

            <?php while (
                $leave =
                mysqli_fetch_assoc($recentLeaveResult)
            ): ?>

                <?php

                $status =
                    strtolower($leave["status"]);

                if ($status === "approved") {

                    $iconClass = "icon-approved";
                    $iconText = "✓";

                } elseif ($status === "rejected") {

                    $iconClass = "icon-rejected";
                    $iconText = "×";

                } else {

                    $iconClass = "";
                    $iconText = "!";

                }

                ?>

                <div class="notification">

                    <div class="icon <?= $iconClass ?>">
                        <?= $iconText ?>
                    </div>

                    <div class="content">

                        <h3>
                            <?= htmlspecialchars(
                                $leave["name"]
                            ) ?>

                            —

                            <?= htmlspecialchars(
                                $leave["status"]
                            ) ?>
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
                        </p>

                        <span class="date">
                            <?= htmlspecialchars(
                                $leave["applied_at"]
                            ) ?>
                        </span>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                No recent leave activity.
            </div>

        <?php endif; ?>

    </div>

    <div class="section">

        <h2>Today's Attendance</h2>

        <?php

        if ($todayAttendanceResult) {
            mysqli_data_seek($todayAttendanceResult, 0);
        }

        ?>

        <?php if (
            $todayAttendanceResult &&
            mysqli_num_rows($todayAttendanceResult) > 0
        ): ?>

            <?php while (
                $attendance =
                mysqli_fetch_assoc($todayAttendanceResult)
            ): ?>

                <div class="notification">

                    <div class="icon icon-attendance">
                        A
                    </div>

                    <div class="content">

                        <h3>
                            <?= htmlspecialchars(
                                $attendance["name"]
                            ) ?>

                            —

                            <?= htmlspecialchars(
                                $attendance["status"]
                            ) ?>
                        </h3>

                        <p>
                            Employee ID:

                            <?= htmlspecialchars(
                                $attendance["employee_id"]
                            ) ?>

                            · Check-in:

                            <?= htmlspecialchars(
                                $attendance["check_in"] ?? "-"
                            ) ?>

                            · Check-out:

                            <?= htmlspecialchars(
                                $attendance["check_out"] ?? "-"
                            ) ?>
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
                No attendance marked today.
            </div>

        <?php endif; ?>

    </div>

    <a href="index.php" class="btn">
        ← Back to Dashboard
    </a>

</div>

</body>

</html>