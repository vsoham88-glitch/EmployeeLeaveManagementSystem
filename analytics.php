<?php

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/database.php';

/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$totalEmployees = 0;

$approvedLeaves = 0;
$pendingLeaves = 0;
$rejectedLeaves = 0;

$totalAttendance = 0;
$presentToday = 0;
$absentToday = 0;
$lateToday = 0;

/*
|--------------------------------------------------------------------------
| Total Employees
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM employees"
);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $totalEmployees =
        (int)($data['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Approved Leaves
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM leave_requests
     WHERE status = 'Approved'"
);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $approvedLeaves =
        (int)($data['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Pending Leaves
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM leave_requests
     WHERE status = 'Pending'"
);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $pendingLeaves =
        (int)($data['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Rejected Leaves
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM leave_requests
     WHERE status = 'Rejected'"
);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $rejectedLeaves =
        (int)($data['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Attendance Table Check
|--------------------------------------------------------------------------
*/

$attendanceTableExists = false;

$tableCheck = mysqli_query(
    $conn,
    "SHOW TABLES LIKE 'attendance'"
);

if (
    $tableCheck &&
    mysqli_num_rows($tableCheck) > 0
) {

    $attendanceTableExists = true;
}

/*
|--------------------------------------------------------------------------
| Attendance Statistics
|--------------------------------------------------------------------------
*/

if ($attendanceTableExists) {

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM attendance"
    );

    if ($result) {

        $data =
            mysqli_fetch_assoc($result);

        $totalAttendance =
            (int)($data['total'] ?? 0);
    }


    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM attendance
         WHERE attendance_date = CURDATE()
         AND status = 'Present'"
    );

    if ($result) {

        $data =
            mysqli_fetch_assoc($result);

        $presentToday =
            (int)($data['total'] ?? 0);
    }


    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM attendance
         WHERE attendance_date = CURDATE()
         AND status = 'Absent'"
    );

    if ($result) {

        $data =
            mysqli_fetch_assoc($result);

        $absentToday =
            (int)($data['total'] ?? 0);
    }


    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM attendance
         WHERE attendance_date = CURDATE()
         AND status = 'Late'"
    );

    if ($result) {

        $data =
            mysqli_fetch_assoc($result);

        $lateToday =
            (int)($data['total'] ?? 0);
    }
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
    Analytics Reports - ELMS
</title>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f8fafc;

    color: #17233c;
}


/* SIDEBAR */

.sidebar {

    width: 240px;

    height: 100vh;

    position: fixed;

    left: 0;

    top: 0;

    background: #1e293b;

    padding-top: 20px;
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
}

.sidebar a:hover {

    background: #334155;

    color: white;
}

.sidebar a.active {

    background: #dc2626;

    color: white;
}


/* MAIN */

.main {

    margin-left: 240px;

    padding: 30px;
}

.header {

    margin-bottom: 30px;
}

.header h1 {

    margin-bottom: 7px;
}

.header p {

    color: #64748b;

    margin: 0;
}


/* CARDS */

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

    border-radius: 12px;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 2px 8px
        rgba(0,0,0,0.05);
}

.card p {

    color: #64748b;

    margin: 0;

    font-size: 14px;
}

.card h2 {

    font-size: 30px;

    margin: 10px 0 0;
}


/* CHARTS */

.chart-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 25px;
}

.chart-card {

    background: white;

    padding: 25px;

    border-radius: 12px;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 2px 8px
        rgba(0,0,0,0.05);
}

.chart-card h3 {

    margin-top: 0;

    margin-bottom: 20px;
}

.chart-wrapper {

    position: relative;

    height: 320px;
}


/* RESPONSIVE */

@media (
    max-width: 1100px
) {

    .cards {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .chart-grid {

        grid-template-columns:
            1fr;
    }
}

@media (
    max-width: 750px
) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;
    }

    .cards {

        grid-template-columns:
            1fr;
    }
}

</style>

</head>


<body>


<div class="sidebar">

    <h2>
        ELMS Corporate
    </h2>

    <a href="index.php">
        Dashboard
    </a>

    <a href="leave_requests.php">
        Leave Requests
    </a>

    <a href="attendance.php">
        Attendance
    </a>

    <a href="employees.php">
        Employees
    </a>

    <a
        href="analytics.php"
        class="active"
    >
        Analytics
    </a>

    <a href="settings.php">
        Settings
    </a>

    <a href="logout.php">
        Logout
    </a>

</div>


<div class="main">


<div class="header">

    <h1>
        Analytics Reports
    </h1>

    <p>
        Overview of employee,
        leave and attendance statistics.
    </p>

</div>


<div class="cards">


<div class="card">

    <p>
        Total Employees
    </p>

    <h2>
        <?php echo $totalEmployees; ?>
    </h2>

</div>


<div class="card">

    <p>
        Approved Leaves
    </p>

    <h2>
        <?php echo $approvedLeaves; ?>
    </h2>

</div>


<div class="card">

    <p>
        Pending Leaves
    </p>

    <h2>
        <?php echo $pendingLeaves; ?>
    </h2>

</div>


<div class="card">

    <p>
        Rejected Leaves
    </p>

    <h2>
        <?php echo $rejectedLeaves; ?>
    </h2>

</div>


<div class="card">

    <p>
        Total Attendance Records
    </p>

    <h2>
        <?php echo $totalAttendance; ?>
    </h2>

</div>


<div class="card">

    <p>
        Present Today
    </p>

    <h2>
        <?php echo $presentToday; ?>
    </h2>

</div>


<div class="card">

    <p>
        Absent Today
    </p>

    <h2>
        <?php echo $absentToday; ?>
    </h2>

</div>


<div class="card">

    <p>
        Late Today
    </p>

    <h2>
        <?php echo $lateToday; ?>
    </h2>

</div>


</div>


<div class="chart-grid">


<div class="chart-card">

    <h3>
        Leave Request Status
    </h3>

    <div class="chart-wrapper">

        <canvas
            id="leaveChart"
        ></canvas>

    </div>

</div>


<div class="chart-card">

    <h3>
        Today's Attendance
    </h3>

    <div class="chart-wrapper">

        <canvas
            id="attendanceChart"
        ></canvas>

    </div>

</div>


</div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| Leave Chart
|--------------------------------------------------------------------------
*/

const leaveCtx =
    document
    .getElementById(
        "leaveChart"
    );

new Chart(
    leaveCtx,
    {

        type: "doughnut",

        data: {

            labels: [
                "Approved",
                "Pending",
                "Rejected"
            ],

            datasets: [

                {

                    data: [

                        <?php
                        echo $approvedLeaves;
                        ?>,

                        <?php
                        echo $pendingLeaves;
                        ?>,

                        <?php
                        echo $rejectedLeaves;
                        ?>

                    ],

                    backgroundColor: [

                        "#16a34a",

                        "#eab308",

                        "#dc2626"

                    ]

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio:
                false

        }

    }
);


/*
|--------------------------------------------------------------------------
| Attendance Chart
|--------------------------------------------------------------------------
*/

const attendanceCtx =
    document
    .getElementById(
        "attendanceChart"
    );

new Chart(
    attendanceCtx,
    {

        type: "bar",

        data: {

            labels: [

                "Present",

                "Absent",

                "Late"

            ],

            datasets: [

                {

                    label:
                        "Employees",

                    data: [

                        <?php
                        echo $presentToday;
                        ?>,

                        <?php
                        echo $absentToday;
                        ?>,

                        <?php
                        echo $lateToday;
                        ?>

                    ],

                    backgroundColor: [

                        "#16a34a",

                        "#dc2626",

                        "#eab308"

                    ]

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio:
                false,

            plugins: {

                legend: {

                    display:
                        false

                }

            },

            scales: {

                y: {

                    beginAtZero:
                        true,

                    ticks: {

                        precision:
                            0

                    }

                }

            }

        }

    }
);

</script>


</body>

</html>