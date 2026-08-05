<?php
session_start();
require_once "../../config/db.php";

// Check if logged in as Lecturer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lecturer') {
    header("Location: ../../login.php");
    exit;
}

$lecturer_id = $_SESSION['lecturer_id'] ?? 0;

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: upload_result.php");
    exit;
}

$matric_no = trim($_POST['student_id'] ?? '');
$course_id = trim($_POST['course_id'] ?? '');
$ca_score = $_POST['ca_score'] ?? null;
$exam_score = $_POST['exam_score'] ?? null;

// Basic validation
if ($matric_no === '' || $course_id === '' || $ca_score === null || $exam_score === null) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: upload_result.php");
    exit;
}

$ca_score = (float) $ca_score;
$exam_score = (float) $exam_score;

if ($ca_score < 0 || $ca_score > 100 || $exam_score < 0 || $exam_score > 100) {
    $_SESSION['error'] = "Scores must be between 0 and 100.";
    header("Location: upload_result.php");
    exit;
}

try {
    // 1. Look up the student by matric number
    $stmt = $pdo->prepare("SELECT id FROM students WHERE matric_no = ?");
    $stmt->execute([$matric_no]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $_SESSION['error'] = "No student found with matric number: " . htmlspecialchars($matric_no);
        header("Location: upload_result.php");
        exit;
    }
    $student_id = $student['id'];

    // 2. Confirm the course exists AND belongs to this lecturer
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
    $stmt->execute([$course_id, $lecturer_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        $_SESSION['error'] = "Invalid course, or this course is not assigned to you.";
        header("Location: upload_result.php");
        exit;
    }

    // 3. Calculate total, grade, and grade point
    $total = $ca_score + $exam_score;
    $score = $total; // 'score' column mirrors total in this schema

    if ($total >= 70) {
        $grade = 'A'; $grade_point = 5.0;
    } elseif ($total >= 60) {
        $grade = 'B'; $grade_point = 4.0;
    } elseif ($total >= 50) {
        $grade = 'C'; $grade_point = 3.0;
    } elseif ($total >= 45) {
        $grade = 'D'; $grade_point = 2.0;
    } else {
        $grade = 'F'; $grade_point = 0.0;
    }

    // 4. Prevent duplicate result for the same student/course combination
    $stmt = $pdo->prepare("SELECT id FROM results WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "A result already exists for this student in this course.";
        header("Location: upload_result.php");
        exit;
    }

    // 5. Generate a simple unique verification code
    $verification_code = strtoupper(bin2hex(random_bytes(4)));

    // 6. Insert the result
    $stmt = $pdo->prepare("
        INSERT INTO results
            (student_id, course_id, ca_score, exam_score, total, score, grade, grade_point, status, semester, verification_code)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'First', ?)
    ");
    $stmt->execute([
        $student_id, $course_id, $ca_score, $exam_score, $total, $score, $grade, $grade_point, $verification_code
    ]);

    $_SESSION['success'] = "Result uploaded successfully. Grade: {$grade} (awaiting approval).";
    header("Location: upload_result.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: could not save result.";
    header("Location: upload_result.php");
    exit;
}