<?php
include 'main.php';
// Ensure GET ID parameter exists
if (!isset($_GET['id'])) {
    exit('No chat log ID provided!');
}
// Retrieve chat logs from the database
if ($_SESSION['chat_account_role'] == 'Admin') {
    $stmt = $pdo->prepare('SELECT c.*, m.msg, a.full_name AS account_sender_full_name, a2.full_name AS account_receiver_full_name, a.email AS account_sender_email, a2.email AS account_receiver_email FROM conversations c JOIN accounts a ON a.id = c.account_sender_id JOIN accounts a2 ON a2.id = c.account_receiver_id LEFT JOIN messages m ON m.conversation_id = c.id WHERE c.id = ?');
    $stmt->execute([ $_GET['id'] ]);
} else {
    $stmt = $pdo->prepare('SELECT c.*, m.msg, a.full_name AS account_sender_full_name, a2.full_name AS account_receiver_full_name, a.email AS account_sender_email, a2.email AS account_receiver_email FROM conversations c JOIN accounts a ON a.id = c.account_sender_id JOIN accounts a2 ON a2.id = c.account_receiver_id LEFT JOIN messages m ON m.conversation_id = c.id WHERE c.id = ? AND (c.account_sender_id = ? OR c.account_receiver_id = ?)');
    $stmt->execute([ $_GET['id'], $_SESSION['chat_account_id'], $_SESSION['chat_account_id'] ]);    
}
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);
// If the conversation doesn't exist
if (!$conversation) {
    exit('Conversation does not exist!');
}
// Retrieve all messages based on the conversation ID
$stmt = $pdo->prepare('SELECT m.*, a.* FROM messages m LEFT JOIN accounts a ON a.id = m.account_id WHERE m.conversation_id = ? ORDER BY m.submit_date ASC');
$stmt->execute([ $_GET['id'] ]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);  
// Group all messages by the submit date
foreach ($results as $result) {
    $result['attachments'] = array_filter(explode(',', $result['attachments']));
    $conversation['messages'][date('d/m/y', strtotime($result['submit_date']))][] = $result;
}
// Chat log template below
?>
<?=template_admin_header('Chat Log', 'conversations', 'chat_logs')?>

<div class="content-title">
    <h2>Chat Log <span>[<?=htmlspecialchars($conversation['account_sender_email'] . ', ' . $conversation['account_receiver_email'], ENT_QUOTES)?>]</span></h2>
    <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
    <a href="chat_logs.php?delete=<?=$conversation['id']?>" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this chat log?')">Delete</a>
    <?php endif; ?>
</div>

<div class="content-block cover">
    <div class="conversations">
        <div class="messages">
            <div class="chat-messages full scroll">
                <?php foreach ($conversation['messages'] as $date => $array): ?>
                <p class="date"><?=$date==date('y/m/d')?'Today':$date?></p>
                <?php foreach ($array as $message): ?>
                <div class="chat-message<?=$_SESSION['chat_account_id']==$message['account_id']?'':' alt'?>" title="<?=date('H:i\p\m', strtotime($message['submit_date']))?>">
                    <span class="chat-message-info"><?=date('H:i\p\m', strtotime($message['submit_date']))?> by <?=htmlspecialchars($message['full_name'], ENT_QUOTES)?></span>
                    <?=decode_emojis(htmlspecialchars($message['msg'], ENT_QUOTES))?>
                </div>
                <?php if ($message['attachments']): ?>
                <?php foreach ($message['attachments'] as $attachment): ?>
                <a href="../<?=$attachment?>" class="chat-message-attachments<?=$_SESSION['chat_account_id']==$message['account_id']?'':' alt'?>" download>
                    <?=basename($attachment)?>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?=template_admin_footer()?>