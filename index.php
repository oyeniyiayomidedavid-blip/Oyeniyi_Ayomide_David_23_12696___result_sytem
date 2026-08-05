<?php
session_start();
require_once "config/db.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($role && $username && $password) {
        if ($role === 'Admin') {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['role'] = 'Admin';
                $_SESSION['admin_id'] = $user['id'];
                header("Location: admin/dashboard.php");
                exit;
            } else {
                $error = "Invalid Admin credentials.";
            }
        } elseif ($role === 'Lecturer') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = 'lecturer'");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['role'] = 'Lecturer';
                $_SESSION['lecturer_id'] = $user['id'];
                header("Location: admin/lecturer/dashboard.php");
                exit;
            } else {
                $error = "Invalid Lecturer credentials.";
            }
        } elseif ($role === 'Student') {
            $stmt = $pdo->prepare("SELECT * FROM students WHERE matric_no = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['role'] = 'Student';
                $_SESSION['student_id'] = $user['id'];
                header("Location: student/student_dashboard.php");
                exit;
            } else {
                $error = "Invalid Student credentials.";
            }
        } else {
            $error = "Please select a valid role.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Result Management & Verification System</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
body, html { margin:0; padding:0; font-family:'Roboto', sans-serif; height:100%; }
.container { display:flex; height:100vh; }
.left-panel {
    flex:1; background:#0A1D3D; color:#fff; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:40px;
}
.left-panel h1 { font-size:36px; margin-bottom:20px; text-align:center; }
.left-panel p { font-size:16px; line-height:1.6; max-width:400px; text-align:center; }
.right-panel {
    flex:1; background:#f4f6f8; display:flex; justify-content:center; align-items:center;
}
.login-box {
    background:#fff; padding:40px; border-radius:10px; box-shadow:0 8px 20px rgba(0,0,0,0.15); width:350px; text-align:center;
}
.login-box img {
    display:block; margin:0 auto 20px auto; width:120px; height:auto;
}
.login-box h2 { margin-bottom:20px; color:#0A1D3D; }
.login-box input, .login-box select, .login-box button {
    width:100%; padding:12px; margin:10px 0; border-radius:6px; border:1px solid #ccc; font-size:14px;
}
.login-box button {
    background:#0A1D3D; color:#fff; font-size:16px; border:none; cursor:pointer;
}
.login-box button:hover { background:#1565C0; }
.login-box .error { color:red; margin-bottom:10px; }
.login-box a { display:block; margin-top:8px; font-size:14px; color:#1565C0; text-decoration:none; }
.login-box a:hover { text-decoration:underline; }
.forgot-link { margin-bottom:10px; } /* separate spacing below password field */
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <h1>RESULT MANAGEMENT & VERIFICATION</h1>
        <p>Secure, verified, and real-time access to academic results for tertiary institutions. Login to access your dashboard or recover your password if forgotten.</p>
    </div>
    <div class="right-panel">
        <div class="login-box">
            <!-- Logo -->
            <img src="assets/images/logo.png" alt="System Logo">
            <h2>Login</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="text" name="username" placeholder="Username / Matric No" required>
                <input type="password" name="password" placeholder="Password" required>
                <a class="forgot-link" href="forgot_password.php">Forgot Password?</a>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Admin">Admin</option>
                    <option value="Lecturer">Lecturer</option>
                    <option value="Student">Student</option>
                </select>
                <button type="submit">Login</button>
            </form>
            <a href="register.php">Don't have an account? Register</a>
        </div>
    </div>
</div>
</body>
</html>