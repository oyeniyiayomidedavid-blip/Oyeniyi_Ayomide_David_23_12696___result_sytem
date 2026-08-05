<?php
session_start();
require_once "../../config/db.php";   // ← Correct path from lecturer folder

// Check if logged in as Lecturer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lecturer') {
    header("Location: ../../login.php");
    exit;
}

$lecturer_id = $_SESSION['lecturer_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Result - Lecturer</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #0A1D3D;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        button {
            grid-column: span 2;
            padding: 14px;
            background: #1565C0;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #0A1D3D;
        }
        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #1565C0;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <a href="dashboard.php" class="back">← Back to Dashboard</a>

    <div class="container">
        <h2>Upload Student Result</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?= $_SESSION['success']; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?= $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="upload_result_action.php">
            <div>
                <label>Student ID / Matric No</label>
                <input type="text" name="student_id" required placeholder="e.g. BIO2024001">
            </div>
            <div>
                <label>Course ID</label>
                <input type="number" name="course_id" required>
            </div>
            <div>
                <label>CA Score</label>
                <input type="number" name="ca_score" min="0" max="100" step="0.01" required>
            </div>
            <div>
                <label>Exam Score</label>
                <input type="number" name="exam_score" min="0" max="100" step="0.01" required>
            </div>
            
            <button type="submit">Upload Result</button>
        </form>
    </div>

</body>
</html>