<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch student info with department name
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name 
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine current level
$currentLevel = $student['level'] ?? 100;

// Fetch all unique results for CGPA calculation (100L → current level)
$stmtAllResults = $pdo->prepare("
    SELECT r.*, c.course_code, c.course_title, c.unit_load, COALESCE(c.credit_unit, c.unit_load) AS credit_unit, c.level
    FROM results r
    INNER JOIN (
        SELECT MAX(r2.id) AS latest_result_id
        FROM results r2
        INNER JOIN courses c2 ON r2.course_id = c2.id
        WHERE r2.student_id = ?
        GROUP BY r2.course_id, r2.session, r2.semester
    ) latest_results ON latest_results.latest_result_id = r.id
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY c.level ASC, r.session ASC, r.semester ASC, c.course_code ASC
");
$stmtAllResults->execute([$student_id, $student_id]);
$allResults = $stmtAllResults->fetchAll(PDO::FETCH_ASSOC);

// Find latest/current semester result (e.g., 200L First Semester)
$stmtLatest = $pdo->prepare("
    SELECT r.session, r.semester, c.level
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
      AND c.level = ?
    ORDER BY r.id DESC
    LIMIT 1
");
$stmtLatest->execute([$student_id, $currentLevel]);
$latestSemesterResult = $stmtLatest->fetch(PDO::FETCH_ASSOC);

$recentSession = $latestSemesterResult['session'] ?? null;
$recentSemester = $latestSemesterResult['semester'] ?? null;
$recentLevel = $latestSemesterResult['level'] ?? $currentLevel;

// Fetch recent/current level semester results only
if ($recentSession && $recentSemester) {
    $stmtRecentResults = $pdo->prepare("
        SELECT r.*, c.course_code, c.course_title, c.unit_load, COALESCE(c.credit_unit, c.unit_load) AS credit_unit, c.level
        FROM results r
        INNER JOIN (
            SELECT MAX(r2.id) AS latest_result_id
            FROM results r2
            INNER JOIN courses c2 ON r2.course_id = c2.id
            WHERE r2.student_id = ?
              AND c2.level = ?
              AND TRIM(r2.session) = ?
              AND TRIM(r2.semester) = ?
            GROUP BY r2.course_id
        ) latest_results ON latest_results.latest_result_id = r.id
        INNER JOIN courses c ON r.course_id = c.id
        ORDER BY c.course_code ASC
    ");
    $stmtRecentResults->execute([
        $student_id,
        $recentLevel,
        trim($recentSession),
        trim($recentSemester)
    ]);
    $results = $stmtRecentResults->fetchAll(PDO::FETCH_ASSOC);
} else {
    $results = [];
}

// Grade to point conversion
function gradeToPoint($grade) {
    switch(strtoupper(trim($grade))) {
        case 'A': return 5;
        case 'B+': return 4.5;
        case 'B': return 4;
        case 'C+': return 3.5;
        case 'C': return 3;
        case 'D': return 2;
        case 'E': return 1;
        default: return 0;
    }
}

// Calculate CGPA (all levels)
$total_units = 0;
$total_points = 0;
foreach($allResults as $res){
    $credit = (int)($res['credit_unit'] ?? $res['unit_load']);
    $grade_point = gradeToPoint($res['grade']);
    $total_units += $credit;
    $total_points += $credit * $grade_point;
}
$cgpa = $total_units ? number_format($total_points / $total_units, 2) : '0.00';

// Calculate Recent Semester GPA
$recent_units = 0;
$recent_points = 0;
foreach($results as $res){
    $credit = (int)($res['credit_unit'] ?? $res['unit_load']);
    $point = gradeToPoint($res['grade']);
    $recent_units += $credit;
    $recent_points += $credit * $point;
}
$recent_gpa = $recent_units ? number_format($recent_points / $recent_units, 2) : '0.00';
$totalCourses = count($allResults);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CGPA Calculator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin:0; }

.sidebar {
    width: 200px;
    background-color: #1f3d5a;
    color: #fff;
    height: 100vh;
    padding-top: 20px;
    position: fixed;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.08);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.35);
    border-radius: 10px;
}

.sidebar h3 {
    text-align:center;
    margin-bottom: 25px;
    font-size:1.2rem;
    line-height: 1.35;
    padding: 0 12px;
}

.sidebar a {
    color:#fff;
    text-decoration:none;
    padding:10px 20px;
    display:flex;
    align-items:center;
    gap:10px;
    border-radius:5px;
    margin-bottom:5px;
}

.sidebar a i {
    width: 18px;
    text-align: center;
    font-size: 15px;
}

.sidebar a:hover,
.sidebar a.active {
    background-color:#3e5c7c;
}

.content { margin-left:210px; padding:30px; }
.card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:20px; }
.table-container { background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
.table thead { background:#343a40; color:#fff; }
.print-btn { margin-top:15px; }
</style>
<script>
function printResults() {
    const content = document.getElementById('cgpaTable').outerHTML;
    const myWindow = window.open('', '', 'width=800,height=600');
    myWindow.document.write('<html><head><title>Print Results</title></head><body>');
    myWindow.document.write(content);
    myWindow.document.write('</body></html>');
    myWindow.document.close();
    myWindow.print();
}
</script>
</head>
<body>

<div class="sidebar">
    <h3>STUDENT</h3>

    <a href="student_dashboard.php">
        <i class="fa-solid fa-gauge-high"></i>
        Dashboard
    </a>

    <a href="profile.php">
        <i class="fa-solid fa-user"></i>
        Profile
    </a>

    <a href="semester_results.php">
        <i class="fa-solid fa-calendar-days"></i>
        Semester Results
    </a>

    <a href="cgpa_calculator.php" class="active">
        <i class="fa-solid fa-calculator"></i>
        CGPA Calculator
    </a>

    <a href="course_registration.php">
        <i class="fa-solid fa-book"></i>
        Course Registration
    </a>

    <hr style="border-color:#3e5c7c; width:85%;">

    <a href="#">
        <i class="fa-solid fa-bell"></i>
        Notifications
    </a>

    <a href="#">
        <i class="fa-solid fa-circle-question"></i>
        Help & FAQ
    </a>

    <a href="#">
        <i class="fa-solid fa-envelope"></i>
        Contact Support
    </a>

    <a href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>
</div>

<div class="content">
    <div class="card">
        <h4>Student Info</h4>
        <p><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
        <p><strong>Matric No:</strong> <?= htmlspecialchars($student['matric_no']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($student['department_name'] ?? 'Not Set') ?></p>
        <p><strong>Level:</strong> <?= htmlspecialchars($student['level']) ?></p>
        <p><strong>Session:</strong> <?= htmlspecialchars($student['session'] ?? '2025/2026'); ?></p>
    </div>

    <div class="card">
        <h4>CGPA Overview</h4>
        <p><strong>Overall CGPA:</strong> <?= $cgpa ?></p>
        <p><strong>Recent Semester GPA (<?= htmlspecialchars($recentLevel) ?> Level - <?= htmlspecialchars($recentSemester) ?> Semester):</strong> <?= $recent_gpa ?></p>
        <p><strong>Total Courses Completed:</strong> <?= $totalCourses ?></p>
    </div>

    <div class="table-container">
        <h4>Recent Semester Results</h4>
        <table class="table table-bordered" id="cgpaTable">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Unit</th>
                    <th>Grade</th>
                    <th>Point</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($results) > 0): ?>
                    <?php foreach($results as $res): 
                        $credit = (int)($res['credit_unit'] ?? $res['unit_load']);
                        $point = gradeToPoint($res['grade']);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($res['course_code']) ?></td>
                        <td><?= htmlspecialchars($res['course_title']) ?></td>
                        <td><?= $credit ?></td>
                        <td><?= htmlspecialchars($res['grade']) ?></td>
                        <td><?= $point ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No recent result found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button class="btn btn-primary print-btn" onclick="printResults()">Print Results</button>
    </div>
</div>

</body>
</html>