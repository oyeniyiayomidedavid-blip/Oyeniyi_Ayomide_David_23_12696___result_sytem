<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch all students for dropdown
$students_stmt = $pdo->prepare("SELECT id, full_name FROM students ORDER BY full_name ASC");
$students_stmt->execute();
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;

    if (!$student_id) {
        $msg = "Please select a student.";
    } else {
        // Generate unique 8-character code
        do {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $check = $pdo->prepare("SELECT * FROM verification_codes WHERE code = ? AND student_id = ?");
            $check->execute([$code, $student_id]);
        } while ($check->rowCount() > 0);

        // Insert new code
        $insert = $pdo->prepare("INSERT INTO verification_codes (student_id, code, status, created_at) VALUES (?, ?, 'Unused', NOW())");
        $insert->execute([$student_id, $code]);

        $msg = "Verification code $code added successfully for the selected student.";
    }
}

// Fetch all verification codes
$codes_stmt = $pdo->prepare("
    SELECT v.*, s.full_name
    FROM verification_codes v
    JOIN students s ON v.student_id = s.id
    ORDER BY v.created_at DESC
");
$codes_stmt->execute();
$codes = $codes_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Verification Code - Admin</title>
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
.badge-unused {background-color:#28a745;}
.badge-used {background-color:#6c757d;}
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
    <h2>Add New Verification Code</h2>
    <?php if($msg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-6">
            <label>Student</label>
            <select class="form-select" name="student_id" required>
                <option value="">Select Student</option>
                <?php foreach($students as $student): ?>
                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Code</label>
            <input type="text" class="form-control" value="Auto-generated" readonly>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Generate / Add Code</button>
        </div>
    </form>

    <div class="table-container">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($codes as $code): ?>
                    <tr>
                        <td><?= htmlspecialchars($code['code']) ?></td>
                        <td><?= htmlspecialchars($code['full_name']) ?></td>
                        <td><?= htmlspecialchars($code['student_id']) ?></td>
                        <td>
                            <?php if($code['status'] === 'Used'): ?>
                                <span class="badge badge-used">Used</span>
                            <?php else: ?>
                                <span class="badge badge-unused">Unused</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($code['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>