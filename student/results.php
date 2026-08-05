<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch student info with department
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: ../login.php");
    exit;
}

$currentLevel = $student['level'];

// Fetch latest session for current level first semester results
$stmt2 = $pdo->prepare("
    SELECT r.session 
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
      AND r.semester = 'First'
      AND c.level = ?
    ORDER BY r.id DESC 
    LIMIT 1
");
$stmt2->execute([$student_id, $currentLevel]);
$latestResult = $stmt2->fetch(PDO::FETCH_ASSOC);
$sessionValue = $latestResult['session'] ?? 'Not Set';

// Fetch distinct results for current level first semester only
$stmt3 = $pdo->prepare("
    SELECT r.*, c.course_code, c.course_title, c.unit_load
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
      AND r.semester = 'First'
      AND c.level = ?
    GROUP BY r.course_id
    ORDER BY c.course_code ASC
");
$stmt3->execute([$student_id, $currentLevel]);
$results = $stmt3->fetchAll(PDO::FETCH_ASSOC);

function gradeToPoint($grade) {
    switch(strtoupper($grade)) {
        case 'A': return 5;
        case 'B+': return 4.5;
        case 'B': return 4;
        case 'C+': return 3.5;
        case 'C': return 3;
        case 'D': return 2;
        default: return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Results</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; }
.sidebar { width: 180px; background-color: #1f3d5a; min-height: 100vh; color: #fff; position: fixed; padding-top: 20px; overflow-y: auto; }
.sidebar h3 { font-size: 1rem; text-align: left; margin: 0 0 20px 15px; }
.sidebar ul { list-style: none; padding: 0; margin: 0; }
.sidebar ul li { padding: 8px 15px; margin-bottom: 5px; }
.sidebar ul li a { color: #fff; text-decoration: none; display: flex; align-items: center; font-weight: 500; border-radius: 5px; font-size: 0.95rem; }
.sidebar ul li a i { margin-right: 8px; font-size: 1rem; }
.sidebar ul li a:hover, .sidebar ul li a.active { background-color: #3e5c7c; }
.content { margin-left: 180px; padding: 25px; }
.card { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
.card h4 { margin-bottom: 15px; }
.table thead { background-color: #343a40; color: #fff; }
.print-btn { margin-top: 15px; }
</style>
<script>
function printResults() {
    const content = document.getElementById('resultsTable').outerHTML;
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
    <h3>Student Portal</h3>
    <ul>
        <li><a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="results.php" class="active"><i class="fas fa-graduation-cap"></i> My Results</a></li>
        <li><a href="verification/verify_result.php"><i class="fas fa-check-circle"></i> Verify Result</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="content">
    <div class="card">
        <h4>Student Info</h4>
        <p><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
        <p><strong>Matric No:</strong> <?= htmlspecialchars($student['matric_no']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($student['department_name'] ?? 'Not Set') ?></p>
        <p><strong>Level:</strong> <?= htmlspecialchars($student['level']) ?></p>
        <p><strong>Session:</strong> <?= htmlspecialchars($sessionValue) ?></p>
    </div>

    <div class="card">
        <h4>My Results - First Semester</h4>
        <table class="table table-bordered" id="resultsTable">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Credit Unit</th>
                    <th>CA Score</th>
                    <th>Exam Score</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Semester</th>
                    <th>Session</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $total_units = 0;
            $total_points = 0;

            if (count($results) > 0):
                foreach($results as $res):
                    $credit = $res['unit_load'];
                    $grade_point = gradeToPoint($res['grade']);
                    $total_units += $credit;
                    $total_points += $credit * $grade_point;
            ?>
            <tr>
                <td><?= htmlspecialchars($res['course_code']) ?></td>
                <td><?= htmlspecialchars($res['course_title']) ?></td>
                <td><?= htmlspecialchars($credit) ?></td>
                <td><?= htmlspecialchars($res['ca_score']) ?></td>
                <td><?= htmlspecialchars($res['exam_score']) ?></td>
                <td><?= htmlspecialchars($res['total']) ?></td>
                <td><?= htmlspecialchars($res['grade']) ?></td>
                <td><?= htmlspecialchars($res['semester']) ?></td>
                <td><?= htmlspecialchars($res['session'] ?? $sessionValue) ?></td>
                <td><?= htmlspecialchars($res['status']) ?></td>
            </tr>
            <?php 
                endforeach;
            else:
            ?>
            <tr>
                <td colspan="10" class="text-center">No First Semester result found for your current level.</td>
            </tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total Units</th>
                    <th><?= $total_units ?></th>
                    <th colspan="6"></th>
                    <th>GPA: <?= $total_units ? number_format($total_points / $total_units, 2) : '0.00' ?></th>
                </tr>
            </tfoot>
        </table>
        <button class="btn btn-primary print-btn" onclick="printResults()">Print Results</button>
    </div>
</div>

</body>
</html>