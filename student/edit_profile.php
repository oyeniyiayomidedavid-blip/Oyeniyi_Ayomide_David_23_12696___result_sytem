<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$success = "";
$error = "";

// Fetch student information
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        d.department_name
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

// Update profile
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $state_of_origin = trim($_POST['state_of_origin'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($full_name === '') {
        $error = "Full name is required.";
    } else {
        $update = $pdo->prepare("
            UPDATE students 
            SET 
                full_name = ?,
                phone_number = ?,
                state_of_origin = ?,
                address = ?
            WHERE id = ?
        ");

        $updated = $update->execute([
            $full_name,
            $phone_number,
            $state_of_origin,
            $address,
            $student_id
        ]);

        if ($updated) {
            $success = "Profile updated successfully.";

            $stmt = $pdo->prepare("
                SELECT 
                    s.*,
                    d.department_name
                FROM students s
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE s.id = ?
            ");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Unable to update profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    background: #f4f6f9;
    color: #0A1D3D;
}

.sidebar {
    width: 220px;
    background: #234765;
    height: 100vh;
    color: #fff;
    position: fixed;
    top: 0;
    left: 0;
    padding: 25px 18px;
    overflow-y: auto;
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

.sidebar a:hover,
.sidebar a.active {
    background: rgba(255, 255, 255, 0.14);
}

.sidebar i {
    width: 18px;
    text-align: center;
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

.page-header {
    background: linear-gradient(90deg, #eaf4ff, #ffffff);
    border-radius: 16px;
    padding: 26px 30px;
    margin-bottom: 24px;
    box-shadow: 0 6px 20px rgba(10, 29, 61, 0.06);
}

.page-header h2 {
    margin: 0 0 8px;
    font-weight: 700;
}

.card-box {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 6px 20px rgba(10, 29, 61, 0.07);
}

.form-control {
    border-radius: 8px;
    padding: 10px 12px;
}

.btn-main {
    background: #0A1D3D;
    color: #fff;
    border: none;
    padding: 11px 22px;
    border-radius: 8px;
    font-weight: 600;
}

.btn-main:hover {
    background: #1565C0;
    color: #fff;
}

.btn-secondary-custom {
    background: #6c757d;
    color: #fff;
    border: none;
    padding: 11px 22px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
}

.btn-secondary-custom:hover {
    background: #5c636a;
    color: #fff;
}
</style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-title">
        RESULT<br>
        MANAGEMENT &<br>
        VERIFICATION
    </div>

    <a href="student_dashboard.php">
        <i class="fa-solid fa-gauge-high"></i>
        Dashboard
    </a>

    <a href="profile.php" class="active">
        <i class="fa-solid fa-user"></i>
        Profile
    </a>

    <a href="results.php">
        <i class="fa-solid fa-graduation-cap"></i>
        My Results
    </a>

    <a href="semester_results.php">
        <i class="fa-solid fa-calendar"></i>
        Semester Results
    </a>

    <a href="cgpa_calculator.php">
        <i class="fa-solid fa-calculator"></i>
        CGPA Calculator
    </a>

    <a href="verification/verify_result.php">
        <i class="fa-solid fa-circle-check"></i>
        Result Verification
    </a>

    <a href="download_transcript.php">
        <i class="fa-solid fa-file-arrow-down"></i>
        Download Transcript
    </a>

    <a href="course_registration.php">
        <i class="fa-solid fa-book"></i>
        Course Registration
    </a>

    <div class="sidebar-divider"></div>

    <a href="notifications.php">
        <i class="fa-solid fa-bell"></i>
        Notifications
    </a>

    <a href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>
</div>

<div class="content">

    <div class="page-header">
        <h2>Edit Profile</h2>
        <p>
            <strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?> |
            <strong>Matric No:</strong> <?= htmlspecialchars($student['matric_no']) ?> |
            <strong>Department:</strong> <?= htmlspecialchars($student['department_name'] ?? 'Not Set') ?> |
            <strong>Level:</strong> <?= htmlspecialchars($student['level']) ?>
        </p>
    </div>

    <div class="card-box">

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Matric Number</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($student['matric_no'] ?? '') ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($student['department_name'] ?? 'Not Set') ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Level</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($student['level'] ?? '') ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($student['phone_number'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">State of Origin</label>
                <input type="text" name="state_of_origin" class="form-control" value="<?= htmlspecialchars($student['state_of_origin'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="4"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-main">
                <i class="fa-solid fa-save"></i>
                Update Profile
            </button>

            <a href="profile.php" class="btn-secondary-custom ms-2">
                Back to Profile
            </a>

        </form>
    </div>

</div>

</body>
</html>