<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$course_id = $_GET['id'] ?? null;
if ($course_id) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id=?");
    $stmt->execute([$course_id]);
}

header("Location: courses.php");
exit;