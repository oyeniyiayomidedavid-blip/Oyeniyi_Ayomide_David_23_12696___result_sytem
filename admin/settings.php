<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$message = "";
$error = "";

// Fetch current admin data
$stmt = $pdo->prepare("SELECT id, username, full_name, role, password FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin account not found.");
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $error = "All fields are required.";
    } elseif ($current_password !== $admin['password']) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdate->execute([$new_password, $admin_id]);
            $message = "Password updated successfully.";
            $admin['password'] = $new_password;
        } catch (PDOException $e) {
            $error = "Error updating password: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Settings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; margin:0; background:#f4f6f9; color:#0A1D3D; }

/* Sidebar scrollable */
.sidebar {
    width: 240px; position: fixed; top:0; left:0; bottom:0;
    background:#1f3d5a; color:#fff; padding:20px; overflow-y:auto; overflow-x:hidden;
}
.sidebar::-webkit-scrollbar { width: 6px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.35); border-radius:10px; }
.sidebar h3 { color:#fff; font-size:1.3rem; margin-bottom:30px; line-height:1.3; }
.sidebar a { color:#fff; text-decoration:none; display:block; padding:10px 15px; margin-bottom:5px; border-radius:5px; }
.sidebar a:hover, .sidebar a.active { background:#162d5c; }
.sidebar a i { margin-right: 8px; }

.content { margin-left:260px; padding:30px; }

.card-box { background:#fff; border-radius:12px; padding:25px; box-shadow:0 3px 10px rgba(0,0,0,0.1); margin-bottom:25px; }
.card-box h4 { margin-bottom:20px; font-weight:700; }

.form-control { border-radius:8px; padding:10px 12px; }
.btn-main { background:#0A1D3D; color:#fff; border:none; padding:10px 20px; border-radius:8px; }
.btn-main:hover { background:#1565C0; color:#fff; }
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
    <a href="reports.php"><i class="fa fa-file"></i> Reports</a>
    <a href="settings.php" class="active"><i class="fa fa-cog"></i> Settings</a>
    <hr style="border-color:#3e5c7c;">
    <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2>Settings</h2>
    <p>Manage your admin account information and password.</p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card-box">
        <h4>Account Information</h4>
        <p><strong>Full Name:</strong> <?= htmlspecialchars($admin['full_name']) ?></p>
        <p><strong>Username:</strong> <?= htmlspecialchars($admin['username']) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($admin['role']) ?></p>
    </div>

    <div class="card-box">
        <h4>Change Password</h4>
        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-main">Update Password</button>
        </form>
    </div>
</div>

</body>
</html>