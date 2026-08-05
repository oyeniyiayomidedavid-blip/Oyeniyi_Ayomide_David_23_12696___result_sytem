<?php
include("../config/db.php");

if(isset($_POST['submit'])){

    $code = $_POST['course_code'];
    $title = $_POST['course_title'];
    $unit = $_POST['unit'];

    $query = "INSERT INTO courses (course_code, course_title, unit_load)
              VALUES ('$code','$title','$unit')";

    mysqli_query($conn, $query);

    echo "Course Added Successfully";
}
?>

<h2>Add Computer Science Course</h2>

<form method="POST">

    <label>Course Code</label>
    <select name="course_code" required>
        <option value="">-- Select Course Code --</option>

        <option value="CSC101">CSC101</option>
        <option value="CSC102">CSC102</option>
        <option value="CSC103">CSC103</option>
        <option value="CSC104">CSC104</option>
        <option value="CSC105">CSC105</option>
        <option value="CSC201">CSC201</option>
        <option value="CSC202">CSC202</option>
        <option value="CSC203">CSC203</option>
        <option value="CSC204">CSC204</option>
        <option value="CSC205">CSC205</option>
        <option value="CSC301">CSC301</option>
        <option value="CSC302">CSC302</option>
        <option value="CSC303">CSC303</option>
        <option value="CSC304">CSC304</option>
        <option value="CSC305">CSC305</option>

    </select>
    <br><br>

    <label>Course Title</label>
    <select name="course_title" required>
        <option value="">-- Select Course Title --</option>

        <option value="Introduction to Computer Science">Introduction to Computer Science</option>
        <option value="Programming Fundamentals">Programming Fundamentals</option>
        <option value="Computer Architecture">Computer Architecture</option>
        <option value="Data Structures">Data Structures</option>
        <option value="Discrete Mathematics">Discrete Mathematics</option>
        <option value="Object Oriented Programming">Object Oriented Programming</option>
        <option value="Database Management Systems">Database Management Systems</option>
        <option value="Operating Systems">Operating Systems</option>
        <option value="Computer Networks">Computer Networks</option>
        <option value="Software Engineering">Software Engineering</option>
        <option value="Artificial Intelligence">Artificial Intelligence</option>
        <option value="Web Development">Web Development</option>
        <option value="Mobile App Development">Mobile App Development</option>
        <option value="Cyber Security Basics">Cyber Security Basics</option>
        <option value="Final Year Project">Final Year Project</option>

    </select>
    <br><br>

    <label>Unit Load</label>
    <select name="unit" required>
        <option value="">-- Select Unit --</option>

        <option value="1">1 Unit</option>
        <option value="2">2 Units</option>
        <option value="3">3 Units</option>
        <option value="4">4 Units</option>
        <option value="6">6 Units</option>

    </select>

    <br><br>

    <button type="submit" name="submit">Add Course</button>

</form>