<?php
include("../config/db.php");

if(isset($_POST['submit'])){

    $matric = $_POST['matric'];
    $fullname = $_POST['fullname'];
    $department = $_POST['department'];
    $level = $_POST['level'];
    $password = $_POST['password'];

    $query = "INSERT INTO students
    (matric_no, fullname, department, level, password)

    VALUES
    ('$matric','$fullname','$department','$level','$password')";

    mysqli_query($conn, $query);

    echo "Student Added Successfully";
}
?>

<h2>Add Student</h2>

<form method="POST">

    Matric Number:
    <input type="text" name="matric">

    Full Name:
    <input type="text" name="fullname">

    Department:
    <input type="text" name="department">

    Level:
    <input type="text" name="level">

    Password:
    <input type="password" name="password">

    <button name="submit">Add Student</button>

</form>