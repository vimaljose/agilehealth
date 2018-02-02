<?php

require 'class.phpmailer.php';

$mail = new PHPMailer();
   //Your SMTP servers details
   $mail->CharSet = 'UTF-8';
   $mail->IsSMTP(); // set mailer to use SMTP
   $mail->Host = "box3026.bluehost.com"; // specify main and backup server or localhost
   $mail->SMTPAuth = true; // turn on SMTP authentication
   $mail->Username = "support@tok2kids.com"; // SMTP username
   $mail->Password = "FT52roV_+S1?"; // SMTP password
   $mail->SMTPSecure = "tls";
   //It should be same as that of the SMTP user
   $html = '<h1>Hello</h1>';
   $mail->From = $mail->Username; //Default From email same as smtp user
   $mail->FromName = "Tok2Kids";
   $mail->AddAddress('gotocva@gmail.com', ""); //Email address where you wish to receive/collect those emails.
   $mail->WordWrap = 100; // set word wrap to 50 characters
   $mail->IsHTML(true); // set email format to HTML
   $mail->Subject = 'test';
   $mail->MsgHTML($html);
   if($mail->Send()){
    echo '{"Result":"Success","Userdetails":"Password has been sent to your emailid"}';
   }else{
    echo '{"Result":"Failed","Userdetails":"Mail sent failed"}';
   }
?>