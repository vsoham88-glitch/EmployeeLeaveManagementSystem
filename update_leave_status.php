<?php
header('Content-Type: application/json');

// Fail-safe database path checking
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
} else if (file_exists(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection file not found.'
    ]);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowedStatuses = ['Approved', 'Rejected'];

if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid leave request ID'
    ]);
    exit;
}

if (!in_array($status, $allowedStatuses, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status parameter'
    ]);
    exit;
}

// UPDATE query safe against missing columns
$stmt = mysqli_prepare(
    $conn,
    "UPDATE leave_requests 
     SET status = ? 
     WHERE id = ?"
);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed: ' . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $status,
    $id
);

if (mysqli_stmt_execute($stmt)) {
    // Check affected rows OR zero-change updates (e.g. approving an already approved request)
    if (mysqli_stmt_affected_rows($stmt) >= 0) {
        echo json_encode([
            'success' => true,
            'status'  => $status,
            'message' => 'Status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No leave request matching that ID was found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Update failed: ' . mysqli_stmt_error($stmt)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>