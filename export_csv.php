<?php
// export_csv.php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/database.php';

// Retrieve filter criteria if passed
$statusFilter = $_GET['status'] ?? 'ALL';

// Build SQL Query based on filter
$sql = "
    SELECT 
        lr.id AS request_id,
        e.name AS employee_name,
        e.department AS department,
        lr.leave_type,
        lr.start_date,
        lr.end_date,
        lr.total_days,
        lr.status,
        lr.reason
    FROM leave_requests lr
    LEFT JOIN employees e ON lr.employee_id = e.id
";

if ($statusFilter !== 'ALL' && in_array($statusFilter, ['Approved', 'Pending', 'Rejected'])) {
    $sql .= " WHERE lr.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}

$sql .= " ORDER BY lr.id DESC";

$result = mysqli_query($conn, $sql);

// Set HTTP response headers to force CSV file download
$filename = "Leave_Report_" . date('Y-m-d_H-i') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open PHP output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, [
    'Request ID', 
    'Employee Name', 
    'Department', 
    'Leave Type', 
    'Start Date', 
    'End Date', 
    'Total Days', 
    'Status', 
    'Justification'
]);

// Write data rows
if ($result && mysqli_num_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['request_id'],
            $row['employee_name'] ?? ('Employee #' . $row['employee_id']),
            $row['department'] ?? 'General',
            $row['leave_type'],
            $row['start_date'],
            $row['end_date'],
            $row['total_days'],
            $row['status'],
            $row['reason']
        ]);
    }
}

fclose($output);
exit();