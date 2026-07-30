<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '//database.php';

$message = "";

/* Save settings */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $company_name = trim($_POST['company_name']);
    $annual_leave_days = (int) $_POST['annual_leave_days'];
    $sick_leave_days = (int) $_POST['sick_leave_days'];
    $theme = $_POST['theme'];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE system_settings
         SET company_name = ?,
             annual_leave_days = ?,
             sick_leave_days = ?,
             theme = ?
         WHERE id = 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "siis",
        $company_name,
        $annual_leave_days,
        $sick_leave_days,
        $theme
    );

    if (mysqli_stmt_execute($stmt)) {
        $message = "Settings saved successfully!";
    } else {
        $message = "Failed to save settings.";
    }

    mysqli_stmt_close($stmt);
}

/* Load saved settings */
$result = mysqli_query(
    $conn,
    "SELECT * FROM system_settings WHERE id = 1"
);

$settings = mysqli_fetch_assoc($result);

$company_name = $settings['company_name'] ?? 'ELMS Corporate';
$annual_leave_days = $settings['annual_leave_days'] ?? 25;
$sick_leave_days = $settings['sick_leave_days'] ?? 10;
$theme = $settings['theme'] ?? 'Light';

/* Apply saved theme */
$bodyClass = ($theme === 'Dark') ? 'dark-theme' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>System Settings - ELMS</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f8fafc;
    color: #111827;
    margin: 0;
    padding: 40px;
}

h1 {
    margin-bottom: 30px;
}

.settings-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    max-width: 700px;
}

.setting-row {
    margin-bottom: 20px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}

input,
select {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-sizing: border-box;
    background: white;
    color: #111827;
}

button {
    background: #eb2535;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
}

.success-message {
    background: #dcfce7;
    color: #15803d;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
}

a {
    display: inline-block;
    margin-top: 25px;
    color: #eb2535;
    text-decoration: none;
    font-weight: bold;
}

/* DARK THEME */

body.dark-theme {
    background: #111827;
    color: #f8fafc;
}

body.dark-theme .settings-card {
    background: #1f2937;
    border-color: #374151;
}

body.dark-theme h1,
body.dark-theme label {
    color: #f8fafc;
}

body.dark-theme input,
body.dark-theme select {
    background: #111827;
    color: #f8fafc;
    border-color: #4b5563;
}

body.dark-theme .success-message {
    background: #14532d;
    color: #dcfce7;
}

</style>

</head>

<body class="<?= htmlspecialchars($bodyClass) ?>">

<h1>System Settings</h1>

<div class="settings-card">

<?php if (!empty($message)): ?>

<div class="success-message">
    <?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>

<form method="POST">

<div class="setting-row">

<label>Company Name</label>

<input
    type="text"
    name="company_name"
    value="<?= htmlspecialchars($company_name) ?>"
    required
>

</div>

<div class="setting-row">

<label>Default Annual Leave Days</label>

<input
    type="number"
    name="annual_leave_days"
    value="<?= $annual_leave_days ?>"
    min="0"
    required
>

</div>

<div class="setting-row">

<label>Default Sick Leave Days</label>

<input
    type="number"
    name="sick_leave_days"
    value="<?= $sick_leave_days ?>"
    min="0"
    required
>

</div>

<div class="setting-row">

<label>System Theme</label>

<select name="theme">

<option value="Light"
    <?= $theme === 'Light' ? 'selected' : '' ?>>
    Light
</option>

<option value="Dark"
    <?= $theme === 'Dark' ? 'selected' : '' ?>>
    Dark
</option>

</select>

</div>

<button type="submit">
    Save Settings
</button>

</form>

</div>

<a href="index.php">← Back to Dashboard</a>

</body>
</html>