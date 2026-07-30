<?php
session_start();

// 1. FAIL-SAFE DATABASE CONNECTION
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
} else if (file_exists(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
} else {
    die("Error: Could not locate database.php file.");
}

$error = "";

// 2. FETCH ACTIVE EMPLOYEES
$employees = mysqli_query(
    $conn,
    "SELECT id, employee_id, name 
     FROM employees 
     WHERE status = 'Active' 
     ORDER BY name ASC"
);

// 3. FETCH LEAVE TYPES
$leaveTypes = mysqli_query(
    $conn,
    "SELECT id, leave_name 
     FROM leave_types 
     ORDER BY leave_name ASC"
);

// 4. HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $employee_id = (int)($_POST["employee_id"] ?? 0);
    $leave_type  = trim($_POST["leave_type"] ?? "");
    $start_date  = $_POST["start_date"] ?? "";
    $end_date    = $_POST["end_date"] ?? "";
    $reason      = trim($_POST["reason"] ?? "");

    if (
        $employee_id <= 0 ||
        $leave_type === "" ||
        $start_date === "" ||
        $end_date === "" ||
        $reason === ""
    ) {
        $error = "Please fill all required fields.";

    } elseif ($end_date < $start_date) {

        $error = "End date cannot be earlier than start date.";

    } else {

        // Calculate total leave days safely using DateTime
        $d1 = new DateTime($start_date);
        $d2 = new DateTime($end_date);
        $diff = $d1->diff($d2);
        $total_days = $diff->days + 1; // Including both start & end dates

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO leave_requests 
            (
                employee_id, 
                leave_type, 
                start_date, 
                end_date, 
                total_days, 
                reason, 
                status
            ) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
        );

        if (!$stmt) {
            die("SQL Prepare Failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isssis",
            $employee_id,
            $leave_type,
            $start_date,
            $end_date,
            $total_days,
            $reason
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: index.php?leave_submitted=1");
            exit;
        } else {
            $error = "Failed to submit leave request: " . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Time Off - ELMS Corporate</title>

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
    max-width: 850px;
    margin: 40px auto;
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
select,
textarea {
    padding: 12px;
    border: 1px solid #d6dce5;
    border-radius: 7px;
    font-size: 15px;
}

textarea {
    min-height: 120px;
    resize: vertical;
}

input:focus,
select:focus,
textarea:focus {
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
</style>
</head>

<body>

<div class="navbar">
    <h2>ELMS Corporate</h2>
    <div>
        <a href="index.php">Dashboard</a>
        <a href="employees.php">Employees</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Request Time Off</h1>
    <p>Submit a new employee leave request.</p>

    <div class="card">
        <?php if ($error !== ""): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">

                <div class="form-group">
                    <label>
                        Employee <span class="required">*</span>
                    </label>
                    <select name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php
                        if ($employees):
                            while ($employee = mysqli_fetch_assoc($employees)):
                        ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['employee_id'] . " - " . $employee['name']); ?>
                            </option>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        Leave Type <span class="required">*</span>
                    </label>
                    <select name="leave_type" required>
                        <option value="">Select Leave Type</option>
                        <?php
                        if ($leaveTypes):
                            while ($type = mysqli_fetch_assoc($leaveTypes)):
                        ?>
                            <option value="<?php echo htmlspecialchars($type['leave_name']); ?>">
                                <?php echo htmlspecialchars($type['leave_name']); ?>
                            </option>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        Start Date <span class="required">*</span>
                    </label>
                    <input type="date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label>
                        End Date <span class="required">*</span>
                    </label>
                    <input type="date" name="end_date" required>
                </div>

                <div class="form-group full">
                    <label>
                        Reason <span class="required">*</span>
                    </label>
                    <textarea name="reason" placeholder="Enter reason for leave..." required></textarea>
                </div>

            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">
                    Submit Leave Request
                </button>
                <a href="index.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>