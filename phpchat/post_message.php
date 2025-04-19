<?php
// Include the main file
include 'main.php';
// Namespaces for the PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Output msg
$msg = '';
$status = '';
// Check if the user is logged-in
if (!is_loggedin($pdo)) {
    // User isn't logged-in
    $status = 'error';
} else if (!isset($_POST['id'], $_POST['msg'])) { 
    // Ensure the GET ID and msg params exists
    $status = 'error';
} else {
    // Make sure the user is associated with the conversation
    $stmt = $pdo->prepare('SELECT 
        c.id, 
        c.account_sender_id,
        c.account_receiver_id,
        a.email AS account_sender_email, 
        a2.email AS account_receiver_email,
        a.full_name AS account_sender_full_name,
        a2.full_name AS account_receiver_full_name,
        a.last_seen AS account_sender_last_seen,
        a2.last_seen AS account_receiver_last_seen 
        FROM conversations c 
        LEFT JOIN accounts a ON a.id = c.account_sender_id 
        LEFT JOIN accounts a2 ON a2.id = c.account_receiver_id
        WHERE c.id = ? AND (c.account_sender_id = ? OR c.account_receiver_id = ?) AND c.status = "Open" GROUP BY c.id');
    $stmt->execute([ $_POST['id'], $_SESSION['chat_widget_account_id'], $_SESSION['chat_widget_account_id'] ]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    // Ensure the conversation exists
    if (!$conversation) {
        // The user isn't associated with the conversation, output error
        $status = 'error';
    } else {
        // Attachments comma-seperated string
        $attachments = '';
        // Check if the user has uploaded files
        if (isset($_FILES['files']) && attachments_enabled) {
            // Iterate all the uploaded files
            for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                // Get the file extension (png, jpg, etc)
                $ext = pathinfo($_FILES['files']['name'][$i], PATHINFO_EXTENSION);
                // The file name will contain a unique code to prevent multiple files with the same name.
                $file_path = file_upload_directory . sha1(uniqid() . $i) .  '.' . $ext;
                // Ensure the file is valid
                if (!empty($_FILES['files']['tmp_name'][$i]) && $_FILES['files']['size'][$i] <= max_allowed_upload_file_size && in_array('.' . strtolower($ext), explode(',', file_types_allowed))) {
                    // If everything checks out we can move the uploaded file to its final destination...
                    move_uploaded_file($_FILES['files']['tmp_name'][$i], $file_path);
                    // Append the new file URL to the attachments variable
                    $attachments .= $file_path . ',';
                }
            }
        }
        // Remove the last comma
        $attachments = rtrim($attachments, ',');
        // Insert the new message into the database
        $stmt = $pdo->prepare('INSERT INTO messages (conversation_id,account_id,msg,attachments,submit_date) VALUES (?,?,?,?,?)');
        $stmt->execute([ $_POST['id'], $_SESSION['chat_widget_account_id'], $_POST['msg'], $attachments, date('Y-m-d H:i:s') ]);
        // Success
        $status = 'success';
        // Send mail if enabled
        if (mail_enabled) {
            // Variables
            $from_email = $_SESSION['chat_widget_account_id'] == $conversation['account_sender_id'] ? $conversation['account_sender_email'] : $conversation['account_receiver_email'];
            $from_name = $_SESSION['chat_widget_account_id'] == $conversation['account_sender_id'] ? $conversation['account_sender_full_name'] : $conversation['account_receiver_full_name'];
            $from_last_seen = $_SESSION['chat_widget_account_id'] == $conversation['account_sender_id'] ? $conversation['account_sender_last_seen'] : $conversation['account_receiver_last_seen'];
            $to_email = $_SESSION['chat_widget_account_id'] == $conversation['account_sender_id'] ? $conversation['account_receiver_email'] : $conversation['account_sender_email'];
            $to_name = $_SESSION['chat_widget_account_id'] == $conversation['account_sender_id'] ? $conversation['account_receiver_full_name'] : $conversation['account_sender_full_name'];
            $to_last_seen = $_SESSION['chat_widget_account_id'] == $conversation['account_sender_id'] ? $conversation['account_receiver_last_seen'] : $conversation['account_sender_last_seen'];
            // Check if the to user is is offline
            if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($to_last_seen . ' + 5 minute'))) {
                // Include PHPMailer library
                require_once 'lib/phpmailer/Exception.php';
                require_once 'lib/phpmailer/PHPMailer.php';
                require_once 'lib/phpmailer/SMTP.php';
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
                    $mail->addAddress($to_email, $to_name);
                    $mail->addReplyTo($from_email, $from_name);
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'New message from ' . $from_name;
                    // Email template
                    $email_template = str_replace(
                        ['%subject%', '%name%', '%email%', '%message%'],
                        ['New message from ' . htmlspecialchars($from_name, ENT_QUOTES), htmlspecialchars($from_name, ENT_QUOTES), htmlspecialchars($from_email, ENT_QUOTES), nl2br(htmlspecialchars($_POST['msg'], ENT_QUOTES))],
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
        }
    }
}
// Output JSON
header('Content-Type: application/json; charset=utf-8');
// Output json
echo json_encode([
    'status' => $status,
    'msg' => $msg
]);
?>