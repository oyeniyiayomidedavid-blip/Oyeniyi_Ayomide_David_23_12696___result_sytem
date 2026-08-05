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

$message = "";
$error = "";

// ---------------------------------------------------------------------
// Handle Add / Remove actions
// Both are scoped to courses that belong to THIS lecturer only, via
// courses.lecturer_id, so a lecturer can never add/remove a student on
// a course that isn't theirs.
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $course_id = $_POST['course_id'] ?? '';
        $matric_no = trim($_POST['matric_no'] ?? '');
        $session   = trim($_POST['session'] ?? '');
        $semester  = $_POST['semester'] ?? 'First';

        if ($course_id === '' || $matric_no === '' || $session === '') {
            $error = "Please select a course and enter a matric number and session.";
        } else {
            // Confirm the course actually belongs to this lecturer
            $courseCheck = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
            $courseCheck->execute([$course_id, $lecturer_id]);

            if (!$courseCheck->fetch()) {
                $error = "You can only add students to your own courses.";
            } else {
                $studentStmt = $pdo->prepare("SELECT id FROM students WHERE matric_no = ?");
                $studentStmt->execute([$matric_no]);
                $studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC);

                if (!$studentRow) {
                    $error = "No student found with matric number '{$matric_no}'.";
                } else {
                    $insert = $pdo->prepare("
                        INSERT IGNORE INTO course_registrations (student_id, course_id, session, semester)
                        VALUES (?, ?, ?, ?)
                    ");
                    $insert->execute([$studentRow['id'], $course_id, $session, $semester]);
                    $message = "Student {$matric_no} added to the selected course.";
                }
            }
        }
    }

    if ($action === 'remove') {
        $registration_id = $_POST['registration_id'] ?? '';

        // Multi-table DELETE: only removes the registration if its course
        // belongs to this lecturer - a lecturer cannot remove a student
        // from a course that isn't theirs, even by guessing an id.
        $delete = $pdo->prepare("
            DELETE cr FROM course_registrations cr
            INNER JOIN courses c ON cr.course_id = c.id
            WHERE cr.id = ? AND c.lecturer_id = ?
        ");
        $delete->execute([$registration_id, $lecturer_id]);

        if ($delete->rowCount() > 0) {
            $message = "Student removed from the course.";
        } else {
            $error = "Could not remove that registration (not found or not one of your courses).";
        }
    }
}

// ---------------------------------------------------------------------
// Courses taught by this lecturer (used for the Add Student dropdown)
// ---------------------------------------------------------------------
$stmtMyCourses = $pdo->prepare("
    SELECT id, course_code, course_title
    FROM courses
    WHERE lecturer_id = ?
    ORDER BY course_code ASC
");
$stmtMyCourses->execute([$lecturer_id]);
$myCourses = $stmtMyCourses->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------------------
// Real student roster: every student registered (via course_registrations)
// for any course taught by this lecturer. One row per student+course
// registration, so a student taking two of the lecturer's courses shows
// up once per course - this is what makes per-course Remove possible.
// ---------------------------------------------------------------------
$students = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            cr.id AS registration_id,
            s.full_name,
            s.matric_no,
            d.department_name,
            s.level,
            c.course_code,
            c.course_title,
            cr.session,
            cr.semester
        FROM course_registrations cr
        INNER JOIN courses c ON cr.course_id = c.id
        INNER JOIN students s ON cr.student_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE c.lecturer_id = ?
        ORDER BY c.course_code ASC, s.full_name ASC
    ");
    $stmt->execute([$lecturer_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not load student roster: " . $e->getMessage();
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Result Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f6f8;
            min-height: 100vh;
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
        .card-box {
            background: white;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .card-box h4 { margin-bottom: 16px; color: #0A1D3D; }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 12px;
        }
        table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-collapse: collapse;
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
            vertical-align: middle;
        }
        .btn-main {
            background: #0A1D3D; color: #fff; border: none;
            padding: 11px 22px; border-radius: 8px; font-weight: 600; cursor: pointer;
        }
        .btn-main:hover { background: #1565C0; }
        .btn-remove {
            background: #fdecea; color: #b3261e; border: none;
            padding: 7px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px;
        }
        .btn-remove:hover { background: #f7d0cd; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-danger { background: #fdecea; color: #b3261e; }
        .add-form-grid {
            display: grid;
            grid-template-columns: 2fr 1.4fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }
        .add-form-grid label {
            display: block; font-size: 13px; font-weight: 600; color: #445; margin-bottom: 6px;
        }
        @media (max-width: 900px) {
            .add-form-grid { grid-template-columns: 1fr; }
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
            <li class="active"><a href="my_students.php"><i class="fas fa-users"></i> My Students</a></li>
            <li><a href="upload_result.php"><i class="fas fa-upload"></i> Upload Results</a></li>
            <li><a href="pending_results.php"><i class="fas fa-list-check"></i> Pending Results</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1 class="welcome">My Students</h1>
            <div>
                <strong>Lecturer</strong> |
                <a href="../../logout.php" style="color:#1565C0; text-decoration:none;">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Student to Course -->
        <div class="card-box">
            <h4>Add Student to a Course</h4>
            <form method="POST" class="add-form-grid">
                <input type="hidden" name="action" value="add">

                <div>
                    <label>Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select course</option>
                        <?php foreach ($myCourses as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>">
                                <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Matric No</label>
                    <input type="text" name="matric_no" class="form-control" placeholder="e.g. BIO2024001" required>
                </div>

                <div>
                    <label>Session</label>
                    <input type="text" name="session" class="form-control" placeholder="2025/2026" required>
                </div>

                <div>
                    <label>Semester</label>
                    <select name="semester" class="form-select">
                        <option value="First">First</option>
                        <option value="Second">Second</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn-main">Add Student</button>
                </div>
            </form>
            <?php if (empty($myCourses)): ?>
                <p style="margin-top:12px; color:#888;">
                    You have no courses assigned yet, so there is nothing to add a student to.
                </p>
            <?php endif; ?>
        </div>

        <!-- Roster -->
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Matric No</th>
                    <th>Department</th>
                    <th>Level</th>
                    <th>Course</th>
                    <th>Session / Semester</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $row) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['matric_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['department_name'] ?? 'Not Set'); ?></td>
                        <td><?php echo htmlspecialchars($row['level']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['session'] . ' · ' . $row['semester']); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this student from this course?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="registration_id" value="<?php echo (int)$row['registration_id']; ?>">
                                <button type="submit" class="btn-remove">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#888;">
                            No students registered for your courses yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>