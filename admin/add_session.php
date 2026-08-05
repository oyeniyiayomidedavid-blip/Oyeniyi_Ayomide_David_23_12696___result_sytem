<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['session_name'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];

    try {
        $stmt = $pdo->prepare("INSERT INTO sessions (session_name, start_date, end_date) VALUES (?, ?, ?)");
        $stmt->execute([$name, $start, $end]);
        header("Location: sessions.php");
        exit;
    } catch (PDOException $e) {
        die("Error adding session: " . $e->getMessage());
    }
}
?>

<form method="post">
    <label>Session Name</label>
    <input type="text" name="session_name" required>
    <label>Start Date</label>
    <input type="date" name="start_date" required>
    <label>End Date</label>
    <input type="date" name="end_date" required>
    <button type="submit">Add Session</button>
</form>