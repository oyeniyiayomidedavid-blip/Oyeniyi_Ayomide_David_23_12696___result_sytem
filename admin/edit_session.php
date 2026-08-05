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

// Fetch session details
try {
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$session) {
        die("Session not found.");
    }
} catch (PDOException $e) {
    die("Error fetching session: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['session_name'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE sessions SET session_name = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $start, $end, $status, $session_id]);
        header("Location: sessions.php");
        exit;
    } catch (PDOException $e) {
        die("Error updating session: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Session</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Session</h2>
    <form method="post">
        <div class="mb-3">
            <label>Session Name</label>
            <input type="text" name="session_name" class="form-control" value="<?= htmlspecialchars($session['session_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= $session['start_date'] ?>" required>
        </div>
        <div class="mb-3">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= $session['end_date'] ?>" required>
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active" <?= $session['status']=='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $session['status']=='inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Session</button>
        <a href="sessions.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>