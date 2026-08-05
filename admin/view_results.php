<?php
session_start();
include("../config/db.php");

// Fetch all results with student + course details
$query = "SELECT results.*, students.fullname, students.matric_no, courses.course_code, courses.course_title
          FROM results
          JOIN students ON results.student_id = students.id
          JOIN courses ON results.course_id = courses.id
          ORDER BY results.id DESC";

$result = mysqli_query($conn, $query);
?>

<h2>ADMIN - VIEW ALL RESULTS</h2>

<table border="1" width="100%" cellpadding="10">

<tr style="background-color:#f2f2f2;">
    <th>Matric No</th>
    <th>Student Name</th>
    <th>Course Code</th>
    <th>Course Title</th>
    <th>CA Score</th>
    <th>Exam Score</th>
    <th>Total</th>
    <th>Grade</th>
    <th>Status</th>
</tr>

<?php
if(mysqli_num_rows($result) > 0){

    while($row = mysqli_fetch_assoc($result)){
?>

<tr>
    <td><?php echo $row['matric_no']; ?></td>
    <td><?php echo $row['fullname']; ?></td>
    <td><?php echo $row['course_code']; ?></td>
    <td><?php echo $row['course_title']; ?></td>
    <td><?php echo $row['ca_score']; ?></td>
    <td><?php echo $row['exam_score']; ?></td>
    <td><?php echo $row['total']; ?></td>
    <td><?php echo $row['grade']; ?></td>

    <td>
        <?php if($row['status'] == "Approved"){ ?>
            <span style="color:green; font-weight:bold;">Approved</span>
        <?php } else { ?>
            <span style="color:orange; font-weight:bold;">Pending</span>
        <?php } ?>
    </td>
</tr>

<?php
    }
}else{
    echo "<tr><td colspan='9'>No results found</td></tr>";
}
?>

</table>