<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: sessions.php");
    exit;
}

$session_id = $_GET['id'];

// Delete session
try {
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    header("Location: sessions.php");
    exit;
} catch (PDOException $e) {
    die("Error deleting session: " . $e->getMessage());
}
?>