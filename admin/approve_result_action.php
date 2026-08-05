<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if(!$id || !in_array($action, ['approve','reject'])){
    die("Invalid request.");
}

$status = ($action === 'approve') ? 1 : 2; // 1 = approved, 2 = rejected

$stmt = $pdo->prepare("UPDATE results SET approved = ? WHERE id = ?");
$stmt->execute([$status, $id]);

header("Location: result_approval.php");
exit;