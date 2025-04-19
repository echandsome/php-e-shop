<?php
include 'main.php';
// Namespaces for the PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Remove the time limit (for media uploads)
set_time_limit(0);
// Output JSON
header('Content-Type: application/json; charset=utf-8');
// Conversation endpoint Endpoint
if (isset($_GET['action']) && $_GET['action'] == 'conversation') {
    // Ensure GET ID exists
    if (isset($_GET['id'])) {
        // Gte conversation based on the GET ID parameter
        $stmt = $pdo->prepare('SELECT c.*, m.msg, a.full_name AS account_sender_full_name, a2.full_name AS account_receiver_full_name, a.email AS account_sender_email, a2.email AS account_receiver_email FROM conversations c JOIN accounts a ON a.id = c.account_sender_id JOIN accounts a2 ON a2.id = c.account_receiver_id LEFT JOIN messages m ON m.conversation_id = c.id WHERE c.id = ? AND (c.account_sender_id = ? OR c.account_receiver_id = ?) AND c.status = "Open"');
        $stmt->execute([ $_GET['id'], $_SESSION['chat_account_id'], $_SESSION['chat_account_id'] ]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        // If the conversation doesn't exist
        if (!$conversation) {
            exit('{"error":"The conversation does not exist!"}');
        }
        $conversation['which'] = $conversation['account_sender_id'] != $_SESSION['chat_account_id'] ? 'sender' : 'receiver';
        $conversation['account_id'] = $_SESSION['chat_account_id'];
        // Retrieve all messages based on the conversation ID
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE conversation_id = ? ORDER BY submit_date DESC LIMIT ?');
        $stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
        $stmt->bindValue(2, max_messages, PDO::PARAM_INT);
        $stmt->execute();
        $results = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC), true);
        // Retrieve all word filters from the database
        $word_filters = $pdo->query('SELECT * FROM word_filters')->fetchAll();
        // Update read messages
        $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND account_id != ?');
        $stmt->execute([ $_GET['id'], $_SESSION['chat_account_id'] ]);        
        // Group all messages by the submit date
        foreach ($results as $result) {
            $result['msg'] = str_ireplace(array_column($word_filters, 'word'), array_column($word_filters, 'replacement'), nl2br(decode_emojis(htmlspecialchars($result['msg'], ENT_QUOTES))));
            $result['msg'] = str_replace(['{name}', '{email}'], [$conversation['account_' . $conversation['which'] . '_full_name'], $conversation['account_' . $conversation['which'] . '_email']], $result['msg']);
            $conversation['messages'][date('d/m/y', strtotime($result['submit_date']))][] = $result;
        }
        // Encode results to JSON format
        exit(json_encode($conversation));
    }
}
// Archive conversation endpoint
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'conversation_archive') {
    // Update the conversation status to Archived
    $stmt = $pdo->prepare('UPDATE conversations SET status = "Archived" WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);    
    // Mark all messages as read
    $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ?');
    $stmt->execute([ $_GET['id'] ]);   
    // Output success
    exit('{"msg":"Success"}');
}
// Create conversation endpoint
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'conversation_create') {
    // Ensure the account exists
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        // Account exists, so check if there is already a conversation between the sender and receiver
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE (account_sender_id = ? OR account_receiver_id = ?) AND (account_sender_id = ? OR account_receiver_id = ?) AND status = "Open"');
        $stmt->execute([ $_SESSION['chat_account_id'], $_SESSION['chat_account_id'], $_GET['id'], $_GET['id'] ]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conversation) {
            // Conversation already exists, output redirect link to the conversation
            exit('{"url":"messages.php?id=' . $conversation['id'] . '"}');
        }
        // Conversation doesn't exist, create new conversation
        $stmt = $pdo->prepare('INSERT INTO conversations (account_sender_id,account_receiver_id,submit_date,status) VALUES (?,?,?,"Open")');
        $stmt->execute([ $_SESSION['chat_account_id'], $_GET['id'], date('Y-m-d H:i:s')]);
        // Ouput redirect link to the conversation
        exit('{"url":"messages.php?id=' . $pdo->lastInsertId() . '"}');
    } else {
        exit('{"error":"Request no longer available!"}');
    }
}
// New message endpoint
if (isset($_GET['action']) && $_GET['action'] == 'message') {
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
    $stmt->execute([ $_POST['id'], $_SESSION['chat_account_id'], $_SESSION['chat_account_id'] ]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$conversation) {
        // The user isn't not associated with the conversation, output error
        exit('{"error":"The conversation does not exist!"}');
    }
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
                move_uploaded_file($_FILES['files']['tmp_name'][$i], '../' . $file_path);
                // Append the new file URL to the attachments variable
                $attachments .= $file_path . ',';
            }
        }
    }
    $attachments = rtrim($attachments, ',');
    // Insert the new message into the database
    $stmt = $pdo->prepare('INSERT INTO messages (conversation_id,account_id,msg,attachments,submit_date) VALUES (?,?,?,?,?)');
    $stmt->execute([ $_POST['id'], $_SESSION['chat_account_id'], $_POST['msg'], $attachments, date('Y-m-d H:i:s') ]);
    // Update status
    $stmt = $pdo->prepare('UPDATE accounts SET status = "Occupied" WHERE id = ?');
    $stmt->execute([ $_SESSION['chat_account_id'] ]);
    // Send mail if enabled
    if (mail_enabled) {
        // Variables
        $from_email = $_SESSION['chat_account_id'] == $conversation['account_sender_id'] ? $conversation['account_sender_email'] : $conversation['account_receiver_email'];
        $from_name = $_SESSION['chat_account_id'] == $conversation['account_sender_id'] ? $conversation['account_sender_full_name'] : $conversation['account_receiver_full_name'];
        $from_last_seen = $_SESSION['chat_account_id'] == $conversation['account_sender_id'] ? $conversation['account_sender_last_seen'] : $conversation['account_receiver_last_seen'];
        $to_email = $_SESSION['chat_account_id'] == $conversation['account_sender_id'] ? $conversation['account_receiver_email'] : $conversation['account_sender_email'];
        $to_name = $_SESSION['chat_account_id'] == $conversation['account_sender_id'] ? $conversation['account_receiver_full_name'] : $conversation['account_sender_full_name'];
        $to_last_seen = $_SESSION['chat_account_id'] == $conversation['account_sender_id'] ? $conversation['account_receiver_last_seen'] : $conversation['account_sender_last_seen'];
        // Check if the to user is is offline
        if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($to_last_seen . ' + 5 minute'))) {
            // Include PHPMailer library
            require_once '../lib/phpmailer/Exception.php';
            require_once '../lib/phpmailer/PHPMailer.php';
            require_once '../lib/phpmailer/SMTP.php';
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
                    file_get_contents('../email-template.html')
                );
                // Body
                $mail->Body = $email_template;
                $mail->AltBody = strip_tags($email_template);
                // Send mail
                $mail->send();
            } catch (Exception $e) {
                // Output error message
                $msg = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
                exit('{"msg":"' . str_replace('"','\"', $msg) . '"}');
            }
        }
    }
    // Output success
    exit('{"msg":"Success"}');
}
// General info endpoint
if (isset($_GET['action']) && $_GET['action'] == 'info') {
    // Retrieve the total number of active accounts
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM accounts WHERE last_seen > date_sub(?, interval 5 minute)');
    $stmt->execute([ date('Y-m-d H:i:s') ]);
    $accounts_total = $stmt->fetchColumn();
    // Get the user's departments
    $departments = explode(',', $account['departments']);
    // Build SQL query that checks if the account is in any of the departments
    $department_where = '';
    if ($departments) {
        $department_where = 'AND (departments = "" OR ';
        foreach ($departments as $k => $department) {
            $department_where .= 'FIND_IN_SET("' . $department . '", departments)' . ($k+1 < count($departments) ? ' OR ' : '');
        }
        $department_where .= ')';
    }
    // SQL query to get all accounts that are waiting
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM accounts WHERE status = "Waiting" AND last_seen > date_sub(?, interval 15 minute) ' . $department_where . ' ORDER BY last_seen');
    $stmt->execute([ date('Y-m-d H:i:s') ]);
    $requests_total = $stmt->fetchColumn();
    // Get accounts awaiting transfer
    $stmt = $pdo->prepare('SELECT 
        COUNT(*) AS total 
        FROM accounts a 
        JOIN conversations c 
        ON (c.account_sender_id = a.id OR c.account_receiver_id = a.id) 
        AND c.status = "Awaiting Transfer" 
        AND ((c.transfer_method = "account" AND c.transfer_to = ?) OR (c.transfer_method = "department" AND FIND_IN_SET(c.transfer_to, ?))) 
        AND c.transfer_from != a.id AND c.account_sender_id != ? AND c.account_receiver_id != ? 
        WHERE a.id != ? AND a.role = "Guest" 
        GROUP BY a.id 
        ORDER BY a.last_seen');
    $stmt->execute([ $_SESSION['chat_account_id'], $account['departments'], $_SESSION['chat_account_id'], $_SESSION['chat_account_id'], $_SESSION['chat_account_id'] ]);
    $transfers_total = $stmt->fetchColumn();
    // Retrieve the total number of messages
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM messages m JOIN conversations c ON c.id = m.conversation_id AND (c.account_sender_id = ? OR c.account_receiver_id = ?) WHERE m.account_id != ? AND m.is_read = 0');
    $stmt->execute([ $_SESSION['chat_account_id'], $_SESSION['chat_account_id'], $_SESSION['chat_account_id'] ]);
    $messages_total = $stmt->fetchColumn();
    // Output JSON
    exit('{"users_online_total":' . $accounts_total . ', "messages_total":' . $messages_total . ', "requests_total":' . (intval($requests_total)+intval($transfers_total)) . ', "account_status":"' . $account['status'] . '"}');
}
// Account endpoint
if (isset($_GET['action']) && $_GET['action'] == 'account') {
    // Ensure GET ID paramater exists
    if (isset($_GET['id'])) {
        // Retrieve account from database
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) {
            exit('{"error":"The account does not exist!"}');
        }
        // Can edit account?
        $account['edit'] = $account['role'] == 'Admin' && $_SESSION['chat_account_role'] != 'Admin' ? false : true;
        exit(json_encode($account));
    }
}
// Accept request endpoint
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'request') {
    // Check if the request is a transfer
    if (isset($_GET['is_transfer'], $_GET['conversation_id']) && $_GET['is_transfer'] == 1) {
        // Get the conversation
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = ? AND status = "Awaiting Transfer" AND ((transfer_method = "account" AND transfer_to = ?) OR (transfer_method = "department" AND FIND_IN_SET(transfer_to, ?))) ');
        $stmt->execute([ $_GET['conversation_id'], $_SESSION['chat_account_id'], $account['departments'] ]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            exit('{"error":"The conversation does not exist!"}');
        }
        // Determine if the admin/operator is the sender or receiver
        $sender_or_receiver = $conversation['account_sender_id'] == $conversation['transfer_from'] ? 'account_sender_id' : 'account_receiver_id';
        // Update the conversation status
        $stmt = $pdo->prepare('UPDATE conversations SET status = "Open", ' . $sender_or_receiver . ' = ? WHERE id = ?');
        $stmt->execute([ $_SESSION['chat_account_id'], $_GET['conversation_id'] ]);
        // Output redirect URL
        exit('{"url":"messages.php?id=' . $_GET['conversation_id'] . '"}');
    }
    // Ensure the account is waiting for an operator
    $stmt = $pdo->prepare('SELECT id FROM accounts WHERE status = "Waiting" AND id = ?');
    $stmt->execute([ $_GET['id'] ]);
    // Check if the account is waiting for an operator or if the request is a transfer
    if ($stmt->rowCount() > 0) {
        // Account is waiting, so update the account status to Idle
        $stmt = $pdo->prepare('UPDATE accounts SET status = "Idle" WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        // Check if conversation already exists
        $stmt = $pdo->prepare('SELECT id FROM conversations WHERE (account_sender_id = ? OR account_receiver_id = ?) AND (account_sender_id = ? OR account_receiver_id = ?) AND status = "Open"');
        $stmt->execute([ $_SESSION['chat_account_id'], $_SESSION['chat_account_id'], $_GET['id'], $_GET['id'] ]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conversation) {
            // Conversation already exists, so output message redirect URL
            exit('{"url":"messages.php?id=' . $conversation['id'] . '"}');
        }
        // Conversation doesn't exist, so create one
        $stmt = $pdo->prepare('INSERT INTO conversations (account_sender_id,account_receiver_id,submit_date,status) VALUES (?,?,?,"Open")');
        $stmt->execute([ $_SESSION['chat_account_id'], $_GET['id'], date('Y-m-d H:i:s')]);
        // Output redirect URL
        exit('{"url":"messages.php?id=' . $pdo->lastInsertId() . '"}');
    } else {

        exit('{"error":"Request no longer available!"}');
    }
}
// Delete request endpoint
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'request_delete') {
    // Update the account status
    $stmt = $pdo->prepare('UPDATE accounts SET status = "Idle" WHERE id = ? AND status = "Waiting"');
    $stmt->execute([ $_GET['id'] ]);
    // Ouput success    
    exit('{"msg":"Success"}');
}
// Update status endpoint
if (isset($_GET['action'], $_GET['status']) && $_GET['action'] == 'update_status') {
    // Update the account status
    $stmt = $pdo->prepare('UPDATE accounts SET status = ? WHERE id = ?');
    $stmt->execute([ $_GET['status'], $_SESSION['chat_account_id'] ]);
    // Ouput success    
    exit('{"msg":"Success"}');
}
// Get online operators and admins endpoint
if (isset($_GET['action']) && $_GET['action'] == 'get_online_ops') {
    // Retrieve all online operators and admins
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE (role = "Admin" OR role = "Operator") AND last_seen > date_sub(?, interval 5 minute) AND id != ?');
    $stmt->execute([ date('Y-m-d H:i:s'), $_SESSION['chat_account_id'] ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Retrieve all departments
    $stmt = $pdo->query('SELECT * FROM departments');
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Output JSON
    exit('{"accounts":' . json_encode($results) . ', "departments":' . json_encode($departments) . '}');
}
// Transfer user endpoint
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'transfer') {
    $conversation_id = $_GET['id'];
    $account_id = isset($_POST['transfer_to']) ? $_POST['transfer_to'] : '';
    $department_id = isset($_POST['transfer_to_department']) ? $_POST['transfer_to_department'] : '';
    $transfer_reason = isset($_POST['transfer_reason']) ? $_POST['transfer_reason'] : '';
    if (!$account_id && !$department_id) {
        exit('{"error":"Please select a department or operator!"}');
    } else if ($account_id) {
        // Ensure the account exists
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ? AND (role = "Admin" OR role = "Operator") AND last_seen > date_sub(?, interval 5 minute)');
        $stmt->execute([ $account_id, date('Y-m-d H:i:s') ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) {
            exit('{"error":"The account does not exist or is offline!"}');
        } else {
            // Update conversation status
            $stmt = $pdo->prepare('UPDATE conversations SET transfer_to = ?, transfer_from = ?, transfer_method = "account", status = "Awaiting Transfer", transfer_reason = ? WHERE id = ?');
            $stmt->execute([ $account_id, $_SESSION['chat_account_id'], $transfer_reason, $conversation_id ]);
            // Output success
            exit('{"msg":"Success"}');
        }
    } else if ($department_id) {
        // Ensure the department exists
        $stmt = $pdo->prepare('SELECT * FROM departments WHERE id = ?');
        $stmt->execute([ $department_id ]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$department) {
            exit('{"error":"The department does not exist!"}');
        } else {
            // Update conversation status
            $stmt = $pdo->prepare('UPDATE conversations SET transfer_to = ?, transfer_from = ?, transfer_method = "department", status = "Awaiting Transfer", transfer_reason = ? WHERE id = ?');
            $stmt->execute([ $department_id, $_SESSION['chat_account_id'], $transfer_reason, $conversation_id ]);
            // Output success
            exit('{"msg":"Success"}');
        }
    }
}
// Encode results to JSON format
exit('{"error":"No action provided!"}');
?>