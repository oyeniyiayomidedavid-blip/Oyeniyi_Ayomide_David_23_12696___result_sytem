<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: users.php");
    exit;
}

// Prevent an admin from deleting their own currently logged-in account
if (isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id'] === $id) {
    header("Location: users.php?error=" . urlencode("You cannot delete your own account while logged in."));
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: users.php?msg=" . urlencode("User deleted successfully."));
    exit;
} catch (PDOException $e) {
    header("Location: users.php?error=" . urlencode("Error deleting user: " . $e->getMessage()));
    exit;
}