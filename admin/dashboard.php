<?php
session_start();
require_once "../config/db.php";

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Fetch admin info
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch statistics for dashboard
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$total_results = $pdo->query("SELECT COUNT(*) FROM results")->fetchColumn();
$verified_results = $pdo->query("SELECT COUNT(*) FROM results WHERE status='Approved'")->fetchColumn();
$recent_results = $pdo->query("SELECT r.*, s.full_name, c.course_code, c.course_title FROM results r 
                                INNER JOIN students s ON r.student_id=s.id
                                INNER JOIN courses c ON r.course_id=c.id
                                ORDER BY r.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Result Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family:'Segoe UI', sans-serif; margin:0; background:#f4f6f9; }
.sidebar {
    width:250px; position:fixed; top:0; left:0; bottom:0;
    background:#1f3d5a; color:#fff; padding:20px; overflow-y:auto;
}
.sidebar h3 { font-size:1.3rem; margin-bottom:30px; }
.sidebar a { color:#fff; text-decoration:none; display:block; padding:10px 15px; margin-bottom:5px; border-radius:5px; }
.sidebar a:hover { background:#162d5c; }
.content { margin-left:270px; padding:30px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.cards { display:flex; gap:20px; margin-bottom:30px; flex-wrap:wrap; }
.card-box { flex:1; background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1); text-align:center; }
.card-box h4 { margin-bottom:10px; }
.table-container, .change-pass-container { background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1); margin-bottom:20px; }
.table thead { background:#343a40; color:#fff; }
.user-info {
    display:flex;
    align-items:center;
    justify-content:center;
    width:50px;
    height:50px;
    background:#1f3d5a;
    color:#fff;
    border-radius:50%;
    font-size:22px;
}
</style>
</head>
<body>

<div class="sidebar">
    <h3>RESULT MANAGEMENT & VERIFICATION</h3>
    <a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a href="students.php"><i class="fa fa-users"></i> Students</a>
    <a href="courses.php"><i class="fa fa-book"></i> Courses</a>
    <a href="results.php"><i class="fa fa-file-alt"></i> Results</a>
    <a href="verification_codes.php"><i class="fa fa-key"></i> Verification Codes</a>
    <a href="departments.php"><i class="fa fa-building"></i> Departments</a>
    <a href="sessions.php"><i class="fa fa-calendar"></i> Sessions</a>
    <a href="users.php"><i class="fa fa-user"></i> Users</a>
    <a href="reports.php"><i class="fa fa-chart-bar"></i> Reports</a>
    <a href="settings.php"><i class="fa fa-cogs"></i> Settings</a>
    <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <div class="header">
        <h2>Welcome back, <?= htmlspecialchars($admin['username']) ?>!</h2>
        <div class="user-info">
            <i class="fa fa-user"></i>
        </div>
    </div>

    <div class="cards">
        <div class="card-box">
            <h4>Total Students</h4>
            <h2><?= $total_students ?></h2>
            <p>View all students</p>
        </div>
        <div class="card-box">
            <h4>Total Courses</h4>
            <h2><?= $total_courses ?></h2>
            <p>View all courses</p>
        </div>
        <div class="card-box">
            <h4>Total Results</h4>
            <h2><?= $total_results ?></h2>
            <p>View all results</p>
        </div>
        <div class="card-box">
            <h4>Verified Results</h4>
            <h2><?= $verified_results ?></h2>
            <p>View verified</p>
        </div>
    </div>

    <div class="table-container">
        <h3>Recent Results</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Grade</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($recent_results as $res): ?>
                <tr>
                    <td><?= htmlspecialchars($res['full_name']) ?></td>
                    <td><?= htmlspecialchars($res['course_title']) ?></td>
                    <td><?= htmlspecialchars($res['grade']) ?></td>
                    <td><?= htmlspecialchars($res['status']) ?></td>
                    <td><?= htmlspecialchars($res['uploaded_at'] ?? date('Y-m-d')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a href="results.php" class="btn btn-primary mt-2">View All Results</a>
    </div>
</div>

</body>
</html>