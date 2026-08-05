<?php
session_start();
require_once "../config/db.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch sessions
try {
    $stmt = $pdo->query("SELECT * FROM sessions ORDER BY id DESC");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching sessions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sessions</title>
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
.btn-action { margin-right:5px; }
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
    <a href="sessions.php" class="active"><i class="fa fa-calendar"></i> Sessions</a>
    <a href="users.php"><i class="fa fa-user"></i> Users</a>
    <a href="reports.php"><i class="fa fa-file"></i> Reports</a>
    <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
    <hr style="border-color:#3e5c7c;">
    <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2>Sessions</h2>
    <p>Below is a list of all academic sessions. You can add a new session, edit, or delete an existing session.</p>

    <div class="table-container">
        <a href="add_session.php" class="btn btn-primary mb-3"><i class="fa fa-plus"></i> Add New Session</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Session Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($sessions) > 0): ?>
                    <?php foreach($sessions as $s): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td><?= htmlspecialchars($s['session_name']) ?></td>
                            <td><?= htmlspecialchars($s['start_date']) ?></td>
                            <td><?= htmlspecialchars($s['end_date']) ?></td>
                            <td><?= ucfirst($s['status']) ?></td>
                            <td>
                                <a href="edit_session.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm btn-action"><i class="fa fa-edit"></i> Edit</a>
                                <a href="delete_session.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm btn-action"><i class="fa fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No sessions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>