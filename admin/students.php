<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch students along with department names
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name 
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    ORDER BY s.id ASC
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Students</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {font-family: 'Segoe UI', sans-serif; margin:0; background:#f4f6f9;}
.sidebar {width:240px; position:fixed; top:0; left:0; bottom:0; background:#1f3d5a; color:#fff; padding:20px; overflow-y:auto;}
.sidebar h3 {color:#fff; font-size:1.3rem; margin-bottom:30px;}
.sidebar a {color:#fff; text-decoration:none; display:block; padding:10px 15px; margin-bottom:5px; border-radius:5px;}
.sidebar a:hover {background:#162d5c;}
.content {margin-left:260px; padding:30px;}
.table-container {background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1);}
.table thead {background:#343a40; color:#fff;}
.btn-view {background:#17a2b8; color:#fff;}
.btn-edit {background:#ffc107; color:#fff;}
.btn-delete {background:#dc3545; color:#fff;}
.btn-view:hover {background:#138496;}
.btn-edit:hover {background:#e0a800;}
.btn-delete:hover {background:#c82333;}
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
    <h2>Students</h2>
    <p>Below is a list of all registered students. You can view, edit, or delete a student record.</p>

    <div class="table-container">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Matric No</th>
                    <th>Department</th>
                    <th>Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['full_name']) ?></td>
                    <td><?= htmlspecialchars($student['matric_no']) ?></td>
                    <td><?= htmlspecialchars($student['department_name']) ?></td>
                    <td><?= htmlspecialchars($student['level']) ?></td>
                    <td>
                        <a href="view_student.php?id=<?= $student['id'] ?>" class="btn btn-view btn-sm"><i class="fa fa-eye"></i> View</a>
                        <a href="edit_student.php?id=<?= $student['id'] ?>" class="btn btn-edit btn-sm"><i class="fa fa-edit"></i> Edit</a>
                        <a href="delete_student.php?id=<?= $student['id'] ?>" class="btn btn-delete btn-sm" onclick="return confirm('Are you sure?');"><i class="fa fa-trash"></i> Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>