<?php
session_start();

// Authentication Guard
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
} else {
    require_once __DIR__ . '/database.php';
}

// 1. SYSTEM SETTINGS & ADMIN DECLARATION
$settingsQuery = mysqli_query($conn, "SELECT company_name FROM system_settings WHERE id = 1");
$settings = mysqli_fetch_assoc($settingsQuery);
$companyName = $settings['company_name'] ?? 'ELMS Enterprise';

// Assigned Administrator Name
$adminName = "Soham Vaidya";

// Fetch Logged-in User/Employee Data from Database
$sessionUserKey = $_SESSION['admin_id'] ?? $_SESSION['admin'] ?? $_SESSION['user_id'] ?? $_SESSION['user'] ?? null;

$userQuery = mysqli_query($conn, "SELECT id, name, role, department, email FROM employees WHERE id = '$sessionUserKey' OR email = '$sessionUserKey' LIMIT 1");

if ($userQuery && mysqli_num_rows($userQuery) > 0) {
    $userData = mysqli_fetch_assoc($userQuery);
    $displayName = $userData['name'];
    $displayRole = $userData['role'] ?? 'Employee';
    $displayDept = $userData['department'] ?? 'General';
    $currentEmpId = $userData['id'];
} else {
    $displayName = $adminName;
    $displayRole = 'System Administrator';
    $displayDept = 'Management';
    $currentEmpId = '';
}

// 2. METRICS QUERIES
$totalEmployeesQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees");
$totalEmployees = mysqli_fetch_assoc($totalEmployeesQuery)['total'] ?? 0;

$approvedQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status = 'Approved'");
$approvedLeaves = mysqli_fetch_assoc($approvedQuery)['total'] ?? 0;

$pendingQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status = 'Pending'");
$pendingLeaves = mysqli_fetch_assoc($pendingQuery)['total'] ?? 0;

$daysLostQuery = mysqli_query($conn, "SELECT SUM(total_days) AS total_days FROM leave_requests WHERE status = 'Approved'");
$totalDaysLost = mysqli_fetch_assoc($daysLostQuery)['total_days'] ?? 0;

// 3. FETCH LEAVE REQUESTS WITH CONNECTED EMPLOYEE DATA + ADMIN ASSIGNMENT
$leaveRequests = [];
$leaveQuery = mysqli_query($conn, "
    SELECT 
        lr.id, 
        lr.employee_id, 
        lr.leave_type, 
        lr.start_date, 
        lr.end_date, 
        lr.total_days, 
        lr.reason, 
        lr.status, 
        COALESCE(lr.created_at, lr.posting_date, NOW()) AS requested_at,
        COALESCE(e.name, lr.employee_name) AS employee_name, 
        COALESCE(e.department, 'General') AS employee_dept
    FROM leave_requests lr
    LEFT JOIN employees e ON lr.employee_id = e.id
    ORDER BY lr.id DESC
");

if ($leaveQuery) {
    while ($row = mysqli_fetch_assoc($leaveQuery)) {
        if (empty($row['employee_name'])) {
            $row['employee_name'] = 'Employee #' . $row['employee_id'];
        }
        $row['admin_assigned'] = $adminName; // Admin set to Soham Vaidya
        $row['formatted_time'] = date('M d, Y - h:i A', strtotime($row['requested_at']));
        $leaveRequests[] = $row;
    }
}

// 4. FETCH EMPLOYEES CURRENTLY OUT OF OFFICE TODAY
$outOfOffice = [];
$todayDate = date('Y-m-d');
$oooQuery = mysqli_query($conn, "
    SELECT lr.leave_type, lr.end_date, e.name, e.department 
    FROM leave_requests lr 
    JOIN employees e ON lr.employee_id = e.id 
    WHERE lr.status = 'Approved' 
      AND '$todayDate' BETWEEN lr.start_date AND lr.end_date 
    LIMIT 5
");
if ($oooQuery) {
    while ($ooo = mysqli_fetch_assoc($oooQuery)) {
        $outOfOffice[] = $ooo;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($companyName) ?> - Leave Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            /* RED THEME PALETTE */
            --primary: #dc2626;
            --primary-hover: #b91c1c;
            --primary-light: #fef2f2;
            --primary-glow: rgba(220, 38, 38, 0.15);
            
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            
            --radius-xl: 20px;
            --radius-lg: 14px;
            --radius-md: 10px;
            --radius-sm: 6px;
            --transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.05);
            --shadow-lg: 0 20px 30px -10px rgba(0,0,0,0.08);
        }

        [data-theme="dark"] {
            /* DARK RED PALETTE */
            --primary: #ef4444;
            --primary-hover: #f87171;
            --primary-light: rgba(239, 68, 68, 0.15);
            --primary-glow: rgba(239, 68, 68, 0.25);
            
            --success: #34d399;
            --success-light: rgba(52, 211, 153, 0.15);
            --warning: #fbbf24;
            --warning-light: rgba(251, 191, 36, 0.15);
            --danger: #f87171;
            --danger-light: rgba(248, 113, 113, 0.15);
            
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --bg-main: #0b0f19;
            --bg-card: #151d30;
            --border: #1e293b;
            --border-hover: #334155;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.4);
            --shadow-lg: 0 20px 30px -10px rgba(0,0,0,0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        aside {
            width: 280px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
            transition: var(--transition);
        }

        .brand {
            padding: 24px 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 19px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
            border-bottom: 1px solid var(--border);
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .sidebar-menu {
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-radius: var(--radius-md);
            transition: var(--transition);
        }

        .nav-item:hover {
            color: var(--primary);
            background: var(--primary-light);
            transform: translateX(4px);
        }

        .nav-item.active {
            color: #ffffff;
            background: var(--primary);
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .nav-item i {
            font-size: 16px;
        }

        .theme-toggle-box {
            padding: 12px 16px;
            margin: 0 16px 12px 16px;
            background: var(--bg-main);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .theme-toggle-box span {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .theme-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            cursor: pointer;
            color: var(--text-main);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .theme-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .user-profile {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #f87171);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 2px 8px var(--primary-glow);
        }

        .user-info h4 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
        }

        .user-info p {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Main Workspace */
        main {
            margin-left: 280px;
            flex: 1;
            padding: 36px 40px;
            max-width: 1440px;
            transition: var(--transition);
        }

        .mobile-header {
            display: none;
            padding: 16px 20px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .dashboard-header h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .dashboard-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Buttons & Badges */
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 12px 22px;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            box-shadow: 0 4px 14px var(--primary-glow);
            text-decoration: none;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--primary-glow);
        }

        .notice-banner {
            background: var(--primary-light);
            color: var(--text-main);
            padding: 16px 22px;
            border-radius: var(--radius-lg);
            margin-bottom: 28px;
            border: 1px solid rgba(220, 38, 38, 0.2);
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px;
            font-weight: 500;
        }

        .notice-banner i {
            color: var(--primary);
            font-size: 20px;
        }

        /* Cards Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 24px;
            border-radius: var(--radius-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-hover);
        }

        .metric-details p {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-details h2 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .metric-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: var(--transition);
        }

        .metric-card:hover .metric-icon {
            transform: scale(1.1) rotate(6deg);
        }

        .grid-two-cols {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 28px;
            align-items: start;
            margin-bottom: 28px;
        }

        .grid-charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 28px;
            align-items: start;
            margin-bottom: 28px;
        }

        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 26px;
            box-shadow: var(--shadow-sm);
        }

        .section-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .section-card-header h3 {
            font-size: 17px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-card-header h3 i {
            color: var(--primary);
        }

        /* Quotas Progress */
        .balance-item {
            margin-bottom: 20px;
        }

        .balance-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .balance-label {
            font-weight: 700;
            color: var(--text-main);
        }

        .balance-value {
            color: var(--text-muted);
            font-weight: 600;
        }

        .balance-progress-track {
            height: 10px;
            background: var(--bg-main);
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .balance-progress-bar {
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }

        /* Out of Office List */
        .ooo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border);
        }

        .ooo-item:last-child {
            border-bottom: none;
        }

        /* Table Styling */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 220px;
            max-width: 360px;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-box input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            background: var(--bg-main);
            color: var(--text-main);
            outline: none;
            transition: var(--transition);
        }

        .search-box input:focus {
            border-color: var(--primary);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--bg-main);
            color: var(--text-main);
            font-size: 14px;
            font-weight: 600;
            outline: none;
            cursor: pointer;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: var(--bg-main);
            padding: 14px 18px;
            font-weight: 700;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: var(--primary-light);
        }

        .emp-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .emp-avatar-mini {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #f87171);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-approved { background: var(--success-light); color: var(--success); }
        .badge-pending { background: var(--warning-light); color: var(--warning); }
        .badge-rejected { background: var(--danger-light); color: var(--danger); }

        .actions-cell {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-icon {
            border: none;
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
        }

        .btn-approve { color: var(--success); background: var(--success-light); }
        .btn-approve:hover { background: var(--success); color: #fff; }
        .btn-reject { color: var(--danger); background: var(--danger-light); }
        .btn-reject:hover { background: var(--danger); color: #fff; }

        /* Request Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease-in-out;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            width: 92%;
            max-width: 580px;
            padding: 32px;
            box-shadow: var(--shadow-lg);
            transform: translateY(20px);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid var(--border);
        }

        .modal-overlay.active .modal-box {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h2 {
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-close {
            background: var(--bg-main);
            border: 1px solid var(--border);
            font-size: 18px;
            color: var(--text-muted);
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .btn-close:hover {
            background: var(--danger-light);
            color: var(--danger);
            border-color: var(--danger);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            outline: none;
            background: var(--bg-main);
            color: var(--text-main);
            transition: var(--transition);
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .requestor-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--primary-light);
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: var(--radius-md);
            margin-bottom: 20px;
        }

        .requestor-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .modal-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-secondary {
            background: var(--bg-main);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 11px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        @media (max-width: 1080px) {
            .grid-two-cols, .grid-charts {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            aside {
                transform: translateX(-100%);
            }

            aside.active {
                transform: translateX(0);
            }

            main {
                margin-left: 0;
                padding: 20px 16px;
            }

            .mobile-header {
                display: flex;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="app-container" id="app-workspace">
        
        <!-- Sidebar Navigation Module -->
        <aside id="sidebar">
            <div class="brand">
                <div class="brand-icon"><i class="fa-solid fa-layer-group"></i></div>
                <span><?= htmlspecialchars($companyName) ?></span>
            </div>
            
            <div class="sidebar-menu">
                <a href="index.php" class="nav-item active">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
                <a href="leave_requests.php" class="nav-item">
                    <i class="fa-solid fa-calendar-days"></i> Leave Requests
                </a>
                <a href="admin_notifications.php" class="nav-item">
                    <i class="fa-solid fa-bell"></i> Notifications
                    <?php if ($pendingLeaves > 0): ?>
                        <span style="margin-left:auto; min-width:24px; height:24px; padding:0 7px; border-radius:20px; background:var(--danger); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:800;">
                            <?= (int) $pendingLeaves ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="attendance.php" class="nav-item">
                    <i class="fa-solid fa-user-check"></i> Attendance
                </a>
                <a href="employees.php" class="nav-item">
                    <i class="fa-solid fa-users"></i> Team Directory
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fa-solid fa-chart-line"></i> Analytics Reports
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fa-solid fa-sliders"></i> System Settings
                </a>
            </div>

            <!-- Appearance Mode Switch -->
            <div class="theme-toggle-box">
                <span>Theme Mode</span>
                <button class="theme-btn" id="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>

            <!-- Admin Profile (Soham Vaidya) -->
            <div class="user-profile">
                <div class="avatar" id="profile-avatar">SV</div>
                <div class="user-info">
                    <h4 id="profile-name"><?= htmlspecialchars($adminName) ?></h4>
                    <p>System Administrator</p>
                </div>
            </div>

            <div style="padding: 16px; border-top: 1px solid var(--border);">
                <a href="logout.php" class="btn-secondary" style="width:100%; display:inline-flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main>
            <!-- Mobile Navigation Bar -->
            <div class="mobile-header">
                <div style="display:flex; align-items:center; gap:10px; font-weight:800; color:var(--primary);">
                    <i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($companyName) ?>
                </div>
                <button onclick="toggleSidebar()" class="btn-secondary" style="padding:8px 12px;">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <!-- Red Style Notice Banner -->
            <div class="notice-banner">
                <i class="fa-solid fa-shield-halved"></i>
                <span><strong>Admin Mode Active:</strong> Logged in as Administrator <strong><?= htmlspecialchars($adminName) ?></strong>. Managing all workforce leave approvals.</span>
            </div>

            <div class="dashboard-header">
                <div>
                    <h1>Workforce Absence Dashboard</h1>
                    <p>Overview of workforce presence, absence tracking, and leave approvals.</p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="admin_notifications.php" class="btn-secondary" style="display:inline-flex; align-items:center; gap:9px; text-decoration:none; position:relative;">
                        <i class="fa-solid fa-bell"></i>
                        Notifications
                        <?php if ($pendingLeaves > 0): ?>
                            <span style="min-width:22px; height:22px; padding:0 6px; border-radius:20px; background:var(--danger); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:800;">
                                <?= (int) $pendingLeaves ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <button onclick="toggleModal(true)" class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Request Time Off
                    </button>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-details">
                        <p>Total Headcount</p>
                        <h2><?php echo $totalEmployees; ?></h2>
                    </div>
                    <div class="metric-icon" style="background: var(--primary-light); color: var(--primary);">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-details">
                        <p>Approved Absences</p>
                        <h2><?php echo $approvedLeaves; ?></h2>
                    </div>
                    <div class="metric-icon" style="background: var(--success-light); color: var(--success);">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-details">
                        <p>Pending Verification</p>
                        <h2><?php echo $pendingLeaves; ?></h2>
                    </div>
                    <div class="metric-icon" style="background: var(--warning-light); color: var(--warning);">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-details">
                        <p>Working Days Lost</p>
                        <h2><?php echo $totalDaysLost; ?></h2>
                    </div>
                    <div class="metric-icon" style="background: var(--danger-light); color: var(--danger);">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                </div>
            </div>

            <!-- Charts Analytics Row -->
            <div class="grid-charts">
                <div class="section-card">
                    <div class="section-card-header">
                        <h3><i class="fa-solid fa-chart-column"></i> Absence Frequency & Trends</h3>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="leaveTrendChart"></canvas>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-card-header">
                        <h3><i class="fa-solid fa-chart-pie"></i> Leave Distribution</h3>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="leaveTypeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Main Content Area: Quotas & Leave Table -->
            <div class="grid-two-cols">
                <!-- Side Panel: Personal Quotas & Out Of Office -->
                <div style="display:flex; flex-direction:column; gap:28px;">
                    <!-- Personal Quotas -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <h3><i class="fa-solid fa-wallet"></i> Leave Quotas</h3>
                        </div>
                        <div id="leave-balances-container">
                            <div class="balance-item">
                                <div class="balance-info">
                                    <span class="balance-label">Casual Leave</span>
                                    <span class="balance-value">8 / 12 Days Used</span>
                                </div>
                                <div class="balance-progress-track">
                                    <div class="balance-progress-bar" style="width: 66%; background: var(--primary);"></div>
                                </div>
                            </div>

                            <div class="balance-item">
                                <div class="balance-info">
                                    <span class="balance-label">Sick Leave</span>
                                    <span class="balance-value">3 / 10 Days Used</span>
                                </div>
                                <div class="balance-progress-track">
                                    <div class="balance-progress-bar" style="width: 30%; background: var(--warning);"></div>
                                </div>
                            </div>

                            <div class="balance-item" style="margin-bottom:0;">
                                <div class="balance-info">
                                    <span class="balance-label">Earned Leave</span>
                                    <span class="balance-value">12 / 15 Days Used</span>
                                </div>
                                <div class="balance-progress-track">
                                    <div class="balance-progress-bar" style="width: 80%; background: var(--success);"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Out Of Office Today -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <h3><i class="fa-solid fa-user-clock"></i> Out Today</h3>
                            <span class="badge badge-approved"><?= count($outOfOffice) ?> Active</span>
                        </div>
                        <div id="ooo-container">
                            <?php if (!empty($outOfOffice)): ?>
                                <?php foreach ($outOfOffice as $ooo): ?>
                                    <div class="ooo-item">
                                        <div>
                                            <strong style="font-size:14px; display:block;"><?= htmlspecialchars($ooo['name']) ?></strong>
                                            <span style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($ooo['department']) ?> &bull; <?= htmlspecialchars($ooo['leave_type']) ?></span>
                                        </div>
                                        <span class="badge badge-pending" style="font-size:11px;">
                                            Until <?= date('M d', strtotime($ooo['end_date'])) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size:13px; color:var(--text-muted); text-align:center; padding:12px 0;">
                                    All workforce members present today.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Table Area -->
                <div class="section-card">
                    <div class="section-card-header">
                        <h3><i class="fa-solid fa-list-check"></i> Recent Requests</h3>
                    </div>

                    <div class="table-controls">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="tableSearch" placeholder="Search employee or leave type..." onkeyup="filterTable()">
                        </div>
                        <select class="filter-select" id="statusFilter" onchange="filterTable()">
                            <option value="ALL">All Statuses</option>
                            <option value="PENDING">Pending</option>
                            <option value="APPROVED">Approved</option>
                            <option value="REJECTED">Rejected</option>
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table id="requestsTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($leaveRequests)): ?>
                                    <?php foreach ($leaveRequests as $req): ?>
                                        <?php 
                                            $initials = strtoupper(substr($req['employee_name'], 0, 2));
                                            $statusClass = strtolower($req['status']);
                                        ?>
                                        <tr data-status="<?= strtoupper($req['status']) ?>">
                                            <td>
                                                <div class="emp-cell">
                                                    <div class="emp-avatar-mini"><?= $initials ?></div>
                                                    <div>
                                                        <strong style="display:block; color:var(--text-main);"><?= htmlspecialchars($req['employee_name']) ?></strong>
                                                        <span style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($req['employee_dept']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight:600;"><?= htmlspecialchars($req['leave_type']) ?></span>
                                            </td>
                                            <td>
                                                <span style="font-size:13px;">
                                                    <?= date('M d', strtotime($req['start_date'])) ?> - <?= date('M d, Y', strtotime($req['end_date'])) ?>
                                                </span>
                                            </td>
                                            <td><strong><?= $req['total_days'] ?></strong></td>
                                            <td>
                                                <span class="badge badge-<?= $statusClass ?>">
                                                    <i class="fa-solid fa-circle" style="font-size:6px;"></i>
                                                    <?= htmlspecialchars($req['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    <?php if (strtolower($req['status']) === 'pending'): ?>
                                                        <button class="btn-icon btn-approve" title="Approve Request" onclick="updateStatus(<?= $req['id'] ?>, 'Approved')">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                        <button class="btn-icon btn-reject" title="Reject Request" onclick="updateStatus(<?= $req['id'] ?>, 'Rejected')">
                                                            <i class="fa-solid fa-xmark"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="font-size:12px; color:var(--text-muted); padding:4px 8px;">Reviewed</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center; color:var(--text-muted); padding:24px;">
                                            No leave requests recorded yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Leave Request Modal Form -->
    <div class="modal-overlay" id="leaveModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2><i class="fa-solid fa-calendar-plus" style="color:var(--primary);"></i> Apply Leave</h2>
                <button class="btn-close" onclick="toggleModal(false)"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="requestor-badge">
                <div class="requestor-avatar">SV</div>
                <div>
                    <h4 style="font-size:14px; font-weight:700;"><?= htmlspecialchars($displayName) ?></h4>
                    <p style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($displayRole) ?> &bull; <?= htmlspecialchars($displayDept) ?></p>
                </div>
            </div>

            <form action="process_leave.php" method="POST">
                <div class="form-group">
                    <label>Leave Type</label>
                    <select class="form-control" name="leave_type" required>
                        <option value="Casual Leave">Casual Leave</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Earned Leave">Earned Leave</option>
                        <option value="Unpaid Leave">Unpaid Leave</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" name="end_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Reason for Absence</label>
                    <textarea class="form-control" name="reason" rows="3" placeholder="Provide brief explanation..." required></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="toggleModal(false)">Cancel</button>
                    <button type="submit" class="btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Theme Switcher Functionality
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            const icon = document.querySelector('#theme-toggle-btn i');
            icon.className = newTheme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }

        // Mobile Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Modal Control
        function toggleModal(show) {
            const modal = document.getElementById('leaveModal');
            if (show) {
                modal.classList.add('active');
            } else {
                modal.classList.remove('active');
            }
        }

        // Real-Time Table Filtering
        function filterTable() {
            const searchValue = document.getElementById('tableSearch').value.toLowerCase();
            const statusValue = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#requestsTable tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const status = row.getAttribute('data-status');
                
                const matchesSearch = text.includes(searchValue);
                const matchesStatus = (statusValue === 'ALL' || status === statusValue);

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Action Trigger Placeholder
        function updateStatus(id, action) {
            if (confirm(`Are you sure you want to mark request #${id} as ${action}?`)) {
                alert(`Request #${id} marked as ${action}.`);
                // Add AJAX or redirection logic here
            }
        }

        // Initialize Analytics Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Trend Line/Bar Chart
            const ctxTrend = document.getElementById('leaveTrendChart').getContext('2d');
            new Chart(ctxTrend, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Days Taken',
                        data: [12, 19, 8, 15, 22, 10, 14],
                        backgroundColor: '#dc2626',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // Leave Type Doughnut Chart
            const ctxType = document.getElementById('leaveTypeChart').getContext('2d');
            new Chart(ctxType, {
                type: 'doughnut',
                data: {
                    labels: ['Casual', 'Sick', 'Earned'],
                    datasets: [{
                        data: [45, 25, 30],
                        backgroundColor: ['#dc2626', '#f59e0b', '#10b981'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    cutout: '70%'
                }
            });
        });
    </script>
</body>
</html>