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

require_once __DIR__ . '/config/database.php';

$employeeId = (int) $_SESSION["employee_id"];

/* Get employee leave history */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        leave_type,
        start_date,
        end_date,
        total_days,
        reason,
        status
     FROM leave_requests
     WHERE employee_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $employeeId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    My Leave History - ELMS
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        sans-serif;

    background:
        #f8fafc;

    color:
        #17233c;
}

.navbar {

    background:
        white;

    padding:
        18px 35px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    border-bottom:
        1px solid #e2e8f0;
}

.logo {

    color:
        #eb2535;

    font-size:
        23px;

    font-weight:
        bold;
}

.navbar a {

    text-decoration:
        none;

    color:
        #475569;

    margin-left:
        20px;
}

.container {

    width:
        92%;

    max-width:
        1200px;

    margin:
        35px auto;
}

.header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        25px;
}

.btn {

    background:
        #eb2535;

    color:
        white;

    padding:
        11px 17px;

    border-radius:
        8px;

    text-decoration:
        none;

    font-weight:
        bold;
}

.card {

    background:
        white;

    border:
        1px solid #e2e8f0;

    border-radius:
        12px;

    padding:
        24px;

    overflow-x:
        auto;
}

table {

    width:
        100%;

    border-collapse:
        collapse;
}

th {

    background:
        #f8fafc;

    color:
        #64748b;

    text-align:
        left;

    padding:
        13px;
}

td {

    padding:
        14px 13px;

    border-bottom:
        1px solid #e2e8f0;
}

.badge {

    display:
        inline-block;

    padding:
        6px 12px;

    border-radius:
        30px;

    font-size:
        12px;

    font-weight:
        bold;
}

.pending {

    background:
        #fef3c7;

    color:
        #92400e;
}

.approved {

    background:
        #dcfce7;

    color:
        #166534;
}

.rejected {

    background:
        #fee2e2;

    color:
        #b91c1c;
}

.empty {

    text-align:
        center;

    color:
        #64748b;

    padding:
        30px;
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

        <a href="employee_leave_history.php">
            Leave History
        </a>

        <a href="employee_logout.php">
            Logout
        </a>

    </div>

</div>

<div class="container">

    <div class="header">

        <div>

            <h1>
                My Leave History
            </h1>

            <p>
                Employee:
                <strong>
                    <?= htmlspecialchars(
                        $_SESSION["employee_name"] ??
                        "Employee"
                    ) ?>
                </strong>
            </p>

        </div>

        <a
            href="employee_submit_leave.php"
            class="btn"
        >
            + Request New Leave
        </a>

    </div>

    <div class="card">

        <table>

            <thead>

                <tr>

                    <th>
                        Leave Type
                    </th>

                    <th>
                        Start Date
                    </th>

                    <th>
                        End Date
                    </th>

                    <th>
                        Days
                    </th>

                    <th>
                        Reason
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php
                while (
                    $leave =
                    mysqli_fetch_assoc($result)
                ):
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

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $leave["end_date"]
                            ) ?>

                        </td>

                        <td>

                            <?= (int)
                                $leave["total_days"]
                            ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $leave["reason"] ?? ""
                            ) ?>

                        </td>

                        <td>

                            <span
                                class="badge <?= strtolower(
                                    $leave["status"]
                                ) ?>"
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
                        colspan="6"
                        class="empty"
                    >

                        No leave history found.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>