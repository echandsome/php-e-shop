<?php
// Include the main file
include 'main.php';
// Get the number of admins and operators online
$stmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE (role = "Admin" OR role = "Operator") AND last_seen > date_sub(?, interval 5 minute)');
$stmt->execute([ date('Y-m-d H:i:s') ]);
$agents_online = $stmt->fetchColumn();
// Retrieve the total number of messages
if (isset($_SESSION['chat_widget_account_loggedin'])) {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM messages m JOIN conversations c ON c.id = m.conversation_id AND (c.account_sender_id = ? OR c.account_receiver_id = ?) WHERE m.account_id != ? AND m.is_read = 0');
    $stmt->execute([ $_SESSION['chat_widget_account_id'], $_SESSION['chat_widget_account_id'], $_SESSION['chat_widget_account_id'] ]);
    $messages_total = $stmt->fetchColumn();
} else {
    $messages_total = 0;
}
// Output JSON
header('Content-Type: application/json; charset=utf-8');
// Output json
echo json_encode([
    'ops_online' => $agents_online,
    'mail_enabled' => mail_enabled,
    'is_loggedin' => isset($_SESSION['chat_widget_account_loggedin']) ? true : false,
    'messages_total' => $messages_total
]);
?>