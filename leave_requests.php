<?php

session_start();

/*
|--------------------------------------------------------------------------
| Admin Protection
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
| AJAX: Approve / Reject Leave Request
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['ajax_action'])
    &&
    $_POST['ajax_action'] === 'update_status'
) {

    header('Content-Type: application/json');

    $requestId = (int)($_POST['id'] ?? 0);

    $newStatus = trim(
        $_POST['status'] ?? ''
    );

    if (
        $requestId <= 0
        ||
        !in_array(
            $newStatus,
            ['Approved', 'Rejected'],
            true
        )
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.'
        ]);

        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | Start Transaction
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction($conn);

    try {

        /*
        |--------------------------------------------------------------------------
        | Get Current Leave Request
        |--------------------------------------------------------------------------
        |
        | FOR UPDATE locks this row while processing.
        | This prevents accidental double approval/deduction.
        |
        */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                id,
                employee_id,
                total_days,
                status
             FROM leave_requests
             WHERE id = ?
             LIMIT 1
             FOR UPDATE"
        );

        if (!$stmt) {
            throw new Exception(
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $requestId
        );

        mysqli_stmt_execute($stmt);

        $requestResult =
            mysqli_stmt_get_result($stmt);

        $leave =
            mysqli_fetch_assoc(
                $requestResult
            );

        mysqli_stmt_close($stmt);


        /*
        |--------------------------------------------------------------------------
        | Validate Leave Request
        |--------------------------------------------------------------------------
        */

        if (!$leave) {

            throw new Exception(
                "Leave request not found."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Important: Process Pending Requests Only
        |--------------------------------------------------------------------------
        */

        if ($leave['status'] !== 'Pending') {

            throw new Exception(
                "This leave request has already been processed."
            );
        }


        $employeeId =
            (int)$leave['employee_id'];

        $totalDays =
            (int)$leave['total_days'];


        /*
        |--------------------------------------------------------------------------
        | Approve Leave
        |--------------------------------------------------------------------------
        */

        if ($newStatus === 'Approved') {

            /*
            |--------------------------------------------------------------------------
            | Check Employee
            |--------------------------------------------------------------------------
            */

            $employeeStmt =
                mysqli_prepare(
                    $conn,
                    "SELECT
                        id,
                        leave_balance
                     FROM employees
                     WHERE id = ?
                     LIMIT 1
                     FOR UPDATE"
                );

            if (!$employeeStmt) {

                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $employeeStmt,
                "i",
                $employeeId
            );

            mysqli_stmt_execute(
                $employeeStmt
            );

            $employeeResult =
                mysqli_stmt_get_result(
                    $employeeStmt
                );

            $employee =
                mysqli_fetch_assoc(
                    $employeeResult
                );

            mysqli_stmt_close(
                $employeeStmt
            );


            if (!$employee) {

                throw new Exception(
                    "Employee account not found."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate New Leave Balance
            |--------------------------------------------------------------------------
            */

            $currentBalance =
                (int)$employee[
                    'leave_balance'
                ];

            $newBalance =
                $currentBalance
                - $totalDays;

            /*
            | Do not allow negative balance.
            */

            if ($newBalance < 0) {

                throw new Exception(
                    "Employee does not have enough leave balance."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Deduct Leave Balance
            |--------------------------------------------------------------------------
            */

            $balanceStmt =
                mysqli_prepare(
                    $conn,
                    "UPDATE employees
                     SET leave_balance = ?
                     WHERE id = ?"
                );

            if (!$balanceStmt) {

                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $balanceStmt,
                "ii",
                $newBalance,
                $employeeId
            );

            if (
                !mysqli_stmt_execute(
                    $balanceStmt
                )
            ) {

                throw new Exception(
                    mysqli_stmt_error(
                        $balanceStmt
                    )
                );
            }

            mysqli_stmt_close(
                $balanceStmt
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Update Leave Request Status
        |--------------------------------------------------------------------------
        */

        $statusStmt =
            mysqli_prepare(
                $conn,
                "UPDATE leave_requests
                 SET status = ?
                 WHERE id = ?
                 AND status = 'Pending'"
            );

        if (!$statusStmt) {

            throw new Exception(
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $statusStmt,
            "si",
            $newStatus,
            $requestId
        );

        if (
            !mysqli_stmt_execute(
                $statusStmt
            )
        ) {

            throw new Exception(
                mysqli_stmt_error(
                    $statusStmt
                )
            );
        }

        mysqli_stmt_close(
            $statusStmt
        );


        /*
        |--------------------------------------------------------------------------
        | Commit Everything
        |--------------------------------------------------------------------------
        */

        mysqli_commit($conn);


        echo json_encode([

            'success' => true,

            'status' =>
                $newStatus,

            'message' =>
                $newStatus === 'Approved'
                ? "Leave approved and employee leave balance updated."
                : "Leave request rejected successfully."

        ]);

        exit();


    } catch (Throwable $e) {

        mysqli_rollback($conn);

        echo json_encode([

            'success' => false,

            'message' =>
                $e->getMessage()

        ]);

        exit();
    }
}


/*
|--------------------------------------------------------------------------
| Load Leave Requests
|--------------------------------------------------------------------------
*/

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

    e.name
        AS employee_name,

    e.employee_id
        AS employee_code,

    e.leave_balance
        AS employee_leave_balance

FROM leave_requests lr

LEFT JOIN employees e

    ON lr.employee_id = e.id

ORDER BY lr.id DESC
";


$result =
    mysqli_query(
        $conn,
        $sql
    );


if (!$result) {

    die(
        "Could not load leave requests: "
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
    content="width=device-width,
    initial-scale=1.0"
>

<title>
    Leave Requests - ELMS Corporate
</title>


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

    background: #f5f7fb;

    color: #17233c;
}


/* NAVBAR */

.navbar {

    min-height: 72px;

    background: #ffffff;

    border-bottom:
        1px solid #dfe5ee;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    padding:
        0 28px;
}

.brand {

    font-size: 20px;

    font-weight: 700;

    color: #ef233c;
}

.nav-links {

    display: flex;

    align-items: center;

    gap: 25px;
}

.nav-links a {

    text-decoration: none;

    color: #32435f;

    font-size: 14px;
}

.nav-links a:hover {

    color: #ef233c;
}


/* MAIN */

.container {

    max-width: 1250px;

    margin: 44px auto;

    padding:
        0 20px;
}

.page-header {

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    margin-bottom: 24px;
}

.page-header h1 {

    margin:
        0 0 8px;

    font-size: 28px;
}

.page-header p {

    margin: 0;

    color: #64748b;

    font-size: 14px;
}


/* BACK BUTTON */

.back-btn {

    background: #ef233c;

    color: #ffffff;

    text-decoration: none;

    padding:
        11px 17px;

    border-radius: 7px;

    font-size: 14px;

    font-weight: 600;
}


/* CARD */

.card {

    background: #ffffff;

    border:
        1px solid #e4e9f1;

    border-radius: 12px;

    padding: 20px;

    box-shadow:
        0 2px 8px
        rgba(0,0,0,.03);

    overflow-x: auto;
}


/* TABLE */

table {

    width: 100%;

    border-collapse:
        collapse;

    min-width: 1050px;
}

th {

    background: #f5f7fb;

    padding:
        14px 12px;

    text-align: left;

    color: #405170;

    font-size: 13px;

    font-weight: 600;
}

td {

    padding:
        14px 12px;

    border-bottom:
        1px solid #e5eaf1;

    font-size: 14px;

    vertical-align: middle;
}

tbody tr:hover {

    background: #fafbfc;
}


/* EMPLOYEE */

.employee-name {

    font-weight: 700;

    color: #17233c;
}

.employee-id {

    margin-top: 4px;

    font-size: 11px;

    color: #64748b;
}

.balance {

    margin-top: 5px;

    font-size: 11px;

    color: #475569;
}


/* STATUS */

.status {

    display: inline-block;

    padding:
        5px 11px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 600;
}

.status-approved {

    background: #dcfce7;

    color: #15803d;
}

.status-pending {

    background: #fef3c7;

    color: #b45309;
}

.status-rejected {

    background: #fee2e2;

    color: #dc2626;
}


/* ACTIONS */

.actions {

    display: flex;

    gap: 9px;

    align-items: center;
}

.action-btn {

    width: 36px;

    height: 36px;

    border: none;

    border-radius: 7px;

    cursor: pointer;

    font-size: 17px;

    display: inline-flex;

    align-items: center;

    justify-content: center;
}

.approve-btn {

    background: #dcfce7;

    color: #15803d;
}

.approve-btn:hover {

    background: #bbf7d0;
}

.reject-btn {

    background: #fee2e2;

    color: #dc2626;
}

.reject-btn:hover {

    background: #fecaca;
}

.processed {

    color: #64748b;

    font-size: 12px;
}


/* MESSAGE */

.message {

    position: fixed;

    top: 25px;

    right: 25px;

    max-width: 400px;

    padding:
        14px 18px;

    border-radius: 8px;

    color: white;

    display: none;

    z-index: 9999;

    box-shadow:
        0 10px 25px
        rgba(0,0,0,.15);
}

.message.success {

    background: #15803d;
}

.message.error {

    background: #dc2626;
}


/* EMPTY */

.empty {

    text-align: center;

    padding: 35px;

    color: #64748b;
}

</style>

</head>


<body>


<header class="navbar">

<div class="brand">

    ◆ ELMS Corporate

</div>


<nav class="nav-links">

<a href="index.php">

    Dashboard

</a>

<a href="employees.php">

    Employees

</a>

<a href="attendance.php">

    Attendance

</a>

<a href="analytics.php">

    Analytics

</a>

<a href="logout.php">

    Logout

</a>

</nav>

</header>


<main class="container">


<div class="page-header">


<div>

<h1>

    Leave Requests

</h1>

<p>

    Review and manage employee
    leave applications.

</p>

</div>


<a
    href="index.php"
    class="back-btn"
>

    ← Dashboard

</a>


</div>


<div class="card">


<table>


<thead>

<tr>

<th>
    Employee
</th>

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

<th>
    Actions
</th>

</tr>

</thead>


<tbody>


<?php

if (
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
):

?>


<?php

while (
    $request =
    mysqli_fetch_assoc(
        $result
    )
):


$status =
    $request['status']
    ?? 'Pending';


if ($status === 'Approved') {

    $statusClass =
        'status-approved';

}
elseif ($status === 'Rejected') {

    $statusClass =
        'status-rejected';

}
else {

    $statusClass =
        'status-pending';

}


$employeeName =
    $request[
        'employee_name'
    ]
    ?? 'Unknown Employee';


$employeeCode =
    $request[
        'employee_code'
    ]
    ?? '-';

?>


<tr
    id="row-<?=
    (int)$request['id']
    ?>"
>


<td>


<div class="employee-name">

<?=
htmlspecialchars(
    $employeeName
)
?>

</div>


<div class="employee-id">

<?=
htmlspecialchars(
    $employeeCode
)
?>

</div>


<div class="balance">

Balance:

<span
    id="balance-<?=
    (int)$request[
        'employee_id'
    ]
    ?>"
>

<?=
(int)(
    $request[
        'employee_leave_balance'
    ]
    ?? 0
)
?>

</span>

days

</div>


</td>


<td>

<?=
htmlspecialchars(
    $request[
        'leave_type'
    ]
    ?? '-'
)
?>

</td>


<td>

<?=
htmlspecialchars(
    $request[
        'start_date'
    ]
    ?? '-'
)
?>

</td>


<td>

<?=
htmlspecialchars(
    $request[
        'end_date'
    ]
    ?? '-'
)
?>

</td>


<td>

<strong>

<?=
(int)(
    $request[
        'total_days'
    ]
    ?? 0
)
?>

</strong>

</td>


<td>

<?=
htmlspecialchars(
    $request[
        'reason'
    ]
    ?? '-'
)
?>

</td>


<td>

<span
    id="status-<?=
    (int)$request['id']
    ?>"

    class="
        status
        <?= $statusClass ?>
    "
>

<?=
htmlspecialchars(
    $status
)
?>

</span>

</td>


<td>


<div
    class="actions"

    id="actions-<?=
    (int)$request['id']
    ?>"
>


<?php

if (
    $status === 'Pending'
):

?>


<button

type="button"

class="
    action-btn
    approve-btn
"

title="Approve"

onclick="
    updateLeaveStatus(
        <?=
        (int)$request['id']
        ?>,
        'Approved'
    )
"

>

✓

</button>


<button

type="button"

class="
    action-btn
    reject-btn
"

title="Reject"

onclick="
    updateLeaveStatus(
        <?=
        (int)$request['id']
        ?>,
        'Rejected'
    )
"

>

✕

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

<td
    colspan="8"
    class="empty"
>

    No leave requests found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</main>


<div
    id="message"
    class="message"
></div>


<script>


function showMessage(
    text,
    type = "success"
) {

    const message =
        document.getElementById(
            "message"
        );

    message.textContent =
        text;

    message.className =
        "message "
        + type;

    message.style.display =
        "block";


    setTimeout(
        function () {

            message.style.display =
                "none";

        },

        3500
    );
}


/*
|--------------------------------------------------------------------------
| Approve / Reject
|--------------------------------------------------------------------------
*/

function updateLeaveStatus(
    id,
    status
) {


const action =
    status === "Approved"
    ? "approve"
    : "reject";


const confirmed =
    confirm(

        "Are you sure you want to "
        + action
        + " this leave request?"

    );


if (!confirmed) {

    return;
}


const formData =
    new FormData();


formData.append(
    "ajax_action",
    "update_status"
);


formData.append(
    "id",
    id
);


formData.append(
    "status",
    status
);


/*
|--------------------------------------------------------------------------
| Send To Same PHP File
|--------------------------------------------------------------------------
*/

fetch(
    "leave_requests.php",
    {

        method: "POST",

        body: formData

    }
)

.then(
    response =>
        response.json()
)

.then(
    data => {


        if (data.success) {


            const statusBadge =
                document.getElementById(
                    "status-" + id
                );


            const actions =
                document.getElementById(
                    "actions-" + id
                );


            if (statusBadge) {


                statusBadge.textContent =
                    status;


                statusBadge.className =
                    "status";


                if (
                    status ===
                    "Approved"
                ) {

                    statusBadge.classList.add(
                        "status-approved"
                    );

                }
                else {

                    statusBadge.classList.add(
                        "status-rejected"
                    );

                }

            }


            if (actions) {

                actions.innerHTML =
                    '<span class="processed">'
                    + 'Processed'
                    + '</span>';

            }


            showMessage(
                data.message,
                "success"
            );


            /*
            |--------------------------------------------------------------------------
            | Refresh after approval so updated leave balance appears
            |--------------------------------------------------------------------------
            */

            setTimeout(
                function () {

                    window.location.reload();

                },

                1200
            );


        }
        else {


            showMessage(

                data.message
                ||
                "Could not update leave request.",

                "error"

            );

        }

    }
)

.catch(
    function (error) {


        console.error(
            error
        );


        showMessage(

            "Something went wrong.",

            "error"

        );

    }
);


}


</script>


</body>

</html>