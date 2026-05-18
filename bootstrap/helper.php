<?php 
use PHPMailer\PHPMailer\PHPMailer;


 function sendEmail($user_email,$subject,$html){
    $mail = new PHPMailer(true);

    // SMTP
    $mail->isSMTP();
    $mail->Host = env('MAIL_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = env('MAIL_USERNAME');
    $mail->Password = env('MAIL_PASSWORD');
    $mail->SMTPSecure = env('MAIL_ENCRYPTION');
    $mail->Port = env('MAIL_PORT');

    // From & To
    $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
    $mail->addAddress($user_email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;

    return $mail->send();
 }
 
 
  function sendViewEmail($user_email,$subject,$viewPath,array $data = []){
        $html = View::make($viewPath, $data)->render();

        return sendEmail($user_email,$subject,$html);
  } 