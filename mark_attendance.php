<?php

session_start();

require_once __DIR__ . '/database.php';

/*
|--------------------------------------------------------------------------
| Create Attendance Table If It Does Not Exist
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
    die("Could not create attendance table: " . mysqli_error($conn));
}

/*
|--------------------------------------------------------------------------
| Save Attendance
|--------------------------------------------------------------------------
*/

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $employee_id = (int)($_POST['employee_id'] ?? 0);
    $attendance_date = $_POST['attendance_date'] ?? '';
    $status = $_POST['status'] ?? 'Present';

    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Hide/Clear Time For Absent And On Leave
    |--------------------------------------------------------------------------
    */

    if ($status === 'Absent' || $status === 'On Leave') {
        $check_in = null;
        $check_out = null;
    } else {
        $check_in = $check_in !== '' ? $check_in : null;
        $check_out = $check_out !== '' ? $check_out : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($employee_id <= 0 || $attendance_date === '') {

        $message = "Please select an employee and attendance date.";
        $messageType = "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Attendance
        |--------------------------------------------------------------------------
        */

        $checkDuplicate = mysqli_prepare(
            $conn,
            "SELECT id
             FROM attendance
             WHERE employee_id = ?
             AND attendance_date = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $checkDuplicate,
            "is",
            $employee_id,
            $attendance_date
        );

        mysqli_stmt_execute($checkDuplicate);

        $duplicateResult =
            mysqli_stmt_get_result($checkDuplicate);

        if (
            $duplicateResult &&
            mysqli_num_rows($duplicateResult) > 0
        ) {

            $message =
                "Attendance for this employee is already marked for this date.";

            $messageType = "error";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Insert Attendance
            |--------------------------------------------------------------------------
            */

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO attendance
                (
                    employee_id,
                    attendance_date,
                    check_in,
                    check_out,
                    status
                )
                VALUES (?, ?, ?, ?, ?)"
            );

            if (!$insert) {
                die(
                    "Insert preparation failed: "
                    . mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $insert,
                "issss",
                $employee_id,
                $attendance_date,
                $check_in,
                $check_out,
                $status
            );

            if (mysqli_stmt_execute($insert)) {

                header(
                    "Location: attendance.php?success=1"
                );

                exit();

            } else {

                $message =
                    "Could not save attendance: "
                    . mysqli_stmt_error($insert);

                $messageType = "error";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fetch Employees
|--------------------------------------------------------------------------
*/

$employeesQuery = "
SELECT id, employee_id, name
FROM employees
ORDER BY name ASC
";

$employeesResult =
    mysqli_query(
        $conn,
        $employeesQuery
    );

if (!$employeesResult) {
    die(
        "Could not load employees: "
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

<title>Mark Attendance - ELMS Corporate</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #0f172a;
    color: #ffffff;
}

.header {
    background: #1e293b;
    border-bottom: 1px solid #334155;
    padding: 18px 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    color: #ff3b3b;
    font-size: 20px;
    font-weight: bold;
}

.header a {
    color: #cbd5e1;
    text-decoration: none;
    margin-left: 25px;
}

.header a:hover {
    color: #ffffff;
}

.container {
    width: 90%;
    max-width: 850px;
    margin: 45px auto;
}

.page-title {
    margin-bottom: 25px;
}

.page-title h1 {
    margin-bottom: 8px;
}

.page-title p {
    color: #94a3b8;
    margin: 0;
}

.card {
    background: #1e293b;
    padding: 30px;
    border-radius: 12px;
    border: 1px solid #334155;
    box-shadow: 0 10px 30px rgba(0,0,0,.20);
}

.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #e2e8f0;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 13px;
    border-radius: 7px;
    border: 1px solid #475569;
    background: #0f172a;
    color: #ffffff;
    font-size: 15px;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #ff3b3b;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
}

.save-btn {
    border: none;
    background: #ef2929;
    color: white;
    padding: 13px 24px;
    border-radius: 7px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

.save-btn:hover {
    background: #d91f1f;
}

.cancel-btn {
    background: #334155;
    color: white;
    text-decoration: none;
    padding: 13px 24px;
    border-radius: 7px;
    font-size: 15px;
}

.cancel-btn:hover {
    background: #475569;
}

.message {
    padding: 14px;
    border-radius: 7px;
    margin-bottom: 22px;
}

.message.error {
    background: rgba(239,68,68,.15);
    border: 1px solid #ef4444;
    color: #fca5a5;
}

.hidden {
    display: none;
}

@media (max-width: 700px) {

    .form-row {
        grid-template-columns: 1fr;
    }

    .header {
        padding: 15px 20px;
    }

    .container {
        width: 94%;
    }
}

</style>

</head>

<body>

<div class="header">

    <div class="logo">
        ELMS Corporate
    </div>

    <div>

        <a href="index.php">
            Dashboard
        </a>

        <a href="attendance.php">
            Attendance
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</div>

<div class="container">

    <div class="page-title">

        <h1>
            Mark Attendance
        </h1>

        <p>
            Record employee attendance,
            check-in and check-out information.
        </p>

    </div>

    <div class="card">

        <?php if ($message !== ""): ?>

            <div
                class="message
                <?php echo $messageType; ?>"
            >

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            action=""
        >

            <div class="form-group">

                <label>
                    Employee
                </label>

                <select
                    name="employee_id"
                    required
                >

                    <option value="">
                        Select Employee
                    </option>

                    <?php
                    while (
                        $employee =
                        mysqli_fetch_assoc(
                            $employeesResult
                        )
                    ):
                    ?>

                        <option
                            value="<?php
                            echo (int)$employee['id'];
                            ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                (
                                    $employee['employee_id']
                                    ?? ''
                                )
                                .
                                ' - '
                                .
                                (
                                    $employee['name']
                                    ?? ''
                                )
                            );

                            ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="form-group">

                <label>
                    Attendance Date
                </label>

                <input
                    type="date"
                    name="attendance_date"
                    value="<?php echo date('Y-m-d'); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Attendance Status
                </label>

                <select
                    name="status"
                    id="attendance_status"
                    required
                >

                    <option value="Present">
                        Present
                    </option>

                    <option value="Absent">
                        Absent
                    </option>

                    <option value="Late">
                        Late
                    </option>

                    <option value="Half Day">
                        Half Day
                    </option>

                    <option value="On Leave">
                        On Leave
                    </option>

                </select>

            </div>

            <div
                class="form-row"
                id="time_fields"
            >

                <div class="form-group">

                    <label>
                        Check In
                    </label>

                    <input
                        type="time"
                        name="check_in"
                        id="check_in"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Check Out
                    </label>

                    <input
                        type="time"
                        name="check_out"
                        id="check_out"
                    >

                </div>

            </div>

            <div class="actions">

                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Attendance
                </button>

                <a
                    href="attendance.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

<script>

function updateAttendanceFields() {

    const status =
        document.getElementById(
            "attendance_status"
        ).value;

    const timeFields =
        document.getElementById(
            "time_fields"
        );

    const checkIn =
        document.getElementById(
            "check_in"
        );

    const checkOut =
        document.getElementById(
            "check_out"
        );

    if (
        status === "Absent" ||
        status === "On Leave"
    ) {

        timeFields.classList.add(
            "hidden"
        );

        checkIn.value = "";
        checkOut.value = "";

    } else {

        timeFields.classList.remove(
            "hidden"
        );
    }
}

document
    .getElementById(
        "attendance_status"
    )
    .addEventListener(
        "change",
        updateAttendanceFields
    );

updateAttendanceFields();

</script>

</body>

</html>