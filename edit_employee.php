<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id'])) {
    die("Employee ID missing.");
}

$id = (int) $_GET['id'];

/* Load employee */
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM employees WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$employee) {
    die("Employee not found.");
}

/* Update employee */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $designation = trim($_POST['designation']);
    $joining_date = $_POST['joining_date'];
    $leave_balance = (int) $_POST['leave_balance'];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE employees
         SET name = ?,
             email = ?,
             phone = ?,
             department = ?,
             designation = ?,
             joining_date = ?,
             leave_balance = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssii",
        $name,
        $email,
        $phone,
        $department,
        $designation,
        $joining_date,
        $leave_balance,
        $id
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: employees.php?updated=1");
        exit();
    }

    $error = "Failed to update employee.";

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Employee - ELMS</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f8fafc;
    padding: 40px;
}

.form-card {
    max-width: 700px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

input {
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
}

button {
    background: #eb2535;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
}

a {
    display: inline-block;
    margin-top: 20px;
    color: #eb2535;
    text-decoration: none;
}

.error {
    color: red;
    margin-bottom: 20px;
}

</style>

</head>

<body>

<div class="form-card">

<h1>Edit Employee</h1>

<?php if (!empty($error)): ?>
<div class="error">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-group">
<label>Name</label>
<input
    type="text"
    name="name"
    value="<?= htmlspecialchars($employee['name']) ?>"
    required
>
</div>

<div class="form-group">
<label>Email</label>
<input
    type="email"
    name="email"
    value="<?= htmlspecialchars($employee['email']) ?>"
    required
>
</div>

<div class="form-group">
<label>Phone</label>
<input
    type="text"
    name="phone"
    value="<?= htmlspecialchars($employee['phone']) ?>"
>
</div>

<div class="form-group">
<label>Department</label>
<input
    type="text"
    name="department name"
    value="<?= htmlspecialchars($employee['department name']) ?>"
>
</div>

<div class="form-group">
<label>Designation</label>
<input
    type="text"
    name="designation"
    value="<?= htmlspecialchars($employee['designation']) ?>"
>
</div>

<div class="form-group">
<label>Joining Date</label>
<input
    type="date"
    name="joining_date"
    value="<?= htmlspecialchars($employee['joining_date']) ?>"
>
</div>

<div class="form-group">
<label>Leave Balance</label>
<input
    type="number"
    name="leave_balance"
    value="<?= (int) $employee['leave_balance'] ?>"
>
</div>

<button type="submit">
Update Employee
</button>

</form>

<a href="employees.php">← Back to Employees</a>

</div>

</body>
</html>