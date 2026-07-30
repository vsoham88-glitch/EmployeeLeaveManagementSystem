<?php

require_once __DIR__ . '/database.php';

$message = "";
$error = "";

// Fetch departments
$departments = mysqli_query(
    $conn,
    "SELECT id, department_name FROM departments ORDER BY department_name ASC"
);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $employee_id   = trim($_POST["employee_id"] ?? "");
    $name          = trim($_POST["name"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $phone         = trim($_POST["phone"] ?? "");
    $password      = $_POST["password"] ?? "";
    $department_id = $_POST["department_id"] ?? "";
    $designation   = trim($_POST["designation"] ?? "");
    $joining_date  = $_POST["joining_date"] ?? "";
    $leave_balance = $_POST["leave_balance"] ?? 25;
    $status        = $_POST["status"] ?? "Active";

    if (
        $employee_id === "" ||
        $name === "" ||
        $email === "" ||
        $password === ""
    ) {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        // Check duplicate employee ID or email
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM employees
             WHERE employee_id = ? OR email = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "ss",
            $employee_id,
            $email
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "Employee ID or email already exists.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $departmentValue =
                $department_id !== ""
                ? (int)$department_id
                : null;

            $joiningDateValue =
                $joining_date !== ""
                ? $joining_date
                : null;

            $leaveBalanceValue = (int)$leave_balance;

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO employees
                (
                    employee_id,
                    name,
                    email,
                    phone,
                    password,
                    department_id,
                    designation,
                    joining_date,
                    leave_balance,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssssissis",
                $employee_id,
                $name,
                $email,
                $phone,
                $hashed_password,
                $departmentValue,
                $designation,
                $joiningDateValue,
                $leaveBalanceValue,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {

                header("Location: employees.php?added=1");
                exit;

            } else {

                $error =
                    "Failed to add employee: "
                    . mysqli_stmt_error($stmt);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Add Employee - ELMS Corporate</title>

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
    background: white;
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

.navbar a {
    text-decoration: none;
    color: #44546a;
    margin-left: 25px;
}

.container {
    width: 90%;
    max-width: 900px;
    margin: 40px auto;
}

.page-title {
    margin-bottom: 25px;
}

.page-title h1 {
    margin-bottom: 8px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    margin-bottom: 7px;
    font-weight: bold;
    color: #46566f;
}

input,
select {
    padding: 12px;
    border: 1px solid #d6dce5;
    border-radius: 7px;
    font-size: 15px;
}

input:focus,
select:focus {
    outline: none;
    border-color: #ed1c2e;
}

.required {
    color: #ed1c2e;
}

.actions {
    margin-top: 25px;
    display: flex;
    gap: 12px;
}

.btn {
    border: none;
    padding: 12px 20px;
    border-radius: 7px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
}

.btn-primary {
    background: #ed1c2e;
    color: white;
}

.btn-primary:hover {
    background: #c91425;
}

.btn-secondary {
    background: #e9edf3;
    color: #36465e;
}

.error {
    background: #fdecec;
    color: #b42318;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
}

@media (max-width: 700px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<div class="navbar">

    <h2>ELMS Corporate</h2>

    <div>

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

    <div class="page-title">

        <h1>Add New Employee</h1>

        <p>
            Enter employee information below.
        </p>

    </div>

    <div class="card">

        <?php if ($error !== ""): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        Employee ID
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="employee_id"
                        placeholder="Example: EMP001"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Full Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Employee full name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Email
                        <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="employee@example.com"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        placeholder="Phone number"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Password
                        <span class="required">*</span>
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Create password"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Department
                    </label>

                    <select name="department_id">

                        <option value="">
                            Select Department
                        </option>

                        <?php
                        if (
                            $departments &&
                            mysqli_num_rows($departments) > 0
                        ):
                        ?>

                            <?php
                            while (
                                $department =
                                mysqli_fetch_assoc($departments)
                            ):
                            ?>

                                <option
                                    value="<?php
                                    echo $department['id'];
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $department[
                                            'department_name'
                                        ]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Designation
                    </label>

                    <input
                        type="text"
                        name="designation"
                        placeholder="Example: Software Engineer"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Joining Date
                    </label>

                    <input
                        type="date"
                        name="joining_date"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Leave Balance
                    </label>

                    <input
                        type="number"
                        name="leave_balance"
                        value="25"
                        min="0"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>

            </div>

            <div class="actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Employee
                </button>

                <a
                    href="employees.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>