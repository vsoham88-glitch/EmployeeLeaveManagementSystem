<?php

session_start();

/*
|--------------------------------------------------------------------------
| If Admin Is Already Logged In
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Get Error Message
|--------------------------------------------------------------------------
*/

$errorMessage = "";

if (isset($_GET['error'])) {

    switch ($_GET['error']) {

        case 'empty':
            $errorMessage = "Please enter username and password.";
            break;

        case 'wrongpassword':
            $errorMessage = "Invalid username or password.";
            break;

        case 'usernotfound':
            $errorMessage = "Invalid username or password.";
            break;

        default:
            $errorMessage = "Login failed. Please try again.";
            break;
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

<title>Admin Login</title>

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", sans-serif;
}

body {

    display: flex;

    justify-content: center;

    align-items: center;

    min-height: 100vh;

    background: #f4f6f9;
}

.login-box {

    width: 380px;

    background: white;

    padding: 40px;

    border-radius: 15px;

    box-shadow:
        0 10px 30px
        rgba(0, 0, 0, 0.1);
}

.login-box h2 {

    text-align: center;

    margin-bottom: 30px;

    color: #c62828;
}

.input-group {

    margin-bottom: 20px;
}

.input-group input {

    width: 100%;

    padding: 14px;

    border: 1px solid #ccc;

    border-radius: 8px;

    font-size: 16px;

    outline: none;
}

.input-group input:focus {

    border-color: #c62828;
}

button {

    width: 100%;

    padding: 14px;

    border: none;

    background: #c62828;

    color: white;

    font-size: 17px;

    border-radius: 8px;

    cursor: pointer;
}

button:hover {

    background: #b71c1c;
}

.error {

    background: #ffeaea;

    color: #c62828;

    padding: 10px;

    border-radius: 7px;

    text-align: center;

    margin-bottom: 20px;

    font-size: 14px;
}

</style>

</head>


<body>


<div class="login-box">


<h2>

    <i class="fa-solid fa-user-shield"></i>

    Admin Login

</h2>


<?php if ($errorMessage !== ""): ?>

<div class="error">

    <?php echo htmlspecialchars($errorMessage); ?>

</div>

<?php endif; ?>


<form
    action="authenticate.php"
    method="POST"
>


<div class="input-group">

<input
    type="text"
    name="username"
    placeholder="Username"
    autocomplete="username"
    required
>

</div>


<div class="input-group">

<input
    type="password"
    name="password"
    placeholder="Password"
    autocomplete="current-password"
    required
>

</div>


<button type="submit">

<i class="fa-solid fa-right-to-bracket"></i>

Login

</button>


</form>


</div>


</body>

</html>