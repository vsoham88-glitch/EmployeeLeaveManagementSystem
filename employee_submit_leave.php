<?php

session_start();

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

if (file_exists(__DIR__ . '/config/database.php')) {

    require_once __DIR__ . '/config/database.php';

} elseif (file_exists(__DIR__ . '/database.php')) {

    require_once __DIR__ . '/database.php';

} else {

    die("Error: Could not locate database.php.");
}


/*
|--------------------------------------------------------------------------
| Employee Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['employee_id'])) {

    header("Location: employee_login.php");
    exit();
}

$employee_id = (int) $_SESSION['employee_id'];


/*
|--------------------------------------------------------------------------
| Handle Leave Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $leave_type =
        trim($_POST['leave_type'] ?? '');

    $start_date =
        trim($_POST['start_date'] ?? '');

    $end_date =
        trim($_POST['end_date'] ?? '');

    $reason =
        trim($_POST['reason'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $leave_type === '' ||
        $start_date === '' ||
        $end_date === '' ||
        $reason === ''
    ) {

        $_SESSION['error_msg'] =
            "Please fill in all required fields.";

        header(
            "Location: employee_submit_leave.php"
        );

        exit();
    }


    if ($end_date < $start_date) {

        $_SESSION['error_msg'] =
            "End date cannot be earlier than start date.";

        header(
            "Location: employee_submit_leave.php"
        );

        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Total Days
    |--------------------------------------------------------------------------
    */

    $start =
        new DateTime($start_date);

    $end =
        new DateTime($end_date);

    $difference =
        $start->diff($end);

    $total_days =
        $difference->days + 1;


    /*
    |--------------------------------------------------------------------------
    | Save Leave Request
    |--------------------------------------------------------------------------
    */

    $status = "Pending";

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO leave_requests
        (
            employee_id,
            leave_type,
            start_date,
            end_date,
            total_days,
            reason,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {

        die(
            "Failed to prepare query: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "isssiss",
        $employee_id,
        $leave_type,
        $start_date,
        $end_date,
        $total_days,
        $reason,
        $status
    );


    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success_msg'] =
            "Leave request submitted successfully.";

        mysqli_stmt_close($stmt);

        header(
            "Location: employee_dashboard.php"
        );

        exit();

    } else {

        $_SESSION['error_msg'] =
            "Could not submit leave request: "
            . mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        header(
            "Location: employee_submit_leave.php"
        );

        exit();
    }
}


/*
|--------------------------------------------------------------------------
| Load Leave Types
|--------------------------------------------------------------------------
*/

$leaveTypes =
    mysqli_query(
        $conn,
        "SELECT leave_name
         FROM leave_types
         ORDER BY leave_name ASC"
    );

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
    Request Leave - ELMS Employee Portal
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

    background: #f8fafc;

    color: #17233c;
}

.navbar {

    background: white;

    padding: 18px 35px;

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    border-bottom:
        1px solid #e2e8f0;
}

.logo {

    color: #eb2535;

    font-size: 22px;

    font-weight: bold;
}

.navbar a {

    text-decoration: none;

    color: #475569;

    margin-left: 20px;
}

.container {

    width: 92%;

    max-width: 800px;

    margin: 40px auto;
}

.card {

    background: white;

    padding: 30px;

    border-radius: 12px;

    border:
        1px solid #e2e8f0;
}

.form-group {

    margin-bottom: 20px;
}

label {

    display: block;

    margin-bottom: 8px;

    font-weight: bold;
}

input,
select,
textarea {

    width: 100%;

    padding: 12px;

    border:
        1px solid #cbd5e1;

    border-radius: 8px;

    font-size: 15px;
}

textarea {

    min-height: 120px;

    resize: vertical;
}

.form-row {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;
}

.btn {

    display: inline-block;

    border: none;

    padding: 12px 20px;

    border-radius: 8px;

    cursor: pointer;

    text-decoration: none;

    font-weight: bold;
}

.btn-primary {

    background: #eb2535;

    color: white;
}

.btn-secondary {

    background: #e2e8f0;

    color: #334155;

    margin-left: 8px;
}

.message {

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;
}

.error {

    background: #fee2e2;

    color: #991b1b;
}

@media (
    max-width: 650px
) {

    .form-row {

        grid-template-columns:
            1fr;
    }
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

        <a href="employee_logout.php">

            Logout

        </a>

    </div>

</div>


<div class="container">

<h1>
    Request Leave
</h1>

<p>
    Submit your leave request for admin approval.
</p>


<div class="card">


<?php

if (
    isset(
        $_SESSION['error_msg']
    )
):

?>

<div class="message error">

<?php

echo htmlspecialchars(
    $_SESSION['error_msg']
);

unset(
    $_SESSION['error_msg']
);

?>

</div>

<?php endif; ?>


<form method="POST">


<div class="form-group">

<label>
    Leave Type
</label>

<select
    name="leave_type"
    required
>

<option value="">

    Select Leave Type

</option>

<?php

if ($leaveTypes):

while (
    $type =
    mysqli_fetch_assoc(
        $leaveTypes
    )
):

?>

<option
    value="<?=
    htmlspecialchars(
        $type['leave_name']
    )
    ?>"
>

<?=
htmlspecialchars(
    $type['leave_name']
)
?>

</option>

<?php

endwhile;

endif;

?>

</select>

</div>


<div class="form-row">


<div class="form-group">

<label>
    Start Date
</label>

<input
    type="date"
    name="start_date"
    required
>

</div>


<div class="form-group">

<label>
    End Date
</label>

<input
    type="date"
    name="end_date"
    required
>

</div>


</div>


<div class="form-group">

<label>
    Reason
</label>

<textarea
    name="reason"
    placeholder="Enter reason for leave..."
    required
></textarea>

</div>


<button
    type="submit"
    class="btn btn-primary"
>

    Submit Leave Request

</button>


<a
    href="employee_dashboard.php"
    class="btn btn-secondary"
>

    Cancel

</a>


</form>


</div>

</div>


</body>

</html>