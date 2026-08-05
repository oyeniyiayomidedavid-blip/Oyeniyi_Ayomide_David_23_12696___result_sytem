<?php
session_start();
require_once "../../config/db.php";

// Check if logged in as Lecturer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lecturer') {
    header("Location: ../../login.php");
    exit;
}

// Get lecturer info
$lecturer_id = $_SESSION['lecturer_id'] ?? 0;

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'lecturer'");
$stmt->execute([$lecturer_id]);
$lecturer = $stmt->fetch(PDO::FETCH_ASSOC);
$full_name = $lecturer['full_name'] ?? 'Lecturer';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['full_name'] ?? '');
    $new_password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($new_name === '') {
        $error = "Full name cannot be empty.";
    } elseif ($new_password !== '' && $new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            if ($new_password !== '') {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_name, $hashed, $lecturer_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$new_name, $lecturer_id]);
            }
            $full_name = $new_name;
            $message = "Settings updated successfully.";
        } catch (PDOException $e) {
            $error = "Could not update settings. Please check your database connection.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Result Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f6f8;
            height: 100vh;
            display: flex;
        }
        .sidebar {
            width: 260px;
            background: #0A1D3D;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100%;
        }
        .sidebar h2 {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar ul {
            list-style: none;
            margin-top: 20px;
        }
        .sidebar ul li {
            padding: 15px 25px;
            cursor: pointer;
            transition: 0.3s;
        }
        .sidebar ul li:hover, .sidebar ul li.active {
            background: #1565C0;
        }
        .sidebar ul li i { margin-right: 10px; }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .welcome {
            font-size: 28px;
            color: #0A1D3D;
        }
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 480px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #0A1D3D;
            font-weight: 500;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }
        input[disabled] {
            background: #f4f6f8;
            color: #888;
        }
        .btn {
            padding: 10px 20px;
            background: #1565C0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            max-width: 480px;
        }
        .alert-success {
            background: #e6f4ea;
            color: #1e7e34;
            border: 1px solid #b8e2c8;
        }
        .alert-error {
            background: #fdecea;
            color: #c62828;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>LECTURER<br>DASHBOARD</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="my_students.php"><i class="fas fa-users"></i> My Students</a></li>
            <li><a href="upload_result.php"><i class="fas fa-upload"></i> Upload Results</a></li>
            <li><a href="pending_results.php"><i class="fas fa-list-check"></i> Pending Results</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li class="active"><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1 class="welcome">Settings</h1>
            <div>
                <strong>Lecturer</strong> | 
                <a href="../../logout.php" style="color:#1565C0; text-decoration:none;">Logout</a>
            </div>
        </div>

        <?php if ($message) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>
        <?php if ($error) { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                </div>
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>

</body>
</html>