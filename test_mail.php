<?php
$to = "davidblaq797@gmail.com"; // a valid email you can check
$subject = "Test Email from XAMPP";
$message = "This is a test email to check your XAMPP SMTP setup.";
$headers = "From: your-email@gmail.com"; // same email you set in sendmail.ini

if(mail($to, $subject, $message, $headers)){
    echo "Email sent successfully!";
}else{
    echo "Email sending failed!";
}
?>