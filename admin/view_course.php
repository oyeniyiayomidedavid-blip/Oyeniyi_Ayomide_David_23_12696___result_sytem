<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "No course ID provided.";
    exit;
}

$course_id = $_GET['id'];

// Fetch course with department name using LEFT JOIN
$stmt = $pdo->prepare("
    SELECT c.*, d.department_name
    FROM courses c
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE c.id = ?
");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    echo "Course not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Course - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {font-family: 'Segoe UI', sans-serif; margin:0; background:#f4f6f9;}
.sidebar {width:240px; position:fixed; top:0; left:0; bottom:0; background:#1f3d5a; color:#fff; padding:20px; overflow-y:auto;}
.sidebar h3 {color:#fff; font-size:1.3rem; margin-bottom:30px;}
.sidebar a {color:#fff; text-decoration:none; display:block; padding:10px 15px; margin-bottom:5px; border-radius:5px;}
.sidebar a:hover {background:#162d5c;}
.content {margin-left:260px; padding:30px;}
.card {border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.1);}
.card-header {background:#343a40; color:#fff; font-size:1.2rem;}
.card-body dt {width:150px;}
</style>
</head>
<body>

<div class="sidebar">
    <h3>RESULT MANAGEMENT & VERIFICATION</h3>
    <a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a href="students.php"><i class="fa fa-users"></i> Students</a>
    <a href="courses.php"><i class="fa fa-book"></i> Courses</a>
    <a href="results.php"><i class="fa fa-graduation-cap"></i> Results</a>
    <a href="approve_results.php"><i class="fa fa-check-circle"></i> Result Approval</a>
    <a href="verification_codes.php"><i class="fa fa-key"></i> Verification Codes</a>
    <a href="departments.php"><i class="fa fa-building"></i> Departments</a>
    <a href="sessions.php"><i class="fa fa-calendar"></i> Sessions</a>
    <a href="users.php"><i class="fa fa-user"></i> Users</a>
    <a href="reports.php"><i class="fa fa-file-alt"></i> Reports</a>
    <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
    <hr style="border-color:#3e5c7c;">
    <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2>View Course Details</h2>
    <p>Details for <strong><?= htmlspecialchars($course['course_title']) ?></strong>.</p>

    <div class="card">
        <div class="card-header">
            Course Information
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Course Code</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($course['course_code']) ?></dd>

                <dt class="col-sm-3">Course Title</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($course['course_title']) ?></dd>

                <dt class="col-sm-3">Unit Load</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($course['unit_load'] ?? '-') ?></dd>

                <dt class="col-sm-3">Credit Unit</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($course['credit_unit'] ?? '-') ?></dd>

                <dt class="col-sm-3">Department</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($course['department_name'] ?? 'Not Set') ?></dd>
            </dl>

            <a href="courses.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Courses</a>
            <a href="edit_course.php?id=<?= $course['id'] ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Edit</a>
            <a href="delete_course.php?id=<?= $course['id'] ?>" class="btn btn-danger"><i class="fa fa-trash"></i> Delete</a>
        </div>
    </div>
</div>

</body>
</html>