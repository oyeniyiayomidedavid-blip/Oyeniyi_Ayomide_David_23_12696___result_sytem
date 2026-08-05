<?php
session_start();
require_once "../config/db.php";
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = "";
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['department_name'];
    $code = $_POST['department_code'];

    $stmt = $pdo->prepare("INSERT INTO departments (department_name, department_code) VALUES (?, ?)");
    if($stmt->execute([$name, $code])){
        $message = "Department added successfully.";
    } else {
        $message = "Failed to add department.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Add Department</title></head>
<body>
<h2>Add Department</h2>
<?php if($message) echo "<p>$message</p>"; ?>
<form method="POST">
    <label>Department Name:</label>
    <input type="text" name="department_name" required><br>
    <label>Department Code:</label>
    <input type="text" name="department_code" required><br>
    <button type="submit">Add</button>
</form>
</body>
</html>