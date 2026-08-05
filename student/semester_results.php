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

// Determine filters
$sessionValue = trim($_GET['session'] ?? null);
$semesterValue = trim($_GET['semester'] ?? null);
$levelValue = trim($_GET['level'] ?? null);

// Fetch latest semester result if no filter
$stmtLatest = $pdo->prepare("
    SELECT r.session, r.semester, c.level
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY c.level DESC, r.id DESC
    LIMIT 1
");
$stmtLatest->execute([$student_id]);
$latest = $stmtLatest->fetch(PDO::FETCH_ASSOC);

$sessionValue = $sessionValue ?? $latest['session'] ?? '2024/2025';
$semesterValue = $semesterValue ?? $latest['semester'] ?? 'First';
$levelValue = $levelValue ?? $latest['level'] ?? 200;

// Fetch **recent semester results** without duplicates
$stmtResults = $pdo->prepare("
    SELECT r.*, c.course_code, c.course_title, COALESCE(NULLIF(c.credit_unit,0),c.unit_load) AS credit_unit, c.unit_load
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
      AND TRIM(r.session) = ?
      AND TRIM(r.semester) = ?
      AND c.level = ?
    GROUP BY r.course_id
    ORDER BY c.course_code ASC
");
$stmtResults->execute([$student_id, $sessionValue, $semesterValue, $levelValue]);
$results = $stmtResults->fetchAll(PDO::FETCH_ASSOC);

// Fetch all results up to current semester for overall CGPA
$stmtAllResults = $pdo->prepare("
    SELECT r.*, COALESCE(NULLIF(c.credit_unit,0),c.unit_load) AS credit_unit
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
      AND (
            c.level < ?
            OR (c.level = ? AND (TRIM(r.session) <= ? AND TRIM(r.semester) <= ?))
          )
");
$stmtAllResults->execute([$student_id, $levelValue, $levelValue, $sessionValue, $semesterValue]);
$allResults = $stmtAllResults->fetchAll(PDO::FETCH_ASSOC);

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
<title>Semester Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family:'Segoe UI', sans-serif; background:#f4f6f9; margin:0; color:#0A1D3D; }
.sidebar { width:220px; background:#234765; height:100vh; color:#fff; position:fixed; top:0; left:0; padding:25px; overflow-y:auto; }
.sidebar-title { font-size:21px; font-weight:700; margin-bottom:35px; }
.sidebar a { color:#fff; text-decoration:none; display:flex; align-items:center; gap:10px; padding:11px 12px; margin-bottom:10px; border-radius:8px; font-size:15px; font-weight:500; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.14); }
.sidebar-divider { height:1px; background: rgba(255,255,255,0.14); margin:22px 0; }
.content { margin-left:220px; padding:30px; }
.page-header { background:linear-gradient(90deg,#eaf4ff,#fff); border-radius:16px; padding:26px 30px; margin-bottom:24px; box-shadow:0 6px 20px rgba(10,29,61,0.06); }
.page-header h2 { margin:0 0 8px; font-weight:700; }
.page-header p { margin:4px 0; color:#5d6b82; }
.card-box { background:#fff; border-radius:16px; padding:24px; margin-bottom:24px; box-shadow:0 6px 20px rgba(10,29,61,0.07); }
.card-box h4 { font-weight:700; margin-bottom:18px; }
.table thead th { background:#0A1D3D; color:#fff; padding:13px; white-space:nowrap; }
.table td { padding:13px; vertical-align:middle; }
.summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-bottom:18px; }
.summary-item { background:#f7f9fc; border-radius:12px; padding:15px; }
.summary-item span { display:block; font-size:13px; color:#667085; margin-bottom:5px; }
.summary-item strong { color:#0A1D3D; font-size:18px; }
.btn-main { background:#0A1D3D; color:#fff; border:none; padding:11px 22px; border-radius:8px; font-weight:600; }
.btn-main:hover { background:#1565C0; color:#fff; }
.status-badge { padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.status-active { background:#dcfce7; color:#166534; }
.status-pending { background:#fff7d6; color:#a16207; }
.status-rejected { background:#fee2e2; color:#991b1b; }
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
    <div class="sidebar-title">STUDENT</div>
    <a href="student_dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="semester_results.php" class="active"><i class="fa-solid fa-calendar"></i> Semester Results</a>
    <a href="cgpa_calculator.php"><i class="fa-solid fa-calculator"></i> CGPA Calculator</a>
    <a href="course_registration.php"><i class="fa-solid fa-book"></i> Course Registration</a>
    <div class="sidebar-divider"></div>
    <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="content">
    <div class="page-header">
        <h2>Semester Results</h2>
        <p>
            <strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?> |
            <strong>Matric No:</strong> <?= htmlspecialchars($student['matric_no']) ?> |
            <strong>Department:</strong> <?= htmlspecialchars($student['department_name'] ?? 'Not Set') ?> |
            <strong>Level:</strong> <?= htmlspecialchars($student['level']) ?>
        </p>
        <p>View your results by academic session, semester, and level.</p>
    </div>

    <div class="card-box filter-card">
        <h4>Filter Results</h4>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Academic Session</label>
                <input type="text" name="session" class="form-control" value="<?= htmlspecialchars($sessionValue) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select">
                    <option value="First" <?= $semesterValue==='First'?'selected':'' ?>>First Semester</option>
                    <option value="Second" <?= $semesterValue==='Second'?'selected':'' ?>>Second Semester</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Level</label>
                <input type="number" name="level" class="form-control" value="<?= htmlspecialchars($levelValue) ?>">
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-main">Filter Results</button>
            </div>
        </form>
    </div>

    <div class="card-box">
        <h4>Recent Semester Results (<?= htmlspecialchars($levelValue) ?> Level - <?= htmlspecialchars($semesterValue) ?>)</h4>
        <div class="table-responsive">
            <table class="table table-bordered" id="resultsTable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Unit Load</th>
                        <th>Credit Unit</th>
                        <th>CA Score</th>
                        <th>Exam Score</th>
                        <th>Total</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(count($results)>0): ?>
                    <?php foreach($results as $row): ?>
                        <?php
                        $status = strtolower($row['status'] ?? 'active');
                        $statusClass='status-active';
                        if($status==='pending') $statusClass='status-pending';
                        elseif($status==='rejected') $statusClass='status-rejected';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['course_code']) ?></td>
                            <td><?= htmlspecialchars($row['course_title']) ?></td>
                            <td><?= htmlspecialchars($row['unit_load']) ?></td>
                            <td><?= htmlspecialchars($row['credit_unit']) ?></td>
                            <td><?= htmlspecialchars($row['ca_score']) ?></td>
                            <td><?= htmlspecialchars($row['exam_score']) ?></td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                            <td><?= htmlspecialchars($row['score']) ?></td>
                            <td><?= htmlspecialchars($row['grade']) ?></td>
                            <td><?= htmlspecialchars($row['grade_point'] ?? gradeToPoint($row['grade'])) ?></td>
                            <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status'] ?? 'Active') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center text-muted">No results found for this semester.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <button class="btn btn-main mt-3" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Semester Result</button>
        </div>
    </div>

    <div class="card-box">
        <h4>Overall CGPA</h4>
        <p><strong>Calculated from 100L First Semester → <?= htmlspecialchars($levelValue) ?>L <?= htmlspecialchars($semesterValue) ?> Semester:</strong> <?= $cgpa ?></p>
        <p><strong>GPA for this Semester:</strong> <?= $recent_gpa ?></p>
    </div>

</div>
</body>
</html>