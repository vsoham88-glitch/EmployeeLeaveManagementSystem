<?php

session_start();

require_once __DIR__ . '/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header("Location: login.php?error=empty");
    exit();
}

$sql = "SELECT * FROM admins WHERE username = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Login query failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) === 1) {

    $admin = mysqli_fetch_assoc($result);

    $storedPassword = $admin['password'] ?? '';

    if (
        password_verify($password, $storedPassword) ||
        $password === $storedPassword
    ) {

        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $admin['id'] ?? null;
        $_SESSION['admin_name'] = $admin['username'] ?? $username;

        header("Location: index.php");
        exit();

    } else {

        header("Location: login.php?error=wrongpassword");
        exit();
    }

} else {

    header("Location: login.php?error=usernotfound");
    exit();
}
?>