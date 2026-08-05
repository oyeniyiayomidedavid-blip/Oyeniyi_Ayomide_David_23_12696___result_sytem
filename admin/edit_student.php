<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "No student ID provided.";
    exit;
}

$student_id = $_GET['id'];

// Fetch student
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) { echo "Student not found."; exit; }

// Fetch departments
$dept_stmt = $pdo->prepare("SELECT * FROM departments ORDER BY department_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $matric_no = $_POST['matric_no'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $level = $_POST['level'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? null;
    $dob = $_POST['dob'] ?? null;

    $update = $pdo->prepare("UPDATE students SET full_name=?, matric_no=?, department_id=?, level=?, email=?, phone=?, dob=? WHERE id=?");
    $update->execute([$full_name, $matric_no, $department_id, $level, $email, $phone, $dob, $student_id]);

    header("Location: students.php?msg=Student updated successfully");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Student - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Student</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($student['full_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Matric Number</label>
            <input type="text" class="form-control" name="matric_no" value="<?= htmlspecialchars($student['matric_no']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select class="form-control" name="department_id" required>
                <option value="">Select Department</option>
                <?php foreach($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $student['department_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Level</label>
            <input type="number" class="form-control" name="level" value="<?= htmlspecialchars($student['level']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($student['email']) ?>">
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($student['phone']) ?>">
        </div>
        <div class="mb-3">
            <label>Date of Birth</label>
            <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($student['dob']) ?>">
        </div>
        <button type="submit" class="btn btn-warning">Update Student</button>
        <a href="students.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>