<?php
session_start();
require_once "config/db.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? '';
    $identifier = $_POST['identifier'] ?? '';

    if ($role && $identifier) {
        if ($role == 'Admin') {
            // Correct table name for admin
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($role == 'Student') {
            // Correct table name for students
            $stmt = $pdo->prepare("SELECT * FROM students WHERE matric_no = ?");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $user = false;
        }

        if ($user) {
            $token = bin2hex(random_bytes(50));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            if ($role == 'Admin') {
                $update = $pdo->prepare("UPDATE admin SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            } else {
                $update = $pdo->prepare("UPDATE students SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            }
            $update->execute([$token, $expiry, $user['id']]);

            $resetLink = "http://localhost/result_system/reset_password.php?role={$role}&token={$token}";

            $headers = "From: Result System <your-email@gmail.com>\r\n";
            $headers .= "Reply-To: your-email@gmail.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $subject = "Password Reset Request";
            $message = "Hello,\n\nYou requested a password reset. Click the link below to reset your password. The link expires in 1 hour.\n\n$resetLink\n\nIf you did not request this, please ignore this email.";

            if (mail($user['email'], $subject, $message, $headers)) {
                $success = "Password reset link sent to your email.";
            } else {
                $error = "Failed to send email. Check your SMTP settings.";
            }
        } else {
            $error = "User not found.";
        }
    } else {
        $error = "Please provide both username/matric number and role.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Roboto', sans-serif; background:#f4f6f8; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.container { background:#fff; padding:40px; border-radius:10px; box-shadow:0 8px 20px rgba(0,0,0,0.15); width:400px; text-align:center; }
input, select, button { width:100%; padding:12px; margin:10px 0; border-radius:6px; font-size:14px; border:1px solid #ccc; }
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
<h2>Forgot Password</h2>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>
<form method="POST">
    <input type="text" name="identifier" placeholder="Enter Username or Matric No" required>
    <select name="role" required>
        <option value="">Select Role</option>
        <option value="Admin">Admin</option>
        <option value="Student">Student</option>
    </select>
    <button type="submit">Send Reset Link</button>
</form>
<a href="login.php">Back to Login</a>
</div>
</body>
</html>