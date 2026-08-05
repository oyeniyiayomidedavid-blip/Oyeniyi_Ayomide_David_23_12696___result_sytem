<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check course ID
if (!isset($_GET['id'])) {
    echo "No course ID provided.";
    exit;
}

$course_id = $_GET['id'];

// Fetch course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) {
    echo "Course not found.";
    exit;
}

// Fetch departments for dropdown
$dept_stmt = $pdo->prepare("SELECT * FROM departments ORDER BY department_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = $_POST['course_code'] ?? '';
    $course_title = $_POST['course_title'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $level = $_POST['level'] ?? '';

    // Update course using department_id
    $update = $pdo->prepare("UPDATE courses SET course_code=?, course_title=?, department_id=?, level=? WHERE id=?");
    $update->execute([$course_code, $course_title, $department_id, $level, $course_id]);

    header("Location: courses.php?msg=Course updated successfully");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Course - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Course</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Course Code</label>
            <input type="text" class="form-control" name="course_code" value="<?= htmlspecialchars($course['course_code']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Course Title</label>
            <input type="text" class="form-control" name="course_title" value="<?= htmlspecialchars($course['course_title']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select class="form-control" name="department_id" required>
                <option value="">Select Department</option>
                <?php foreach($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $course['department_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Level</label>
            <input type="number" class="form-control" name="level" value="<?= htmlspecialchars($course['level']) ?>" required>
        </div>
        <button type="submit" class="btn btn-warning">Update Course</button>
        <a href="courses.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>