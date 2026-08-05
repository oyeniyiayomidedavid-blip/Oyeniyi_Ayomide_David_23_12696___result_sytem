<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // If deletion confirmed, delete the record
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: students.php?msg=Student deleted successfully");
            exit;
        } catch (PDOException $e) {
            echo "Error deleting student: " . $e->getMessage();
        }
    } else {
        // JS confirmation popup
        echo '<script>
            if (confirm("Are you sure you want to delete this student?")) {
                window.location.href = "delete_student.php?id='.$id.'&confirm=yes";
            } else {
                window.location.href = "students.php";
            }
        </script>';
    }
} else {
    echo "No student ID provided.";
}
?>