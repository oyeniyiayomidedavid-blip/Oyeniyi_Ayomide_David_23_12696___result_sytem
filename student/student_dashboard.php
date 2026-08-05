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

if (!$student) {
    header("Location: ../login.php");
    exit;
}

$currentLevel = $student['level'] ?? 100;

// Fetch all unique student results for correct CGPA calculation
// This prevents duplicated results from affecting CGPA
$stmtAllResults = $pdo->prepare("
    SELECT 
        r.*,
        c.course_code,
        c.course_title,
        c.unit_load,
        COALESCE(NULLIF(c.credit_unit, 0), c.unit_load) AS credit_unit,
        c.level
    FROM results r
    INNER JOIN (
        SELECT 
            MAX(r2.id) AS latest_result_id
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

// Find the student's most recently uploaded result, regardless of the
// student's currently stored level. Previously this query required
// c.level to match the student's own level column, which silently
// returned nothing whenever a student's results included a course level
// that didn't match their stored level (e.g. level not yet updated after
// promotion, or a carryover/retake course) - even though results clearly
// existed. We now simply take the most recently inserted result row for
// this student and use ITS course's level for display purposes.
$stmtLatest = $pdo->prepare("
    SELECT 
        r.session,
        r.semester,
        c.level
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY r.id DESC
    LIMIT 1
");
$stmtLatest->execute([$student_id]);
$latestSemesterResult = $stmtLatest->fetch(PDO::FETCH_ASSOC);

$recentSession = $latestSemesterResult['session'] ?? null;
$recentSemester = $latestSemesterResult['semester'] ?? null;
$recentLevel = $latestSemesterResult['level'] ?? $currentLevel;

// Fetch every result belonging to that most recent session/semester,
// regardless of course level (a single semester can legitimately contain
// a carryover course from a different level). This is what shows in the
// Recent Results table.
if ($recentSession && $recentSemester) {
    $stmtRecentResults = $pdo->prepare("
        SELECT 
            r.*,
            c.course_code,
            c.course_title,
            c.unit_load,
            COALESCE(NULLIF(c.credit_unit, 0), c.unit_load) AS credit_unit,
            c.level
        FROM results r
        INNER JOIN (
            SELECT 
                MAX(r2.id) AS latest_result_id
            FROM results r2
            INNER JOIN courses c2 ON r2.course_id = c2.id
            WHERE r2.student_id = ?
              AND TRIM(r2.session) = ?
              AND TRIM(r2.semester) = ?
            GROUP BY r2.course_id
        ) latest_results ON latest_results.latest_result_id = r.id
        INNER JOIN courses c ON r.course_id = c.id
        ORDER BY c.course_code ASC
    ");
    $stmtRecentResults->execute([
        $student_id,
        trim($recentSession),
        trim($recentSemester)
    ]);
    $results = $stmtRecentResults->fetchAll(PDO::FETCH_ASSOC);
} else {
    $results = [];
}

// Function to convert grade to grade point
function gradeToPoint($grade) {
    switch(strtoupper(trim($grade))) {
        case 'A':
            return 5;
        case 'B+':
            return 4.5;
        case 'B':
            return 4;
        case 'C+':
            return 3.5;
        case 'C':
            return 3;
        case 'D':
            return 2;
        case 'E':
            return 1;
        default:
            return 0;
    }
}

// Calculate total units and CGPA from all unique results
$total_units = 0;
$total_points = 0;

foreach($allResults as $res){
    $credit = (int)($res['credit_unit'] ?? $res['unit_load']);
    $grade_point = gradeToPoint($res['grade']);

    $total_units += $credit;
    $total_points += $credit * $grade_point;
}

$cgpa = $total_units ? number_format($total_points / $total_units, 2) : '0.00';
$totalCourses = count($allResults);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; margin:0; background:#f4f6f9; }
.sidebar {
    width:240px; position:fixed; top:0; bottom:0; left:0; background:#1f3d5a; color:#fff; 
    padding:20px; overflow-y:auto; /* make sidebar scrollable */
}
.sidebar h3 { font-size:1.3rem; margin-bottom:20px; }
.sidebar a { color:#fff; text-decoration:none; display:block; padding:10px 15px; margin-bottom:5px; border-radius:5px; }
.sidebar a:hover { background:#162d5c; }
.content { margin-left:260px; padding:30px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.header .user-info { text-align:right; }
.cards { display:flex; gap:20px; margin-bottom:30px; flex-wrap:wrap; }
.card-box { flex:1; min-width:200px; background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1); text-align:center; }
.card-box h4 { margin-bottom:10px; }
.table-container { background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1); }
.table thead { background:#343a40; color:#fff; }
.chart-container { background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1); margin-top:20px; }
.notices { background:#fff; border-radius:10px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1); margin-top:20px; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <h3>RESULT MANAGEMENT & VERIFICATION</h3>
    <a href="student_dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a href="profile.php"><i class="fa fa-user"></i> Profile</a>
    <a href="semester_results.php"><i class="fa fa-calendar"></i> Semester Results</a>
    <a href="cgpa_calculator.php"><i class="fa fa-calculator"></i> CGPA Calculator</a>
    <a href="../verify_result.php" target="_blank"><i class="fa fa-check-circle"></i> Result Verification</a>
    <a href="course_registration.php"><i class="fa fa-book"></i> Course Registration</a>
    <hr style="border-color:#3e5c7c;">
    <a href="#"><i class="fa fa-bell"></i> Notifications</a>
    <a href="#"><i class="fa fa-question-circle"></i> Help & FAQ</a>
    <a href="#"><i class="fa fa-envelope"></i> Contact Support</a>
    <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <div class="header">
        <div>
            <h2>Welcome back, <?= htmlspecialchars($student['full_name']) ?>!</h2>
            <p>Matric No: <?= htmlspecialchars($student['matric_no']) ?> · Department: <?= htmlspecialchars($student['department_name'] ?? 'Not Set') ?> · Level: <?= htmlspecialchars($student['level']) ?></p>
            <p>Stay updated with your academic performance.</p>
        </div>
        <div>
            <img src="../assets/images/graduation_icon.png" alt="Graduation" width="80">
        </div>
    </div>

    <div class="cards">
        <div class="card-box">
            <h4>Current CGPA</h4>
            <h2><?= $cgpa ?></h2>
            <p>Good Standing</p>
        </div>
        <div class="card-box">
            <h4>Total Courses</h4>
            <h2><?= $totalCourses ?></h2>
            <p>Completed</p>
        </div>
        <div class="card-box">
            <h4>Total Units Earned</h4>
            <h2><?= $total_units ?></h2>
            <p>As at <?= date('Y/m') ?></p>
        </div>
        <div class="card-box">
            <h4>Academic Status</h4>
            <h2>Active</h2>
            <p>Continue the good work!</p>
        </div>
    </div>

    <div class="table-container">
        <h3>
            Recent Results
            <?php if ($recentSession && $recentSemester): ?>
                <small style="font-size:16px; color:#6c757d;">
                    <?= htmlspecialchars($recentLevel) ?> Level · <?= htmlspecialchars($recentSemester) ?> Semester · <?= htmlspecialchars($recentSession) ?>
                </small>
            <?php endif; ?>
        </h3>

        <table class="table table-bordered">
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
                <?php if (count($results) > 0): ?>
                    <?php foreach($results as $res): 
                        $credit = (int)($res['credit_unit'] ?? $res['unit_load']);
                        $grade_point = gradeToPoint($res['grade']);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($res['course_code']) ?></td>
                        <td><?= htmlspecialchars($res['course_title']) ?></td>
                        <td><?= $credit ?></td>
                        <td><?= htmlspecialchars($res['grade']) ?></td>
                        <td><?= $grade_point ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No recent result found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button class="btn btn-primary mt-2" onclick="window.print()">Print Results</button>
    </div>

    <div class="chart-container">
        <h3>CGPA Progress</h3>
        <canvas id="cgpaChart"></canvas>
    </div>

    <div class="notices">
        <h3>Important Notices</h3>
        <ul>
            <li>2025/2026 First Semester Result Released December 30, 2025</li>
            <li>2025/2026 Second Semester session ongoing</li>
            <li>Course Registration Second Semester 2025/2026 ongoing - May 20, 2026</li>
        </ul>
    </div>
</div>

<script>
const ctx = document.getElementById('cgpaChart').getContext('2d');
const cgpaChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['100 Level','200 Level','300 Level','400 Level'],
        datasets: [{
            label: 'CGPA',
            data: [3.12, 3.45, 3.68, 4.00],
            borderColor: 'blue',
            backgroundColor: 'rgba(0,0,255,0.1)',
            tension: 0.4
        }]
    },
    options: {
        scales: { y: { beginAtZero: true, max:5 } }
    }
});
</script>

</body>
</html>