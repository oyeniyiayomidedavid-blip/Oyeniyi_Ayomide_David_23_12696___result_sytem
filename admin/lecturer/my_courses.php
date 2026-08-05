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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Result Management System</title>
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
        table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        th {
            background: #0A1D3D;
            color: white;
            padding: 15px;
            text-align: left;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>LECTURER<br>DASHBOARD</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="active"><a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="my_students.php"><i class="fas fa-users"></i> My Students</a></li>
            <li><a href="upload_result.php"><i class="fas fa-upload"></i> Upload Results</a></li>
            <li><a href="pending_results.php"><i class="fas fa-list-check"></i> Pending Results</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1 class="welcome">My Courses</h1>
            <div>
                <strong>Lecturer</strong> | 
                <a href="../../logout.php" style="color:#1565C0; text-decoration:none;">Logout</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Unit Load</th>
                    <th>Level</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT course_code, course_title, unit_load, level FROM courses WHERE lecturer_id = ? ORDER BY course_code ASC");
                $stmt->execute([$lecturer_id]);
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($courses)) {
                    echo "<tr><td colspan='4' style='text-align:center; color:#888;'>No courses assigned yet.</td></tr>";
                } else {
                    foreach ($courses as $row) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['course_code']) . "</td>
                            <td>" . htmlspecialchars($row['course_title']) . "</td>
                            <td>" . htmlspecialchars($row['unit_load']) . "</td>
                            <td>" . htmlspecialchars($row['level']) . "</td>
                        </tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>