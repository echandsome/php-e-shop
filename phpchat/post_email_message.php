<?php
// include the main file
include 'main.php';
// Namespaces for the PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Include PHPMailer library
require_once 'lib/phpmailer/Exception.php';
require_once 'lib/phpmailer/PHPMailer.php';
require_once 'lib/phpmailer/SMTP.php';
// Output msg
$msg = '';
// Validate the form data
if (!isset($_POST['name'], $_POST['email'], $_POST['message'])) {
    $msg = 'Please enter a valid name, email address and message!';
} else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
	$msg = 'Please enter a valid email address!';
} else if (!preg_match('/^[a-zA-Z\s]+$/', $_POST['name'])) {
    $msg = 'Name must contain only letters!';
} else if (strlen($_POST['message']) < 10 || strlen($_POST['message']) > 500) {
    $msg = 'Message must be between 10 and 500 characters long!';
} else {
    // Validation success. Send email.
    $mail = new PHPMailer(true);
    try {
        // SMTP Server settings
        if (SMTP) {
            $mail->isSMTP();
            $mail->Host = smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = smtp_user;
            $mail->Password = smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = smtp_port;
        }
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress(mail_to);
        $mail->addReplyTo($_POST['email'], $_POST['name']);
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New message from ' . $_POST['name'];
        // Email template
        $email_template = str_replace(
            ['%subject%', '%name%', '%email%', '%message%'],
            ['New message from ' . htmlspecialchars($_POST['name'], ENT_QUOTES), htmlspecialchars($_POST['name'], ENT_QUOTES), htmlspecialchars($_POST['email'], ENT_QUOTES), nl2br(htmlspecialchars($_POST['message'], ENT_QUOTES))],
            file_get_contents('email-template.html')
        );
        // Body
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($email_template);
        // Send mail
        $mail->send();
        // Output success message
        $msg = 'Your message has been sent!';
    } catch (Exception $e) {
        // Output error message
        $msg = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
    }
}
// Output JSON
header('Content-Type: application/json; charset=utf-8');
// Output json
echo json_encode([
    'msg' => $msg
]);
?>