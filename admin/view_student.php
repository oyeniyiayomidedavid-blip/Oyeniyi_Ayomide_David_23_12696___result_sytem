<?php
session_start();
require_once "../config/db.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if 'id' is provided
if (!isset($_GET['id'])) {
    echo "No student ID provided.";
    exit;
}

$id = $_GET['id'];

// Fetch student details with department
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Student not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Student - Admin</title>
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
    <h2>View Student Details</h2>
    <p>Details for <strong><?= htmlspecialchars($student['full_name']) ?></strong>.</p>

    <div class="card">
        <div class="card-header">
            Student Information
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Full Name</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($student['full_name']) ?></dd>

                <dt class="col-sm-3">Matric Number</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($student['matric_no']) ?></dd>

                <dt class="col-sm-3">Department</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($student['department_name']) ?></dd>

                <dt class="col-sm-3">Level</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($student['level']) ?></dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($student['email'] ?? '-') ?></dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($student['phone'] ?? '-') ?></dd>

                <dt class="col-sm-3">Date of Birth</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($student['dob'] ?? '-') ?></dd>
            </dl>
            <a href="students.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to List</a>
            <a href="edit_student.php?id=<?= $student['id'] ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Edit</a>
            <a href="delete_student.php?id=<?= $student['id'] ?>" class="btn btn-danger"><i class="fa fa-trash"></i> Delete</a>
        </div>
    </div>
</div>

</body>
</html>