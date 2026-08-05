<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = "";

// Get ID from query string
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: users.php");
    exit;
}

// Fetch the user record
try {
    $stmt = $pdo->prepare("SELECT id, full_name, username, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user: " . $e->getMessage());
}

if (!$user) {
    header("Location: users.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $role      = trim($_POST['role'] ?? '');

    // Validation
    if ($full_name === '') {
        $errors[] = "Full name is required.";
    }
    if ($username === '') {
        $errors[] = "Username is required.";
    }
    if (!in_array($role, ['admin officer', 'lecturer'])) {
        $errors[] = "Please select a valid role.";
    }

    // Check username uniqueness (excluding current user)
    if (empty($errors)) {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$username, $id]);
            if ($check->fetch()) {
                $errors[] = "That username is already taken by another user.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error checking username: " . $e->getMessage();
        }
    }

    // Update the record
    if (empty($errors)) {
        try {
            $update = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role = ? WHERE id = ?");
            $update->execute([$full_name, $username, $role, $id]);

            // Refresh local data and show success
            $user['full_name'] = $full_name;
            $user['username']  = $username;
            $user['role']      = $role;
            $success = "User updated successfully.";
        } catch (PDOException $e) {
            $errors[] = "Error updating user: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; margin:0; background:#f4f6f9; color:#0A1D3D; }
.sidebar { width: 240px; position: fixed; top:0; left:0; bottom:0; background:#1f3d5a; color:#fff; padding:20px; overflow-y:auto; }
.sidebar h3 { color:#fff; font-size:1.3rem; margin-bottom:30px; }
.sidebar a { color:#fff; text-decoration:none; display:block; padding:10px 15px; margin-bottom:5px; border-radius:5px; }
.sidebar a:hover, .sidebar a.active { background:#162d5c; }
.content { margin-left:260px; padding:30px; }
.form-container { background:#fff; border-radius:10px; padding:30px; box-shadow:0 3px 10px rgba(0,0,0,0.1); max-width:600px; }
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
    <a href="sessions.php"><i class="fa fa-calendar"></i> Sessions</a>
    <a href="users.php" class="active"><i class="fa fa-user"></i> Users</a>
    <a href="reports.php"><i class="fa fa-file"></i> Reports</a>
    <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
    <hr style="border-color:#3e5c7c;">
    <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2>Edit User</h2>
    <p>Update the details for this user account.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="edit_user.php?id=<?= $user['id'] ?>">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="admin officer" <?= $user['role'] === 'admin officer' ? 'selected' : '' ?>>Admin Officer</option>
                    <option value="lecturer" <?= $user['role'] === 'lecturer' ? 'selected' : '' ?>>Lecturer</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-action"><i class="fa fa-save"></i> Save Changes</button>
            <a href="users.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Cancel</a>
        </form>
    </div>
</div>
</body>
</html>