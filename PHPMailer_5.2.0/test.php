<?php

require 'class.phpmailer.php';

$mail = new PHPMailer;

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

//$mail->isSMTP();                                      // Set mailer to use SMTP
//$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
//$mail->SMTPAuth = true;                               // Enable SMTP authentication
//$mail->Username = '';                 // SMTP username
//$mail->Password = '';                           // SMTP password
//$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
//$mail->Port = 587;                                    // TCP port to connect to

$mail->IsMAIL();
$mail->From = 'from@example.com';
$mail->FromName = 'Mailer';
$mail->addAddress('gotocva@gmail.com');     // Add a recipient
//$mail->addAddress('ellen@example.com');               // Name is optional
$mail->addReplyTo('gotocva@gmail.com', 'Information');
$mail->addCC('gotocva@gmail.com');
$mail->addBCC('gotocva@gmail.com');

//$mail->addAttachment('');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = 'Here is the subject';
$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
$d = $mail->send();
var_dump($d);
if(!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message has been sent';
}
?>