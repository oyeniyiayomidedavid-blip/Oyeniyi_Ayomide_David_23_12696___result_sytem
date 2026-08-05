<?php
session_start();
require_once "../config/db.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit; }

$id = $_GET['id'] ?? null;
if($id){
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: departments.php");
exit;