<?php
// Include the main file
include 'main.php';
// Variables
$msg = '';
$status = 'waiting';
// Check if the user is logged-in
if (!is_loggedin($pdo)) {
    // User isn't logged-in
    $status = 'error';
} else {
    // Get counter
    $count = isset($_GET['count']) && is_numeric($_GET['count']) ? intval($_GET['count']) : 0;
    // Get departments
    $departments = [];
    // Check if departments exist in the database
    if (isset($_GET['departments'])) {
        $stmt = $pdo->prepare('SELECT id FROM departments WHERE FIND_IN_SET(id, ?) OR FIND_IN_SET(title, ?)');
        $stmt->execute([ $_GET['departments'], $_GET['departments'] ]);
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    // Update the account status to Waiting
    $stmt = $pdo->prepare('UPDATE accounts SET status = "Waiting", departments = ? WHERE id = ?');
    $stmt->execute([ implode(',',$departments), $_SESSION['chat_widget_account_id'] ]);
    // Check if the conversation was already created
    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE (account_sender_id = ? OR account_receiver_id = ?) AND submit_date > date_sub(?, interval 1 minute) AND status = "Open"');
    $stmt->execute([ $_SESSION['chat_widget_account_id'], $_SESSION['chat_widget_account_id'], date('Y-m-d H:i:s') ]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    // If the conversation exists, output the ID
    if ($conversation) {
        $status = 'success';
        $msg = $conversation['id'];
    } else {
        // Automated responses while waiting to connect to an operator
        $automated_responses = explode('\n', automated_responses);
        $msg = isset($automated_responses[$count]) ? $automated_responses[$count] : '';
        // Extended responses
        if ($count >= 10) {
            $extended_count = $count - 10;
            $automated_responses = explode('\n', extended_responses);
            $msg = isset($automated_responses[$extended_count]) ? $automated_responses[$extended_count] : '';
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