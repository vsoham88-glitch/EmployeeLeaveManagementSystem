<?php

session_start();

unset($_SESSION["employee_id"]);
unset($_SESSION["employee_code"]);
unset($_SESSION["employee_name"]);
unset($_SESSION["employee_email"]);
unset($_SESSION["camera_verified"]);

header("Location: employee_login.php");
exit();