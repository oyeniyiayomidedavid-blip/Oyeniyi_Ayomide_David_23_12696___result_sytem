<?php
session_start();
require_once "../config/db.php";

// Check student login
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Include FPDF library
require_once('C:/xampp/htdocs/result_system/fpdf/fpdf.php');

// Fetch student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all student results
$stmt2 = $pdo->prepare("
    SELECT r.*, c.course_code, c.course_title, c.unit_load 
    FROM results r
    INNER JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY r.session ASC, c.level ASC, r.semester ASC
");
$stmt2->execute([$student_id]);
$results = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Function to convert grade to grade point
function gradeToPoint($grade) {
    switch(strtoupper($grade)) {
        case 'A': return 5;
        case 'B+': return 4.5;
        case 'B': return 4;
        case 'C': return 3;
        case 'D': return 2;
        default: return 0;
    }
}

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);

// Header
$pdf->Cell(0,10,'Transcript',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,'Name: ' . $student['full_name'],0,1);
$pdf->Cell(0,8,'Matric No: ' . $student['matric_no'],0,1);
$pdf->Cell(0,8,'Department: ' . $student['department_id'],0,1);
$pdf->Cell(0,8,'Level: ' . $student['level'],0,1);
$pdf->Ln(5);

// Table header
$pdf->SetFont('Arial','B',12);
$pdf->Cell(35,8,'Course Code',1);
$pdf->Cell(70,8,'Course Title',1);
$pdf->Cell(20,8,'Unit',1);
$pdf->Cell(20,8,'Grade',1);
$pdf->Cell(20,8,'Point',1);
$pdf->Ln();

// Table body
$pdf->SetFont('Arial','',12);
$total_units = 0;
$total_points = 0;

foreach($results as $res){
    $grade_point = gradeToPoint($res['grade']);
    $total_units += $res['unit_load'];
    $total_points += $res['unit_load'] * $grade_point;

    $pdf->Cell(35,8,$res['course_code'],1);
    $pdf->Cell(70,8,$res['course_title'],1);
    $pdf->Cell(20,8,$res['unit_load'],1);
    $pdf->Cell(20,8,$res['grade'],1);
    $pdf->Cell(20,8,number_format($grade_point,2),1);
    $pdf->Ln();
}

// GPA
$gpa = $total_units ? number_format($total_points / $total_units, 2) : 0;
$pdf->Ln(5);
$pdf->Cell(0,8,'Total Units: ' . $total_units,0,1);
$pdf->Cell(0,8,'GPA: ' . $gpa,0,1);

// Output PDF
$pdf->Output('D','Transcript_' . $student['matric_no'] . '.pdf');
exit;
?>