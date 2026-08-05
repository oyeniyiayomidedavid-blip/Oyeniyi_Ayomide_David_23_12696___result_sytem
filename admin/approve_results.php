<?php
session_start();
require_once("../config/db.php"); // defines $pdo

// Handle the approve action when the link is clicked
if (isset($_GET['approve'])) {
    $resultId = $_GET['approve'];

    // Step 1: get the student_id for this result, needed for the verifications table
    $lookupStmt = $pdo->prepare("SELECT student_id FROM results WHERE id = :id");
    $lookupStmt->execute([':id' => $resultId]);
    $resultRow = $lookupStmt->fetch(PDO::FETCH_ASSOC);

    if ($resultRow) {
        // Step 2: mark the result as approved
        $updateStmt = $pdo->prepare("UPDATE results SET status = 'Approved', approved = 1, rejected = 0 WHERE id = :id");
        $updateStmt->execute([':id' => $resultId]);

        // Step 3: insert into verifications so the public verify page can find it
        $insertStmt = $pdo->prepare("INSERT INTO verifications (student_id, result_id, status, created_at)
                                      VALUES (:student_id, :result_id, 'Approved', NOW())");
        $insertStmt->execute([
            ':student_id' => $resultRow['student_id'],
            ':result_id'  => $resultId,
        ]);
    }

    // Redirect back to avoid re-approving on page refresh
    header("Location: approve_results.php");
    exit;
}

// Fetch all pending results
$query = "SELECT results.*, students.full_name, courses.course_code
          FROM results
          JOIN students ON results.student_id = students.id
          JOIN courses ON results.course_id = courses.id
          WHERE results.status = 'Pending'";

$stmt = $pdo->prepare($query);
$stmt->execute();
$pendingResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>ADMIN - APPROVE RESULTS</h2>

<table border="1" width="100%" cellpadding="10">

<tr style="background-color:#f2f2f2;">
    <th>Student Name</th>
    <th>Course Code</th>
    <th>CA Score</th>
    <th>Exam Score</th>
    <th>Total</th>
    <th>Grade</th>
    <th>Action</th>
</tr>

<?php if (count($pendingResults) > 0): ?>
    <?php foreach ($pendingResults as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['full_name']) ?></td>
    <td><?= htmlspecialchars($row['course_code']) ?></td>
    <td><?= htmlspecialchars($row['ca_score']) ?></td>
    <td><?= htmlspecialchars($row['exam_score']) ?></td>
    <td><?= htmlspecialchars($row['total']) ?></td>
    <td><?= htmlspecialchars($row['grade']) ?></td>
    <td>
        <a href="approve_results.php?approve=<?= $row['id'] ?>"
           style="color:green; font-weight:bold;">
           Approve
        </a>
    </td>
</tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7">No pending results found</td></tr>
<?php endif; ?>

</table>