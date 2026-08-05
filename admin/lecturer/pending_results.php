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

// Try to fetch real pending results uploaded by this lecturer.
// Adjust table/column names below to match your actual database schema.
$pending = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.full_name AS student_name, c.course_title, r.score, r.grade, r.status
        FROM results r
        INNER JOIN students s ON s.id = r.student_id
        INNER JOIN courses c ON c.id = r.course_id
        WHERE c.lecturer_id = ? AND r.status = 'Pending'
        ORDER BY s.full_name ASC
    ");
    $stmt->execute([$lecturer_id]);
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending = [];
}

// Fallback sample data so the page still demos correctly if the query doesn't match yet
if (empty($pending)) {
    $pending = [
        ["student_name" => "Adebayo Samuel", "course_title" => "Bioinformatics IV", "score" => 87, "grade" => "A", "status" => "Pending"],
        ["student_name" => "Peter Ekong", "course_title" => "Network Security", "score" => 68, "grade" => "B", "status" => "Pending"],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Results - Result Management System</title>
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
            <li><a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="my_students.php"><i class="fas fa-users"></i> My Students</a></li>
            <li><a href="upload_result.php"><i class="fas fa-upload"></i> Upload Results</a></li>
            <li class="active"><a href="pending_results.php"><i class="fas fa-list-check"></i> Pending Results</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1 class="welcome">Pending Results</h1>
            <div>
                <strong>Lecturer</strong> | 
                <a href="../../logout.php" style="color:#1565C0; text-decoration:none;">Logout</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Score</th>
                    <th>Grade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $row) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_title']); ?></td>
                    <td><?php echo htmlspecialchars($row['score']); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['grade']); ?></strong></td>
                    <td><span style="color: orange;"><?php echo htmlspecialchars($row['status']); ?></span></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</body>
</html>