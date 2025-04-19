<?php
// Include the main file
include 'main.php';
// Check if the user is logged-in
if (!is_loggedin($pdo)) {
    // User isn't logged-in
    exit('error');
}
// Ensure the GET ID param exists
if (!isset($_GET['id'])) {
    exit('error');
}
// Update the account status to Occupied
$stmt = $pdo->prepare('UPDATE accounts SET status = "Occupied" WHERE id = ?');
$stmt->execute([ $_SESSION['chat_widget_account_id'] ]);
// Retrieve the conversation based on the GET ID param and account ID
$stmt = $pdo->prepare('SELECT 
    c.*, 
    m.msg,
    a.id AS account_sender_id, 
    a2.id AS account_receiver_id,
    a.full_name AS account_sender_full_name, 
    a2.full_name AS account_receiver_full_name, 
    a.photo_url AS account_sender_photo_url, 
    a2.photo_url AS account_receiver_photo_url, 
    a.status AS account_sender_status, 
    a2.status AS account_receiver_status, 
    a.role AS account_sender_role, 
    a2.role AS account_receiver_role,
    a.last_seen AS account_sender_last_seen, 
    a2.last_seen AS account_receiver_last_seen, 
    a.email AS account_sender_email,
    a2.email AS account_receiver_email   
    FROM conversations c 
    JOIN accounts a ON a.id = c.account_sender_id 
    JOIN accounts a2 ON a2.id = c.account_receiver_id 
    LEFT JOIN messages m ON m.conversation_id = c.id 
    WHERE c.id = ? AND (c.account_sender_id = ? OR c.account_receiver_id = ?) AND (c.status = "Open" OR c.status = "Awaiting Transfer")');
$stmt->execute([ $_GET['id'], $_SESSION['chat_widget_account_id'], $_SESSION['chat_widget_account_id'] ]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);
$conversation['messages'] = [];
// If the conversation doesn't exist
if (!$conversation) {
    exit('error');
}
// Agent
$which = $conversation['account_sender_id'] != $_SESSION['chat_widget_account_id'] ? 'sender' : 'receiver';
// Guest
$which2 = $conversation['account_sender_id'] == $_SESSION['chat_widget_account_id'] ? 'sender' : 'receiver';
// Retrieve all messages based on the conversation ID
$stmt = $pdo->prepare('SELECT * FROM messages WHERE conversation_id = ? ORDER BY submit_date DESC LIMIT ?');
$stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
$stmt->bindValue(2, max_messages, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$results = array_reverse($results, true);
// Retrieve all word filters from the database
$word_filters = $pdo->query('SELECT * FROM word_filters')->fetchAll();
// Update read messages
$stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND account_id != ?');
$stmt->execute([ $_GET['id'], $_SESSION['chat_widget_account_id'] ]);        
// Group all messages by the submit date
foreach ($results as $result) {
    $result['msg'] = str_ireplace(array_column($word_filters, 'word'), array_column($word_filters, 'replacement'), nl2br(decode_emojis(htmlspecialchars($result['msg'], ENT_QUOTES))));
    $result['msg'] = str_replace(['{name}', '{email}'], [$conversation['account_' . $which2 . '_full_name'], $conversation['account_' . $which2 . '_email']], $result['msg']);
    $result['attachments'] = array_filter(explode(',', $result['attachments']));
    $conversation['messages'][date('d/m/y', strtotime($result['submit_date']))][] = $result;
}
// Conversation template below
?>
<div class="chat-widget-message-header">
    <div class="chat-widget-profile-img">
        <?=!empty($conversation['account_' . $which . '_photo_url']) ? '<img src="' . htmlspecialchars($conversation['account_' . $which . '_photo_url'], ENT_QUOTES) . '" alt="' . htmlspecialchars($conversation['account_' . $which . '_full_name'], ENT_QUOTES) . '\'s Profile Image">' : '<span style="background-color:' . color_from_string($conversation['account_' . $which . '_full_name']) . '">' . strtoupper(substr($conversation['account_' . $which . '_full_name'], 0, 1)) . '</span>';?>
        <i class="<?=date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($conversation['account_' . $which . '_last_seen'] . ' + 5 minute'))?'offline':strtolower($conversation['account_' . $which . '_status'])?>"></i>
    </div>
    <div class="chat-widget-details">
        <h3><?=htmlspecialchars($conversation['account_' . $which . '_full_name'], ENT_QUOTES)?></h3>
        <p>Last seen <?=date('d/m/Y', strtotime($conversation['account_' . $which . '_last_seen']))?> at <?=date('H:i', strtotime($conversation['account_' . $which . '_last_seen']))?></p>
    </div>
</div>
<div class="chat-widget-messages chat-widget-scroll">
    <p class="date">You're now chatting with <?=htmlspecialchars($conversation['account_' . $which . '_full_name'], ENT_QUOTES)?>!</p>
    <?php foreach ($conversation['messages'] as $date => $array): ?>
    <p class="date"><?=$date==date('y/m/d')?'Today':$date?></p>
    <?php foreach ($array as $message): ?>
    <div class="chat-widget-message<?=$_SESSION['chat_widget_account_id']==$message['account_id']?'':' alt'?>" title="<?=date('H:i\p\m', strtotime($message['submit_date']))?>"><?=$message['msg']?></div>
    <?php if ($message['attachments']): ?>
    <div class="chat-widget-message-attachments<?=$_SESSION['chat_widget_account_id']==$message['account_id']?'':' alt'?>">
        <svg xmlns="http://www.w3.org/2000/svg" height="12" width="10" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M364.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z"/></svg> <?=count($message['attachments'])?> Attachment<?=count($message['attachments']) > 1 ? 's' : ''?>
    </div>
    <div class="chat-widget-message-attachments-links">
        <?php foreach ($message['attachments'] as $attachment): ?>
        <a href="<?=$attachment?>" download></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php if ($conversation['status'] == 'Awaiting Transfer'): ?>
    <p class="date">Please wait while we transfer you to another agent...</p>
    <?php endif; ?>
</div>
<div class="chat-widget-attachments"></div>
<form action="post_message.php" method="post" class="chat-widget-input-message" autocomplete="off">
    <input type="text" name="msg" placeholder="Message">
    <input type="file" name="files[]" class="files" accept="<?=file_types_allowed?>" multiple>
    <input type="hidden" name="id" value="<?=$conversation['id']?>">
    <div class="actions">
        <?php if (attachments_enabled): ?>
        <a href="#" class="attach-files" title="Attach Files">
            <svg xmlns="http://www.w3.org/2000/svg" height="14" width="12" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M364.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z"/></svg>
        </a>
        <?php endif; ?>
        <div class="view-emojis">
            <svg xmlns="http://www.w3.org/2000/svg" height="14" width="14" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm177.6 62.1C192.8 334.5 218.8 352 256 352s63.2-17.5 78.4-33.9c9-9.7 24.2-10.4 33.9-1.4s10.4 24.2 1.4 33.9c-22 23.8-60 49.4-113.6 49.4s-91.7-25.5-113.6-49.4c-9-9.7-8.4-24.9 1.4-33.9s24.9-8.4 33.9 1.4zM144.4 208a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm192-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>
            <span class="emoji-list chat-widget-scroll">
                <?php foreach (explode(',', emoji_list) as $emoji): ?>
                <span>&#x<?=$emoji?>;</span>
                <?php endforeach; ?>
            </span>    
        </div>                       
    </div>
</form>