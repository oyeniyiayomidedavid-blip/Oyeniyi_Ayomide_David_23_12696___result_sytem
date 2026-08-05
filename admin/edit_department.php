<?php
session_start();
require_once "../config/db.php";
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit; }

$id = $_GET['id'] ?? null;
if(!$id){ die("Department ID missing."); }

$stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$id]);
$dep = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$dep){ die("Department not found."); }

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['department_name'];
    $code = $_POST['department_code'];
    $stmt = $pdo->prepare("UPDATE departments SET department_name = ?, department_code = ? WHERE id = ?");
    if($stmt->execute([$name, $code, $id])){ header("Location: departments.php"); exit; }
}
?>
<form method="POST">
    <input type="text" name="department_name" value="<?= htmlspecialchars($dep['department_name']) ?>" required>
    <input type="text" name="department_code" value="<?= htmlspecialchars($dep['department_code']) ?>" required>
    <button type="submit">Update</button>
</form>