<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f6f9;
    margin: 0;
    color: #0A1D3D;
}

/* Sidebar scrollable */
.sidebar {
    width: 220px;
    background: #234765;
    color: #fff;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    padding: 25px 18px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
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

.card-profile {
    background-color: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.card-profile img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin-bottom: 15px;
    object-fit: cover;
}

.card-profile h4 {
    margin-bottom: 15px;
    font-weight: 600;
}

.card-profile p {
    font-size: 16px;
    margin-bottom: 8px;
}

.edit-btn {
    margin-top: 15px;
}

@media(max-width:900px){
    .sidebar {
        width: 200px;
    }
    .content {
        margin-left: 200px;
        padding: 20px;
    }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-title">
        STUDENT PORTAL
    </div>

    <a href="student_dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="semester_results.php"><i class="fa-solid fa-calendar-days"></i> Semester Results</a>
    <a href="cgpa_calculator.php"><i class="fa-solid fa-calculator"></i> CGPA Calculator</a>
    <a href="course_registration.php"><i class="fa-solid fa-book"></i> Course Registration</a>
    <div class="sidebar-divider"></div>
    <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
    <a href="#"><i class="fa-solid fa-question-circle"></i> Help & FAQ</a>
    <a href="#"><i class="fa-solid fa-envelope"></i> Contact Support</a>
    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="content">
    <div class="card-profile">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Profile Icon">
        <h4><?= htmlspecialchars($student['full_name']) ?></h4>
        <p><strong>Matric No:</strong> <?= htmlspecialchars($student['matric_no']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($student['department_name'] ?? 'Cyber Security') ?></p>
        <p><strong>Level:</strong> <?= htmlspecialchars($student['level']) ?></p>
        <p><strong>State of Origin:</strong> <?= htmlspecialchars($student['state_of_origin'] ?? 'Not Set') ?></p>
        <p><strong>Phone Number:</strong> <?= htmlspecialchars($student['phone_number'] ?? 'Not Set') ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($student['address'] ?? 'Not Set') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($student['email'] ?? 'Not Set') ?></p>
        <a href="edit_profile.php" class="btn btn-primary edit-btn">Edit Profile</a>
    </div>
</div>

</body>
</html>