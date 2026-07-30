<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Leave Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif}
body{background:#f4f7fb;color:#333}
header{position:sticky;top:0;background:#fff;display:flex;justify-content:space-between;align-items:center;padding:18px 50px;box-shadow:0 2px 12px rgba(0,0,0,.1)}
.logo{font-size:28px;font-weight:700;color:#2563eb}
nav a{text-decoration:none;color:#333;margin-left:20px}
.hero{background:linear-gradient(135deg,#2563eb,#0ea5e9);color:#fff;text-align:center;padding:80px 20px}
.hero h1{font-size:48px}.hero p{max-width:700px;margin:20px auto}
.btn{display:inline-block;background:#fff;color:#2563eb;padding:14px 28px;border-radius:30px;text-decoration:none;font-weight:600}
.stats,.features{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;padding:40px 8%}
.card{background:#fff;padding:30px;border-radius:16px;box-shadow:0 8px 18px rgba(0,0,0,.08);text-align:center}
.card i{font-size:42px;color:#2563eb;margin-bottom:12px}
.form{max-width:700px;margin:40px auto;background:#fff;padding:30px;border-radius:16px;box-shadow:0 8px 18px rgba(0,0,0,.08)}
.row{display:grid;grid-template-columns:1fr 1fr;gap:15px}
input,select,textarea{width:100%;padding:12px;margin:8px 0;border:1px solid #ccc;border-radius:8px}
textarea{height:120px}
button{width:100%;padding:14px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer}
footer{background:#0f172a;color:#fff;text-align:center;padding:25px;margin-top:40px}
@media(max-width:700px){.row{grid-template-columns:1fr}header{padding:15px 20px}}
</style>
</head>
<body>
<header><div class="logo">ELMS</div><nav><a href="#">Home</a><a href="#">Employees</a><a href="#">Leave</a><a href="#">Reports</a><a href="#">Contact</a></nav></header>
<section class="hero">
<h1>Employee Leave Management System</h1>
<p>Manage employee leave requests with a modern and professional HR platform.</p>
<a class="btn" href="#apply">Apply Leave</a>
</section>
<section class="stats">
<div class="card"><i class="fa-solid fa-users"></i><h2>250+</h2><p>Employees</p></div>
<div class="card"><i class="fa-solid fa-calendar-check"></i><h2>120</h2><p>Approved Leaves</p></div>
<div class="card"><i class="fa-solid fa-clock"></i><h2>18</h2><p>Pending</p></div>
<div class="card"><i class="fa-solid fa-building"></i><h2>12</h2><p>Departments</p></div>
</section>
<section class="features">
<div class="card"><i class="fa-solid fa-user"></i><h3>Employee Portal</h3><p>Apply and track leave requests.</p></div>
<div class="card"><i class="fa-solid fa-user-check"></i><h3>Manager Approval</h3><p>Approve or reject leave requests.</p></div>
<div class="card"><i class="fa-solid fa-chart-line"></i><h3>Reports</h3><p>View leave reports and analytics.</p></div>
</section>
<div class="form" id="apply">
<h2 style="text-align:center;color:#2563eb">Leave Application</h2>
<form onsubmit="return submitForm()">
<div class="row">
<input required placeholder="Employee Name">
<input type="email" required placeholder="Email">
</div>
<div class="row">
<input placeholder="Department">
<select><option>Casual Leave</option><option>Sick Leave</option><option>Paid Leave</option></select>
</div>
<div class="row">
<input type="date" required>
<input type="date" required>
</div>
<textarea placeholder="Reason"></textarea>
<button type="submit">Submit Leave Request</button>
</form>
</div>
<footer>© 2026 Employee Leave Management System</footer>
<script>
function submitForm(){alert("Leave request submitted successfully!");return false;}
</script>
</body>
</html>
