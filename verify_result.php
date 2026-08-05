<?php
// verify_result.php
// Public-facing result verification page — no login required.
// Matches actual schema: students.id (PK), verification_codes, verifications, verification_log

require_once __DIR__ . '/config/db.php'; // adjust filename if different

$resultRows  = null;
$studentInfo = null;
$errorMsg    = '';
$matricNo    = '';
$verifyCode  = '';
$groupedResults = [];
$overallTotalUnits = 0;
$overallTotalPoints = 0;
$overallCgpa = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricNo   = trim($_POST['matric_no'] ?? '');
    $verifyCode = trim($_POST['verification_code'] ?? '');

    if ($matricNo === '' || $verifyCode === '') {
        $errorMsg = 'Please enter both the matric number and verification code.';
    } else {
        // Step 1: confirm the code belongs to the student with this matric number
        $codeSql = "SELECT vc.id AS code_id, vc.code, vc.student_id, vc.status AS code_status,
                           s.id AS student_pk, s.full_name, s.matric_no, s.level, d.department_name
                    FROM verification_codes vc
                    JOIN students s    ON vc.student_id = s.id
                    JOIN departments d ON s.department_id = d.id
                    WHERE s.matric_no = :matric_no
                      AND vc.code = :code";

        $codeStmt = $pdo->prepare($codeSql);
        $codeStmt->execute([
            ':matric_no' => $matricNo,
            ':code'      => $verifyCode,
        ]);
        $codeMatch = $codeStmt->fetch(PDO::FETCH_ASSOC);

        if (!$codeMatch) {
            $errorMsg = 'No matching record found. Please check the matric number and verification code and try again.';
        } else {
            $studentInfo = $codeMatch;

            // Step 2: pull this student's results that have been approved for verification
            $resultSql = "SELECT c.course_code, c.course_title, c.credit_unit,
                                 r.id AS result_id, r.grade, r.grade_point, r.session, r.semester
                          FROM verifications v
                          JOIN results r ON v.result_id = r.id
                          JOIN courses c ON r.course_id = c.id
                          WHERE v.student_id = :student_id
                            AND v.status = 'Approved'
                          ORDER BY r.session ASC, r.semester ASC";

            $resultStmt = $pdo->prepare($resultSql);
            $resultStmt->execute([':student_id' => $codeMatch['student_id']]);
            $resultRows = $resultStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($resultRows) === 0) {
                $errorMsg = 'This code is valid, but no approved results were found for this student yet.';
                $studentInfo = null;
            } else {
                // Step 3: log the verification attempt
                $logSql = "INSERT INTO verification_log
                            (verification_code, verifier_name, verifier_organisation, date_verified, result_id)
                           VALUES (:code, :name, :org, NOW(), :result_id)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    ':code'      => $verifyCode,
                    ':name'      => $_POST['verifier_name'] ?? 'Not provided',
                    ':org'       => $_POST['verifier_organisation'] ?? 'Not provided',
                    ':result_id' => $resultRows[0]['result_id'],
                ]);

                // Step 4: group results by session + semester, and calculate GPA per group + overall CGPA
                foreach ($resultRows as $row) {
                    $key = $row['session'] . ' - ' . $row['semester'];

                    if (!isset($groupedResults[$key])) {
                        $groupedResults[$key] = [
                            'courses'      => [],
                            'total_units'  => 0,
                            'total_points' => 0,
                        ];
                    }

                    $unit  = (float) $row['credit_unit'];
                    $point = (float) $row['grade_point'];

                    $groupedResults[$key]['courses'][] = $row;
                    $groupedResults[$key]['total_units']  += $unit;
                    $groupedResults[$key]['total_points'] += ($unit * $point);

                    $overallTotalUnits  += $unit;
                    $overallTotalPoints += ($unit * $point);
                }

                if ($overallTotalUnits > 0) {
                    $overallCgpa = $overallTotalPoints / $overallTotalUnits;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">

    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body p-4">
            <h3 class="mb-4">Enter Verification Details</h3>

            <?php if ($errorMsg): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Matric Number</label>
                    <input type="text" name="matric_no" class="form-control"
                           value="<?= htmlspecialchars($matricNo) ?>"
                           placeholder="e.g. BIO2024001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Verification Code</label>
                    <input type="text" name="verification_code" class="form-control"
                           value="<?= htmlspecialchars($verifyCode) ?>"
                           placeholder="e.g. 6EA26CA7" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Your Name <span class="text-muted">(optional)</span></label>
                    <input type="text" name="verifier_name" class="form-control" placeholder="Verifier's name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Organisation <span class="text-muted">(optional)</span></label>
                    <input type="text" name="verifier_organisation" class="form-control" placeholder="Company or institution">
                </div>
                <button type="submit" class="btn btn-success">Verify</button>
            </form>
        </div>
    </div>

    <?php if (!empty($groupedResults) && $studentInfo): ?>
    <div class="card shadow-sm mx-auto mt-4" style="max-width: 800px;">
        <div class="card-body p-4">
            <h4 class="mb-1">Verified Result</h4>
            <p class="text-success mb-4">&#10003; This result has been verified as authentic.</p>

            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($studentInfo['full_name']) ?></p>
            <p class="mb-1"><strong>Matric No:</strong> <?= htmlspecialchars($studentInfo['matric_no']) ?></p>
            <p class="mb-1"><strong>Department:</strong> <?= htmlspecialchars($studentInfo['department_name']) ?></p>
            <p class="mb-4"><strong>Level:</strong> <?= htmlspecialchars($studentInfo['level']) ?></p>

            <?php foreach ($groupedResults as $sessionSemester => $group): ?>
                <h5 class="mt-4 mb-2"><?= htmlspecialchars($sessionSemester) ?></h5>
                <table class="table table-bordered mb-2">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Unit</th>
                            <th>Grade</th>
                            <th>Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group['courses'] as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['course_code']) ?></td>
                            <td><?= htmlspecialchars($row['course_title']) ?></td>
                            <td><?= htmlspecialchars($row['credit_unit']) ?></td>
                            <td><?= htmlspecialchars($row['grade']) ?></td>
                            <td><?= htmlspecialchars($row['grade_point']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
                    $gpa = $group['total_units'] > 0 ? ($group['total_points'] / $group['total_units']) : 0;
                ?>
                <p class="text-end text-muted mb-4">
                    Units: <?= htmlspecialchars($group['total_units']) ?> |
                    GPA: <?= htmlspecialchars(number_format($gpa, 2)) ?>
                </p>
            <?php endforeach; ?>

            <hr class="mt-4">
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <strong>Total Units Completed:</strong> <?= htmlspecialchars($overallTotalUnits) ?>
                </div>
                <div class="fs-5">
                    <strong>Overall CGPA:</strong>
                    <span class="text-success"><?= htmlspecialchars(number_format($overallCgpa, 2)) ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>