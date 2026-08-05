<?php
session_start();
require_once "config/db.php";

$error = '';
$success = '';

if (isset($_GET['role'], $_GET['token'])) {
    $role = $_GET['role'];
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT * FROM " . strtolower($role) . " WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Invalid or expired token.");
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE " . strtolower($role) . " SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            $update->execute([$hashed_password, $user['id']]);
            $success = "Password successfully updated. <a href='login.php'>Login</a>";
        }
    }
} else {
    die("Invalid request.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Roboto', sans-serif; background:#f4f6f8; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.container { background:#fff; padding:40px; border-radius:10px; box-shadow:0 8px 20px rgba(0,0,0,0.15); width:400px; text-align:center; }
input, button { width:100%; padding:12px; margin:10px 0; border-radius:6px; font-size:14px; border:1px solid #ccc; }
button { background:#0A1D3D; color:#fff; border:none; cursor:pointer; font-size:16px; }
button:hover { background:#1565C0; }
.error { color:red; margin-bottom:10px; }
.success { color:green; margin-bottom:10px; }
a { display:block; margin-top:10px; font-size:14px; color:#1565C0; text-decoration:none; }
a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container">
<h2>Reset Password</h2>
<?php if ($error) echo "<p class='error'>$error</p>"; ?>
<?php if ($success) echo "<p class='success'>$success</p>"; ?>
<?php if (!$success): ?>
<form method="POST">
    <input type="password" name="password" placeholder="Enter New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
    <button type="submit">Reset Password</button>
</form>
<?php endif; ?>
</div>
</body>
</html>