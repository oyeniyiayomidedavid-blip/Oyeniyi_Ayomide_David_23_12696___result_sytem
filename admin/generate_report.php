<?php
session_start();
require_once "../config/db.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

try {
    // Fetch all results
    $stmt = $pdo->query("
        SELECT r.id, s.full_name, s.matric_no, c.course_code, c.course_title, r.grade, r.session, r.semester
        FROM results r
        INNER JOIN students s ON r.student_id = s.id
        INNER JOIN courses c ON r.course_id = c.id
        ORDER BY r.id DESC
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching results: " . $e->getMessage());
}

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=student_results.csv');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['ID', 'Student Name', 'Matric No', 'Course Code', 'Course Title', 'Grade', 'Session', 'Semester']);

// Output data
foreach ($results as $row) {
    fputcsv($output, [
        $row['id'],
        $row['full_name'],
        $row['matric_no'],
        $row['course_code'],
        $row['course_title'],
        $row['grade'],
        $row['session'],
        $row['semester']
    ]);
}

fclose($output);
exit;