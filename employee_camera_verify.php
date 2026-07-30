<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["employee_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Employee session not found."
    ]);

    exit();
}

/*
This is still a DEMO verification step.
A real biometric version would compare
the captured face against a stored face.
*/

$_SESSION["camera_verified"] = true;

echo json_encode([
    "success" => true
]);