<?php
session_start();

// Database Connection
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
} elseif (file_exists(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
} else {
    // Default fallback
    $conn = mysqli_connect("localhost", "root", "", "employee_db");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = mysqli_real_escape_string($conn, $_POST['employee_name'] ?? '');
    $leave_type    = mysqli_real_escape_string($conn, $_POST['leave_type'] ?? '');
    $start_date    = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
    $end_date      = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');
    $reason        = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');

    // 1. Find employee ID by name (or fallback to user session ID if available)
    $emp_id = $_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? null;

    if (!$emp_id && !empty($employee_name)) {
        $empQuery = mysqli_query($conn, "SELECT id FROM employees WHERE name LIKE '%$employee_name%' LIMIT 1");
        if ($empQuery && mysqli_num_rows($empQuery) > 0) {
            $row = mysqli_fetch_assoc($empQuery);
            $emp_id = $row['id'];
        }
    }

    // Default to ID 1 if no matching employee is found
    if (!$emp_id) {
        $emp_id = 1; 
    }

    // 2. Insert into database (excluding 'employee_name')
    $query = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status) 
              VALUES ('$emp_id', '$leave_type', '$start_date', '$end_date', '$reason', 'Pending')";

    if (mysqli_query($conn, $query)) {
        header("Location: index.php?status=success");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>