<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$message = "";
$error = "";

// Fetch student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: ../login.php");
    exit;
}

// Create course registration table automatically if it does not exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS course_registrations (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        session VARCHAR(50) NOT NULL,
        semester VARCHAR(50) NOT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_course_registration (student_id, course_id, session, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Get latest academic session from results
$stmtSession = $pdo->prepare("SELECT session FROM results WHERE student_id = ? ORDER BY id DESC LIMIT 1");
$stmtSession->execute([$student_id]);
$latestSession = $stmtSession->fetch(PDO::FETCH_ASSOC);
$sessionValue = $latestSession['session'] ?? "2024/2025";

// Semester filter
$selectedSemester = $_GET['semester'] ?? 'First';

// Student level
$studentLevel = $student['level'] ?? 100;

// Register selected courses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedCourses = $_POST['courses'] ?? [];
    $semester = $_POST['semester'] ?? 'First';
    $session = $_POST['session'] ?? $sessionValue;

    if (empty($selectedCourses)) {
        $error = "Please select at least one course to register.";
    } else {
        $insert = $pdo->prepare("
            INSERT IGNORE INTO course_registrations 
            (student_id, course_id, session, semester) 
            VALUES (?, ?, ?, ?)
        ");

        foreach ($selectedCourses as $course_id) {
            $insert->execute([$student_id, $course_id, $session, $semester]);
        }

        $message = "Course registration completed successfully.";
        $selectedSemester = $semester;
        $sessionValue = $session;
    }
}

// Fetch available courses for the student's level and selected semester
$stmtCourses = $pdo->prepare("
    SELECT * FROM courses
    WHERE level = ? AND semester = ?
    ORDER BY course_code ASC
");
$stmtCourses->execute([$studentLevel, $selectedSemester]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Fetch already registered courses
$stmtRegistered = $pdo->prepare("
    SELECT cr.*, c.course_code, c.course_title, c.unit_load, c.credit_unit
    FROM course_registrations cr
    INNER JOIN courses c ON cr.course_id = c.id
    WHERE cr.student_id = ? AND cr.session = ? AND cr.semester = ?
    ORDER BY c.course_code ASC
");
$stmtRegistered->execute([$student_id, $sessionValue, $selectedSemester]);
$registeredCourses = $stmtRegistered->fetchAll(PDO::FETCH_ASSOC);

$registeredIds = [];
foreach ($registeredCourses as $registered) {
    $registeredIds[] = $registered['course_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Course Registration</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f9;
    margin: 0;
    color: #0A1D3D;
}

/* Sidebar Scrollable */
.sidebar {
    width: 220px;
    background: #234765;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    padding: 25px 18px;
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

.sidebar-title {
    font-size: 21px;
    font-weight: 700;
    line-height: 1.35;
    margin-bottom: 35px;
}

.sidebar a {
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 12px;
    margin-bottom: 10px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
}

.sidebar a i {
    width: 18px;
    text-align: center;
    font-size: 16px;
}

.sidebar a:hover,
.sidebar a.active {
    background: rgba(255, 255, 255, 0.14);
}

.sidebar-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.14);
    margin: 22px 0;
}

.content {
    margin-left: 220px;
    padding: 30px;
}

/* Card and table styles retained */
.page-header {
    background: linear-gradient(90deg, #eaf4ff, #ffffff);
    border-radius: 16px;
    padding: 26px 30px;
    margin-bottom: 24px;
    box-shadow: 0 6px 20px rgba(10, 29, 61, 0.06);
}

.page-header h2 { margin: 0 0 8px; font-weight: 700; }
.page-header p { margin: 4px 0; color: #5d6b82; }

.card-box { background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow:0 6px 20px rgba(10,29,61,0.07);}
.card-box h4 { font-weight:700; margin-bottom:18px;}
.form-control, .form-select { border-radius:8px; padding:10px 12px;}
.table thead th { background:#0A1D3D; color:#fff; padding:13px; white-space:nowrap;}
.table td { padding:13px; vertical-align:middle;}
.btn-main { background:#0A1D3D; color:#fff; border:none; padding:11px 22px; border-radius:8px; font-weight:600;}
.btn-main:hover { background:#1565C0; color:#fff;}
.badge-registered { background:#dcfce7; color:#166534; padding:7px 12px; border-radius:20px; font-size:12px; font-weight:600;}
.summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:15px;}
.summary-item { background:#f7f9fc; border-radius:12px; padding:15px;}
.summary-item span { display:block; font-size:13px; color:#667085; margin-bottom:5px;}
.summary-item strong { color:#0A1D3D;}

@media(max-width:900px){
.sidebar { width:200px;}
.content { margin-left:200px; padding:20px;}
.summary-grid { grid-template-columns:1fr;}
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-title">
        <h3>STUDENT</h3>
    </div>

    <a href="student_dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="semester_results.php"><i class="fa-solid fa-calendar-days"></i> Semester Results</a>
    <a href="cgpa_calculator.php"><i class="fa-solid fa-calculator"></i> CGPA Calculator</a>
    <a href="course_registration.php" class="active"><i class="fa-solid fa-book"></i> Course Registration</a>

    <div class="sidebar-divider"></div>

    <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="content">

    <div class="page-header">
        <h2>Course Registration</h2>
        <p>
            <strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?> |
            <strong>Matric No:</strong> <?= htmlspecialchars($student['matric_no']) ?> |
            <strong>Level:</strong> <?= htmlspecialchars($student['level']) ?>
        </p>
        <p>Select and register your courses for the current semester.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card-box">
        <h4>Registration Details</h4>

        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Academic Session</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($sessionValue) ?>" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select" onchange="this.form.submit()">
                    <option value="First" <?= $selectedSemester === 'First' ? 'selected' : '' ?>>First Semester</option>
                    <option value="Second" <?= $selectedSemester === 'Second' ? 'selected' : '' ?>>Second Semester</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Level</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($studentLevel) ?>" readonly>
            </div>
        </form>
    </div>

    <form method="POST">
        <input type="hidden" name="session" value="<?= htmlspecialchars($sessionValue) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($selectedSemester) ?>">

        <div class="card-box">
            <h4>Available Courses</h4>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Unit Load</th>
                            <th>Credit Unit</th>
                            <th>Semester</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($courses) > 0): ?>
                        <?php foreach ($courses as $course): ?>
                            <?php $alreadyRegistered = in_array($course['id'], $registeredIds); ?>
                            <tr>
                                <td>
                                    <?php if ($alreadyRegistered): ?>
                                        <input type="checkbox" checked disabled>
                                    <?php else: ?>
                                        <input type="checkbox" name="courses[]" value="<?= htmlspecialchars($course['id']) ?>">
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($course['course_code']) ?></td>
                                <td><?= htmlspecialchars($course['course_title']) ?></td>
                                <td><?= htmlspecialchars($course['unit_load']) ?></td>
                                <td><?= htmlspecialchars($course['credit_unit']) ?></td>
                                <td><?= htmlspecialchars($course['semester']) ?></td>

                                <td>
                                    <?php if ($alreadyRegistered): ?>
                                        <span class="badge-registered">Registered</span>
                                    <?php else: ?>
                                        <span class="text-muted">Not Registered</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No courses found for this level and semester.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-main mt-3">
                Register Selected Courses
            </button>
        </div>
    </form>

    <div class="card-box">
        <h4>Registered Courses</h4>

        <div class="summary-grid mb-3">
            <div class="summary-item">
                <span>Total Registered Courses</span>
                <strong><?= count($registeredCourses) ?></strong>
            </div>

            <div class="summary-item">
                <span>Session</span>
                <strong><?= htmlspecialchars($sessionValue) ?></strong>
            </div>

            <div class="summary-item">
                <span>Semester</span>
                <strong><?= htmlspecialchars($selectedSemester) ?></strong>
            </div>

            <div class="summary-item">
                <span>Level</span>
                <strong><?= htmlspecialchars($studentLevel) ?></strong>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Unit Load</th>
                        <th>Credit Unit</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($registeredCourses) > 0): ?>
                    <?php foreach ($registeredCourses as $registered): ?>
                        <tr>
                            <td><?= htmlspecialchars($registered['course_code']) ?></td>
                            <td><?= htmlspecialchars($registered['course_title']) ?></td>
                            <td><?= htmlspecialchars($registered['unit_load']) ?></td>
                            <td><?= htmlspecialchars($registered['credit_unit']) ?></td>
                            <td><?= htmlspecialchars($registered['registered_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            You have not registered any course for this semester yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
