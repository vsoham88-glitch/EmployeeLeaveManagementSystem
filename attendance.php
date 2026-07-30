<?php

session_start();

/*
|--------------------------------------------------------------------------
| Protect Admin Page
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database.php';

/*
|--------------------------------------------------------------------------
| Create Attendance Table If Needed
|--------------------------------------------------------------------------
*/

$createTable = "
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $createTable)) {
    die(
        "Attendance table error: "
        . mysqli_error($conn)
    );
}

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalAttendance = 0;
$todayPresent = 0;
$todayAbsent = 0;
$todayLate = 0;

/*
|--------------------------------------------------------------------------
| Total Attendance Records
|--------------------------------------------------------------------------
*/

$countQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM attendance"
);

if ($countQuery) {

    $countData =
        mysqli_fetch_assoc($countQuery);

    $totalAttendance =
        (int)($countData['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Present Today
|--------------------------------------------------------------------------
*/

$presentQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM attendance
     WHERE attendance_date = CURDATE()
     AND status = 'Present'"
);

if ($presentQuery) {

    $presentData =
        mysqli_fetch_assoc($presentQuery);

    $todayPresent =
        (int)($presentData['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Absent Today
|--------------------------------------------------------------------------
*/

$absentQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM attendance
     WHERE attendance_date = CURDATE()
     AND status = 'Absent'"
);

if ($absentQuery) {

    $absentData =
        mysqli_fetch_assoc($absentQuery);

    $todayAbsent =
        (int)($absentData['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Late Today
|--------------------------------------------------------------------------
*/

$lateQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM attendance
     WHERE attendance_date = CURDATE()
     AND status = 'Late'"
);

if ($lateQuery) {

    $lateData =
        mysqli_fetch_assoc($lateQuery);

    $todayLate =
        (int)($lateData['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Fetch Attendance Records
|--------------------------------------------------------------------------
*/

$query = "
SELECT
    attendance.*,
    employees.employee_id AS employee_code,
    employees.name AS employee_name

FROM attendance

LEFT JOIN employees
    ON attendance.employee_id = employees.id

ORDER BY
    attendance.attendance_date DESC,
    attendance.id DESC
";

$result = mysqli_query(
    $conn,
    $query
);

if (!$result) {

    die(
        "Could not load attendance records: "
        . mysqli_error($conn)
    );
}

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
    Attendance Management - ELMS Corporate
</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}

body {
    background: #f4f6f9;
    color: #17233c;
}


/* -----------------------------------------------------------
   SIDEBAR
----------------------------------------------------------- */

.sidebar {

    width: 240px;
    height: 100vh;

    background: #1e293b;

    position: fixed;

    left: 0;
    top: 0;

    padding-top: 20px;

    overflow-y: auto;
}

.sidebar h2 {

    color: white;

    text-align: center;

    margin-bottom: 30px;
}

.sidebar a {

    display: block;

    padding: 14px 25px;

    color: #cbd5e1;

    text-decoration: none;

    transition: 0.2s;
}

.sidebar a:hover {

    background: #334155;

    color: white;
}

.sidebar a.active {

    background: #dc2626;

    color: white;
}


/* -----------------------------------------------------------
   MAIN CONTENT
----------------------------------------------------------- */

.main {

    margin-left: 240px;

    padding: 30px;
}

.header {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 30px;
}

.header h1 {

    color: #1e293b;

    margin-bottom: 6px;
}

.header p {

    color: #64748b;
}


/* -----------------------------------------------------------
   BUTTONS
----------------------------------------------------------- */

.btn {

    padding: 11px 20px;

    border: none;

    border-radius: 7px;

    cursor: pointer;

    text-decoration: none;

    display: inline-block;

    font-weight: bold;
}

.btn-primary {

    background: #dc2626;

    color: white;
}

.btn-primary:hover {

    background: #b91c1c;
}


/* -----------------------------------------------------------
   SUCCESS MESSAGE
----------------------------------------------------------- */

.success-message {

    background: #dcfce7;

    color: #166534;

    border: 1px solid #bbf7d0;

    padding: 14px 18px;

    border-radius: 8px;

    margin-bottom: 25px;
}


/* -----------------------------------------------------------
   STATISTIC CARDS
----------------------------------------------------------- */

.cards {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 30px;
}

.card {

    background: white;

    padding: 22px;

    border-radius: 10px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.08);
}

.card h3 {

    color: #64748b;

    font-size: 14px;

    margin-bottom: 10px;
}

.card h2 {

    color: #1e293b;

    font-size: 30px;
}


/* -----------------------------------------------------------
   TABLE
----------------------------------------------------------- */

.table-container {

    background: white;

    padding: 20px;

    border-radius: 10px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.08);

    overflow-x: auto;
}

.table-header {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 20px;
}

table {

    width: 100%;

    border-collapse: collapse;
}

th {

    background: #f1f5f9;

    text-align: left;

    padding: 13px;

    color: #475569;

    white-space: nowrap;
}

td {

    padding: 13px;

    border-bottom:
        1px solid #e2e8f0;

    vertical-align: middle;
}

tbody tr:hover {

    background: #f8fafc;
}

.employee-name {

    font-weight: bold;

    color: #1e293b;
}

.employee-code {

    display: block;

    margin-top: 4px;

    font-size: 12px;

    color: #64748b;
}


/* -----------------------------------------------------------
   STATUS BADGES
----------------------------------------------------------- */

.status {

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

    display: inline-block;

    white-space: nowrap;
}

.status-present {

    background: #dcfce7;

    color: #166534;
}

.status-absent {

    background: #fee2e2;

    color: #991b1b;
}

.status-late {

    background: #fef3c7;

    color: #92400e;
}

.status-half-day {

    background: #dbeafe;

    color: #1e40af;
}

.status-on-leave {

    background: #f3e8ff;

    color: #7e22ce;
}

.empty {

    text-align: center;

    padding: 40px;

    color: #64748b;
}


/* -----------------------------------------------------------
   RESPONSIVE
----------------------------------------------------------- */

@media (max-width: 1100px) {

    .cards {

        grid-template-columns:
            repeat(2, 1fr);
    }
}

@media (max-width: 750px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;

        padding: 20px;
    }

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 18px;
    }

    .cards {

        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <h2>
        ELMS Corporate
    </h2>

    <a href="index.php">
        Dashboard
    </a>

    <a href="employees.php">
        Employees
    </a>

    <a href="leave_requests.php">
        Leave Requests
    </a>

    <a
        href="attendance.php"
        class="active"
    >
        Attendance
    </a>

    <a href="analytics.php">
        Analytics
    </a>

    <a href="settings.php">
        Settings
    </a>

    <a href="logout.php">
        Logout
    </a>

</div>


<!-- MAIN -->

<div class="main">


<!-- HEADER -->

<div class="header">

    <div>

        <h1>
            Attendance Management
        </h1>

        <p>
            Manage employee attendance and working hours.
        </p>

    </div>

    <a
        href="mark_attendance.php"
        class="btn btn-primary"
    >
        + Mark Attendance
    </a>

</div>


<!-- SUCCESS -->

<?php if (isset($_GET['success'])): ?>

<div class="success-message">

    ✓ Attendance saved successfully.

</div>

<?php endif; ?>


<!-- STATISTICS -->

<div class="cards">


<div class="card">

    <h3>
        Total Attendance Records
    </h3>

    <h2>

        <?php
        echo $totalAttendance;
        ?>

    </h2>

</div>


<div class="card">

    <h3>
        Present Today
    </h3>

    <h2>

        <?php
        echo $todayPresent;
        ?>

    </h2>

</div>


<div class="card">

    <h3>
        Absent Today
    </h3>

    <h2>

        <?php
        echo $todayAbsent;
        ?>

    </h2>

</div>


<div class="card">

    <h3>
        Late Today
    </h3>

    <h2>

        <?php
        echo $todayLate;
        ?>

    </h2>

</div>


</div>


<!-- ATTENDANCE TABLE -->

<div class="table-container">


<div class="table-header">

    <h2>
        Attendance Records
    </h2>

</div>


<table>


<thead>

<tr>

    <th>
        ID
    </th>

    <th>
        Employee
    </th>

    <th>
        Date
    </th>

    <th>
        Check In
    </th>

    <th>
        Check Out
    </th>

    <th>
        Status
    </th>

</tr>

</thead>


<tbody>


<?php if (
    $result &&
    mysqli_num_rows($result) > 0
): ?>


<?php while (
    $row =
        mysqli_fetch_assoc($result)
): ?>


<?php

$status =
    $row['status']
    ?? 'Present';

switch ($status) {

    case 'Absent':

        $statusClass =
            'status-absent';

        break;


    case 'Late':

        $statusClass =
            'status-late';

        break;


    case 'Half Day':

        $statusClass =
            'status-half-day';

        break;


    case 'On Leave':

        $statusClass =
            'status-on-leave';

        break;


    default:

        $statusClass =
            'status-present';
}

?>


<tr>


<td>

<?php

echo (int)$row['id'];

?>

</td>


<td>

<span class="employee-name">

<?php

echo htmlspecialchars(
    $row['employee_name']
    ?? 'Unknown Employee'
);

?>

</span>


<span class="employee-code">

<?php

echo htmlspecialchars(
    $row['employee_code']
    ?? ''
);

?>

</span>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['attendance_date']
    ?? '-'
);

?>

</td>


<td>

<?php

if (
    $status === 'Absent'
    ||
    $status === 'On Leave'
) {

    echo '-';

} else {

    echo htmlspecialchars(
        $row['check_in']
        ?: '-'
    );
}

?>

</td>


<td>

<?php

if (
    $status === 'Absent'
    ||
    $status === 'On Leave'
) {

    echo '-';

} else {

    echo htmlspecialchars(
        $row['check_out']
        ?: '-'
    );
}

?>

</td>


<td>

<span
    class="status
    <?php
    echo $statusClass;
    ?>"
>

<?php

echo htmlspecialchars(
    $status
);

?>

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

    No attendance records found.

    <br><br>

    Click

    <strong>
        + Mark Attendance
    </strong>

    to add the first attendance record.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>


</body>

</html>