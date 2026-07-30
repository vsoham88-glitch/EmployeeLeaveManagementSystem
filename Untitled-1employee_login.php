<?php

session_start();

require_once __DIR__ . '/database.php';

$error = "";

/* If employee is already logged in */
if (isset($_SESSION['employee_id'])) {
    header("Location: employee_dashboard.php");
    exit();
}

/* Handle login */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter email and password.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, employee_id, name, email, password, status
             FROM employees
             WHERE email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $employee = mysqli_fetch_assoc($result);

        if (!$employee) {

            $error = "Employee account not found.";

        } elseif ($employee["status"] !== "Active") {

            $error = "Your employee account is inactive.";

        } elseif (!password_verify(
            $password,
            $employee["password"]
        )) {

            $error = "Incorrect password.";

        } else {

            /*
             * Store employee login details.
             * Camera verification will happen next.
             */

            $_SESSION["employee_id"] =
                $employee["id"];

            $_SESSION["employee_code"] =
                $employee["employee_id"];

            $_SESSION["employee_name"] =
                $employee["name"];

            $_SESSION["employee_email"] =
                $employee["email"];

            $_SESSION["camera_verified"] = false;

            header(
                "Location: employee_camera_auth.php"
            );

            exit();
        }

        mysqli_stmt_close($stmt);
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
    Employee Login - ELMS
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
        #f5f7fb;

    min-height:
        100vh;

    display:
        flex;

    justify-content:
        center;

    align-items:
        center;
}

.login-card {

    width:
        420px;

    background:
        white;

    padding:
        35px;

    border-radius:
        16px;

    box-shadow:
        0 15px 40px
        rgba(
            0,
            0,
            0,
            0.08
        );
}

.logo {

    color:
        #eb2535;

    font-size:
        24px;

    font-weight:
        bold;

    margin-bottom:
        8px;
}

.subtitle {

    color:
        #64748b;

    margin-bottom:
        28px;
}

.form-group {

    margin-bottom:
        18px;
}

label {

    display:
        block;

    margin-bottom:
        7px;

    font-weight:
        bold;
}

input {

    width:
        100%;

    padding:
        12px;

    border:
        1px solid
        #cbd5e1;

    border-radius:
        8px;

    font-size:
        15px;
}

button {

    width:
        100%;

    padding:
        13px;

    background:
        #eb2535;

    color:
        white;

    border:
        none;

    border-radius:
        8px;

    font-size:
        15px;

    font-weight:
        bold;

    cursor:
        pointer;
}

button:hover {

    background:
        #c91d2c;
}

.error {

    background:
        #fee2e2;

    color:
        #b91c1c;

    padding:
        12px;

    border-radius:
        8px;

    margin-bottom:
        18px;
}

.back {

    text-align:
        center;

    margin-top:
        20px;
}

.back a {

    color:
        #64748b;

    text-decoration:
        none;
}

</style>

</head>

<body>

<div class="login-card">

    <div class="logo">
        ELMS Corporate
    </div>

    <h1>
        Employee Login
    </h1>

    <p class="subtitle">
        Sign in to access your
        employee leave portal.
    </p>

    <?php if ($error !== ""): ?>

        <div class="error">

            <?=
            htmlspecialchars(
                $error
            )
            ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label>
                Employee Email
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
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Enter password"
                required
            >

        </div>

        <button type="submit">

            Login & Continue
            to Camera Verification

        </button>

    </form>

    <div class="back">

        <a href="login.php">
            ← Admin Login
        </a>

    </div>

</div>

</body>

</html>