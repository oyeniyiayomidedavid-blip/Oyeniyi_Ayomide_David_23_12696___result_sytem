<?php
session_start();
include("../config/db.php");

// Check if lecturer is logged in
if(!isset($_SESSION['lecturer_id'])){
    echo "<script>
        alert('Please login first');
        window.location.href='../index.php';
    </script>";
    exit();
}

$lecturer_id = $_SESSION['lecturer_id'];

// Fetch lecturer uploaded results
$query = "SELECT results.*, students.fullname, students.matric_no,
                 courses.course_code, courses.course_title
          FROM results
          JOIN students ON results.student_id = students.id
          JOIN courses ON results.course_id = courses.id
          WHERE results.lecturer_id = '$lecturer_id'
          ORDER BY results.id DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Uploaded Results</title>

    <style>
        body{
            font-family: Arial;
            background: #f4f4f4;
        }

        .container{
            width: 90%;
            margin: 40px auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px gray;
        }

        table{
            width: 100%;
            border-collapse: collapse;
        }

        th, td{
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th{
            background: #007bff;
            color: white;
        }

        .pending{
            color: orange;
            font-weight: bold;
        }

        .approved{
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>My Uploaded Results</h2>

    <table>

        <tr>
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
                    <span class="approved">Approved</span>
                <?php } else { ?>
                    <span class="pending">Pending</span>
                <?php } ?>
            </td>
        </tr>

        <?php
            }
        } else {
            echo "<tr><td colspan='9'>No results uploaded yet</td></tr>";
        }
        ?>

    </table>

</div>

</body>
</html>