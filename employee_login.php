<?php

session_start();

require_once __DIR__ . '/database.php';
/* If employee is already logged in */
if (isset($_SESSION["employee_id"])) {

    if (
        isset($_SESSION["camera_verified"]) &&
        $_SESSION["camera_verified"] === true
    ) {
        header("Location: employee_dashboard.php");
    } else {
        header("Location: employee_camera_auth.php");
    }

    exit();
}

$error = "";

/* Handle Login */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $employee_code =
        trim($_POST["employee_code"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    if (
        $employee_code === "" ||
        $email === ""
    ) {

        $error =
            "Please enter Employee ID and Email.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                id,
                employee_id,
                name,
                email,
                status
             FROM employees
             WHERE employee_id = ?
             AND email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error =
                "Database error: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $employee_code,
                $email
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);

            $employee =
                mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($employee) {

                if (
                    strtolower(
                        $employee["status"]
                    ) !== "active"
                ) {

                    $error =
                        "Your employee account is inactive.";

                } else {

                    /*
                    IMPORTANT:
                    employee_id session stores database ID.
                    employee_code stores emp002, emp001, etc.
                    */

                    $_SESSION["employee_id"] =
                        (int) $employee["id"];

                    $_SESSION["employee_code"] =
                        $employee["employee_id"];

                    $_SESSION["employee_name"] =
                        $employee["name"];

                    $_SESSION["employee_email"] =
                        $employee["email"];

                    /*
                    Camera verification must happen
                    after every new login.
                    */

                    $_SESSION["camera_verified"] =
                        false;

                    header(
                        "Location: employee_camera_auth.php"
                    );

                    exit();
                }

            } else {

                $error =
                    "Invalid Employee ID or Email.";
            }
        }
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

    min-height: 100vh;

    display: flex;

    justify-content: center;

    align-items: center;

    font-family:
        Arial,
        sans-serif;

    background:
        #f5f7fb;

    color:
        #17233c;
}

.login-container {

    width: 100%;

    max-width: 430px;

    padding: 20px;
}

.login-card {

    background:
        white;

    border:
        1px solid #e2e8f0;

    border-radius:
        16px;

    padding:
        35px;

    box-shadow:
        0 15px 40px
        rgba(0, 0, 0, 0.08);
}

.logo {

    text-align: center;

    color:
        #eb2535;

    font-size:
        25px;

    font-weight:
        bold;

    margin-bottom:
        30px;
}

h1 {

    text-align:
        center;

    margin-bottom:
        8px;
}

.subtitle {

    text-align:
        center;

    color:
        #64748b;

    margin-bottom:
        30px;
}

.form-group {

    margin-bottom:
        20px;
}

label {

    display:
        block;

    font-weight:
        bold;

    margin-bottom:
        8px;
}

input {

    width:
        100%;

    padding:
        13px;

    border:
        1px solid #cbd5e1;

    border-radius:
        8px;

    font-size:
        15px;

    outline:
        none;
}

input:focus {

    border-color:
        #eb2535;
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
        #d91f30;
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
        20px;

    text-align:
        center;
}

.camera-info {

    margin-top:
        20px;

    padding:
        12px;

    background:
        #f8fafc;

    border-radius:
        8px;

    color:
        #64748b;

    font-size:
        13px;

    text-align:
        center;
}

.admin-link {

    text-align:
        center;

    margin-top:
        22px;
}

.admin-link a {

    color:
        #eb2535;

    text-decoration:
        none;

    font-weight:
        bold;
}

</style>

</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="logo">

            ELMS Corporate

        </div>

        <h1>

            Employee Login

        </h1>

        <p class="subtitle">

            Sign in to your employee portal

        </p>

        <?php if ($error !== ""): ?>

            <div class="error">

                <?= htmlspecialchars(
                    $error
                ) ?>

            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>

                    Employee ID

                </label>

                <input
                    type="text"
                    name="employee_code"
                    placeholder="Example: emp002"
                    value="<?=
                        htmlspecialchars(
                            $_POST[
                                "employee_code"
                            ] ?? ""
                        )
                    ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label>

                    Registered Email

                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?=
                        htmlspecialchars(
                            $_POST[
                                "email"
                            ] ?? ""
                        )
                    ?>"
                    required
                >

            </div>

            <button type="submit">

                Login & Continue to Verification

            </button>

        </form>

        <div class="camera-info">

            📷 Camera verification is required
            after successful employee login.

        </div>

        <div class="admin-link">

            <a href="login.php">

                Admin Login

            </a>

        </div>

    </div>

</div>

</body>

</html>