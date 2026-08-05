<?php
session_start();
require_once "../config/db.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch reports (example: all results)
try {
    $stmt = $pdo->query("
        SELECT r.id, s.full_name, s.matric_no, c.course_code, c.course_title, r.grade, r.session, r.semester
        FROM results r
        INNER JOIN students s ON r.student_id = s.id
        INNER JOIN courses c ON r.course_id = c.id
        ORDER BY r.id DESC
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching reports: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; margin:0; background:#f4f6f9; color:#0A1D3D; }
.sidebar { width: 240px; position: fixed; top: 0; left: 0; bottom: 0; background:#1f3d5a; color:#fff; padding:20px; overflow-y:auto; }
.sidebar h3 { color:#fff; font-size:1.3rem; margin-bottom:30px; }
.sidebar a { color:#fff; text-decoration:none; display:block; padding:10px 15px; margin-bottom:5px; border-radius:5px; }
.sidebar a:hover, .sidebar a.active { background:#162d5c; }
.content { margin-left:260px; padding:30px; }
.table-container { background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1); }
.table thead { background:#343a40; color:#fff; }
</style>
</head>
<body>

<div class="sidebar">
    <h3>RESULT MANAGEMENT & VERIFICATION</h3>
    <a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a href="students.php"><i class="fa fa-users"></i> Students</a>
    <a href="courses.php"><i class="fa fa-book"></i> Courses</a>
    <a href="results.php"><i class="fa fa-graduation-cap"></i> Results</a>
    <a href="result_approval.php"><i class="fa fa-check-circle"></i> Result Approval</a>
    <a href="verification_codes.php"><i class="fa fa-key"></i> Verification Codes</a>
    <a href="departments.php"><i class="fa fa-building"></i> Departments</a>
    <a href="sessions.php"><i class="fa fa-calendar"></i> Sessions</a>
    <a href="users.php"><i class="fa fa-user"></i> Users</a>
    <a href="reports.php" class="active"><i class="fa fa-file"></i> Reports</a>
    <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
    <hr style="border-color:#3e5c7c;">
    <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2>Reports</h2>
    <p>Below is a list of all results for reporting purposes.</p>

    <div class="table-container">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Matric No</th>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Grade</th>
                    <th>Session</th>
                    <th>Semester</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($reports) > 0): ?>
                    <?php foreach($reports as $rep): ?>
                        <tr>
                            <td><?= htmlspecialchars($rep['full_name']) ?></td>
                            <td><?= htmlspecialchars($rep['matric_no']) ?></td>
                            <td><?= htmlspecialchars($rep['course_code']) ?></td>
                            <td><?= htmlspecialchars($rep['course_title']) ?></td>
                            <td><?= htmlspecialchars($rep['grade']) ?></td>
                            <td><?= htmlspecialchars($rep['session']) ?></td>
                            <td><?= htmlspecialchars($rep['semester']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No results found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>