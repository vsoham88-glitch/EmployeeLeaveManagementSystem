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
| Fetch Employees With Department Name
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT 
        employees.*,
        departments.department_name

    FROM employees

    LEFT JOIN departments
        ON employees.department_id = departments.id

    ORDER BY employees.id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
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

<title>Employees - ELMS Corporate</title>

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

    background: #ffffff;

    padding: 18px 35px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom: 1px solid #ddd;
}

.navbar h2 {

    margin: 0;

    color: #ed1c2e;
}

.nav-links a {

    text-decoration: none;

    color: #44546a;

    margin-left: 25px;

    font-weight: 500;
}

.nav-links a:hover {

    color: #ed1c2e;
}

.container {

    width: 92%;

    margin: 35px auto;
}

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}

.page-header h1 {

    margin: 0;
}

.add-btn {

    background: #ed1c2e;

    color: white;

    padding: 12px 20px;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;
}

.add-btn:hover {

    background: #c91425;
}

.card {

    background: white;

    border-radius: 10px;

    padding: 25px;

    box-shadow: 0 2px 8px rgba(0,0,0,0.05);

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;
}

th {

    background: #f5f7fb;

    color: #53657d;

    text-align: left;

    padding: 15px;

    white-space: nowrap;
}

td {

    padding: 15px;

    border-bottom: 1px solid #eeeeee;

    vertical-align: middle;
}

tr:hover {

    background: #fafbfc;
}

.status-active {

    background: #e8f8ee;

    color: #159447;

    padding: 6px 12px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;

    display: inline-block;
}

.status-inactive {

    background: #fdecec;

    color: #d93025;

    padding: 6px 12px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;

    display: inline-block;
}

.edit-btn {

    display: inline-block;

    text-decoration: none;

    background: #17233c;

    color: white;

    padding: 7px 12px;

    border-radius: 6px;

    font-size: 13px;
}

.edit-btn:hover {

    background: #ed1c2e;
}

.empty {

    text-align: center;

    padding: 40px;

    color: #777;
}

.back-btn {

    display: inline-block;

    margin-top: 20px;

    text-decoration: none;

    color: #ed1c2e;

    font-weight: bold;
}

</style>

</head>


<body>


<div class="navbar">

    <h2>ELMS Corporate</h2>

    <div class="nav-links">

        <a href="index.php">
            Dashboard
        </a>

        <a href="employees.php">
            Employees
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</div>


<div class="container">


<div class="page-header">

    <div>

        <h1>
            Employee Directory
        </h1>

        <p>
            Manage and view employees in your organization.
        </p>

    </div>


    <a
        href="add_employee.php"
        class="add-btn"
    >
        + Add Employee
    </a>

</div>


<div class="card">


<table>


<thead>

<tr>

    <th>Employee ID</th>

    <th>Name</th>

    <th>Email</th>

    <th>Phone</th>

    <th>Department</th>

    <th>Designation</th>

    <th>Joining Date</th>

    <th>Leave Balance</th>

    <th>Status</th>

    <th>Actions</th>

</tr>

</thead>


<tbody>


<?php if (mysqli_num_rows($result) > 0): ?>


<?php while ($employee = mysqli_fetch_assoc($result)): ?>


<tr>


<td>

<?php

echo htmlspecialchars(
    $employee['employee_id'] ?? '-'
);

?>

</td>


<td>

<strong>

<?php

echo htmlspecialchars(
    $employee['name'] ?? '-'
);

?>

</strong>

</td>


<td>

<?php

echo htmlspecialchars(
    $employee['email'] ?? '-'
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $employee['phone'] ?? '-'
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $employee['department_name']
    ?? 'Not Assigned'
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $employee['designation']
    ?? '-'
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $employee['joining_date']
    ?? '-'
);

?>

</td>


<td>

<?php

echo (int)(
    $employee['leave_balance']
    ?? 0
);

?>

Days

</td>


<td>

<?php

$status = $employee['status'] ?? 'Inactive';

?>

<?php if ($status === 'Active'): ?>

<span class="status-active">

    Active

</span>

<?php else: ?>

<span class="status-inactive">

    Inactive

</span>

<?php endif; ?>

</td>


<td>

<a
    href="edit_employee.php?id=<?php echo (int)$employee['id']; ?>"
    class="edit-btn"
>
    Edit
</a>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
    colspan="10"
    class="empty"
>

    No employees found yet.

    <br><br>

    Click

    <strong>
        + Add Employee
    </strong>

    to add your first employee.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


<a
    href="index.php"
    class="back-btn"
>

    ← Back to Dashboard

</a>


</div>


</body>

</html>