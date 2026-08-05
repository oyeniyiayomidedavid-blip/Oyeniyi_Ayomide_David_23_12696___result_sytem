<?php
session_start();
require_once "config/db.php";
/*
 * register.php
 * -----------------------------------------------------------------------
 * Public student self-registration page.
 *
 * Matches the REAL schema confirmed from phpMyAdmin:
 *   departments: id, department_name, department_code
 *   students   : id, full_name, matric_no, email, password, department_id,
 *                level, state_of_origin, phone_number, address, reset_token
 *
 * Students log in directly against the students table (there is no
 * separate users row for the student role), so this page inserts one row
 * straight into students - no users table involved.
 * -----------------------------------------------------------------------
 */

$errors = [];
$old = [
    'full_name'      => '',
    'matric_no'      => '',
    'email'          => '',
    'department_id'  => '',
    'level'          => '',
    'state_of_origin'=> '',
    'phone_number'   => '',
    'address'        => '',
];

// Fetch departments for the dropdown
try {
    $deptStmt = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
    $errors[] = "Could not load departments: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name       = trim($_POST['full_name'] ?? '');
    $matric_no       = trim($_POST['matric_no'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $department_id   = $_POST['department_id'] ?? '';
    $level           = trim($_POST['level'] ?? '');
    $state_of_origin = trim($_POST['state_of_origin'] ?? '');
    $phone_number    = trim($_POST['phone_number'] ?? '');
    $address         = trim($_POST['address'] ?? '');

    // keep entered values so the form doesn't clear on error
    $old = compact('full_name', 'matric_no', 'email', 'department_id', 'level',
                    'state_of_origin', 'phone_number', 'address');

    // ---- Validation ----
    if ($full_name === '') {
        $errors[] = "Full name is required.";
    }
    if ($matric_no === '') {
        $errors[] = "Matric number is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if ($department_id === '') {
        $errors[] = "Please select your department.";
    }
    if ($level === '' || !ctype_digit($level)) {
        $errors[] = "Please enter a valid numeric level (e.g. 100, 200, 300, 400).";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Password and Confirm Password do not match.";
    }

    // ---- Check matric number is not already registered ----
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM students WHERE matric_no = ?");
        $check->execute([$matric_no]);
        if ($check->fetch()) {
            $errors[] = "That matric number is already registered. Please log in instead.";
        }
    }

    // ---- Create the student record ----
    if (empty($errors)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO students
                    (full_name, matric_no, email, password, department_id, level,
                     state_of_origin, phone_number, address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $full_name, $matric_no, $email, $hashed, $department_id, $level,
                $state_of_origin ?: null, $phone_number ?: null, $address ?: null
            ]);

            // Registration succeeded - send the new student to the login page
            header("Location: login.php?registered=1");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Result Management & Verification</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f7; }
    .side-panel {
        background: #0f1f3d;
        color: #fff;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px 50px;
    }
    .side-panel h1 { font-weight: 800; font-size: 2.4rem; line-height: 1.2; }
    .side-panel p { color: #c7d0e0; margin-top: 20px; }
    .form-panel {
        background: #fff;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 50px;
    }
    .form-card { max-width: 520px; margin: 0 auto; width: 100%; }
    .cap-icon { font-size: 2.5rem; text-align: center; display: block; }
    .btn-register { background: #0f1f3d; color: #fff; font-weight: 600; padding: 10px; }
    .btn-register:hover { background: #16305e; color: #fff; }
    .form-control, .form-select { padding: 10px 12px; }
    .row-tight > div { margin-bottom: 1rem; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-5 side-panel d-none d-lg-flex">
            <h1>RESULT MANAGEMENT &amp; VERIFICATION</h1>
            <p>Create your student account to view your semester results, track your CGPA,
               and access your verified academic record online.</p>
        </div>
        <div class="col-lg-7 form-panel">
            <div class="form-card">
                <span class="cap-icon">🎓</span>
                <h2 class="text-center fw-bold mb-4" style="color:#0f1f3d;">Register</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <div class="mb-3">
                        <input type="text" name="full_name" class="form-control"
                               placeholder="Full Name"
                               value="<?= htmlspecialchars($old['full_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="matric_no" class="form-control"
                               placeholder="Matric No (e.g. BIO2024001)"
                               value="<?= htmlspecialchars($old['matric_no']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control"
                               placeholder="Email Address"
                               value="<?= htmlspecialchars($old['email']) ?>" required>
                    </div>

                    <div class="row row-tight">
                        <div class="col-md-6">
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"
                                        <?= ((string)$old['department_id'] === (string)$d['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="level" class="form-select" required>
                                <option value="">Select Level</option>
                                <?php foreach (['100','200','300','400','500'] as $lvl): ?>
                                    <option value="<?= $lvl ?>" <?= ($old['level'] === $lvl) ? 'selected' : '' ?>>
                                        <?= $lvl ?> Level
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row row-tight">
                        <div class="col-md-6">
                            <input type="text" name="state_of_origin" class="form-control"
                                   placeholder="State of Origin (optional)"
                                   value="<?= htmlspecialchars($old['state_of_origin']) ?>">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="phone_number" class="form-control"
                                   placeholder="Phone Number (optional)"
                                   value="<?= htmlspecialchars($old['phone_number']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" name="address" class="form-control"
                               placeholder="Address (optional)"
                               value="<?= htmlspecialchars($old['address']) ?>">
                    </div>

                    <div class="mb-3">
                        <input type="password" name="password" class="form-control"
                               placeholder="Password (min. 6 characters)" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Confirm Password" required>
                    </div>
                    <button type="submit" class="btn btn-register w-100 rounded">Register</button>
                    <p class="text-center mt-3">
                        Already have an account? <a href="login.php">Login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>