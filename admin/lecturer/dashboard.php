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

// Stats and recent results pulled from real data, with safe fallbacks
$course_count = 0;
$student_count = 0;
$results_count = 0;
$pending_count = 0;
$recent = [];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE lecturer_id = ?");
    $stmt->execute([$lecturer_id]);
    $course_count = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT r.student_id)
        FROM results r
        INNER JOIN courses c ON c.id = r.course_id
        WHERE c.lecturer_id = ?
    ");
    $stmt->execute([$lecturer_id]);
    $student_count = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM results r
        INNER JOIN courses c ON c.id = r.course_id
        WHERE c.lecturer_id = ?
    ");
    $stmt->execute([$lecturer_id]);
    $results_count = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM results r
        INNER JOIN courses c ON c.id = r.course_id
        WHERE c.lecturer_id = ? AND r.status = 'Pending'
    ");
    $stmt->execute([$lecturer_id]);
    $pending_count = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT s.full_name AS student_name, c.course_title, r.grade, r.status
        FROM results r
        INNER JOIN students s ON s.id = r.student_id
        INNER JOIN courses c ON c.id = r.course_id
        WHERE c.lecturer_id = ?
        ORDER BY r.id DESC
        LIMIT 5
    ");
    $stmt->execute([$lecturer_id]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Leaves zeros/empty array above if query fails
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard - Result Management System</title>
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
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card h3 { font-size: 32px; margin: 10px 0; color: #0A1D3D; }
        .card p { color: #666; }
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
        .btn {
            padding: 8px 16px;
            background: #1565C0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>LECTURER DASHBOARD</h2>
        <ul>
            <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
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
            <h1 class="welcome">Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <div>
                <strong>Lecturer</strong> | 
                <a href="../../logout.php" style="color:#1565C0; text-decoration:none;">Logout</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="cards">
            <div class="card">
                <h3><?php echo $course_count; ?></h3>
                <p>My Courses</p>
            </div>
            <div class="card">
                <h3><?php echo $student_count; ?></h3>
                <p>Students Taught</p>
            </div>
            <div class="card">
                <h3><?php echo $results_count; ?></h3>
                <p>Results Uploaded</p>
            </div>
            <div class="card">
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Approval</p>
            </div>
        </div>

        <!-- Recent Results -->
        <h2 style="margin-bottom:20px; color:#0A1D3D;">Recent Results Uploaded</h2>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Grade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($recent)) {
                    echo "<tr><td colspan='4' style='text-align:center; color:#888;'>No results uploaded yet.</td></tr>";
                } else {
                    foreach ($recent as $row) {
                        $status_color = ($row['status'] === 'Approved') ? 'green' : 'orange';
                        echo "<tr>
                            <td>" . htmlspecialchars($row['student_name']) . "</td>
                            <td>" . htmlspecialchars($row['course_title']) . "</td>
                            <td><strong>" . htmlspecialchars($row['grade']) . "</strong></td>
                            <td><span style='color: {$status_color};'>" . htmlspecialchars($row['status']) . "</span></td>
                        </tr>";
                    }
                }
                ?>
            </tbody>
        </table>

        <br><br>
        <a href="upload_result.php" class="btn">Upload New Results</a>
    </div>

</body>
</html>