<?php
// Include the main file
include 'main.php';
// Check if the user is logged-in
if (!is_loggedin($pdo)) {
    // User isn't logged-in
    exit('error');
}
// Update the account status to Idle
$stmt = $pdo->prepare('UPDATE accounts SET status = "Idle" WHERE id = ?');
$stmt->execute([ $_SESSION['chat_widget_account_id'] ]);
// Retrieve all the conversations associated with the user along with the most recent message
$stmt = $pdo->prepare('SELECT 
    c.*, 
    (SELECT msg FROM messages WHERE conversation_id = c.id ORDER BY submit_date DESC LIMIT 1) AS msg, 
    (SELECT submit_date FROM messages WHERE conversation_id = c.id ORDER BY submit_date DESC LIMIT 1) AS msg_date, 
    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND account_id = a.id AND is_read = 0) AS account_sender_unread, 
    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND account_id = a2.id AND is_read = 0) AS account_receiver_unread, 
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
    WHERE (c.account_sender_id = ? OR c.account_receiver_id = ?) AND (c.status = "Open" OR c.status = "Awaiting Transfer") GROUP BY c.id');
$stmt->execute([ $_SESSION['chat_widget_account_id'], $_SESSION['chat_widget_account_id'] ]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Sort the conversations by the most recent message date
usort($conversations, function($a, $b) {
    $date_a = strtotime($a['msg_date'] ? $a['msg_date'] : $a['submit_date']);
    $date_b = strtotime($b['msg_date'] ? $b['msg_date'] : $b['submit_date']);
    return $date_b - $date_a;
});
// Conversations template below
?>
<div class="chat-widget-conversations">
    <a href="#" class="chat-widget-new-conversation">&plus; New Chat</a>
    <?php foreach ($conversations as $c): ?>
    <?php 
    // Agent
    $which = $c['account_sender_id'] != $_SESSION['chat_widget_account_id'] ? 'sender' : 'receiver'; 
    // Guest
    $which2 = $c['account_sender_id'] == $_SESSION['chat_widget_account_id'] ? 'sender' : 'receiver';
    ?>
    <a href="#" class="chat-widget-user" data-id="<?=$c['id']?>" data-accountid="<?=$c['account_' . $which . '_id']?>" data-lastseen="Last seen <?=date('d/m/Y', strtotime($c['account_' . $which . '_last_seen']))?> at <?=date('H:i', strtotime($c['account_' . $which . '_last_seen']))?>" title="<?=date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($c['account_' . $which . '_last_seen'] . ' + 5 minute'))?'Offline':$c['account_' . $which . '_status']?>">
        <div class="chat-widget-profile-img">
            <?=!empty($c['account_' . $which . '_photo_url']) ? '<img src="' . htmlspecialchars($c['account_' . $which . '_photo_url'], ENT_QUOTES) . '" alt="' . htmlspecialchars($c['account_' . $which . '_full_name'], ENT_QUOTES) . '\'s Profile Image">' : '<span style="background-color:' . color_from_string($c['account_' . $which . '_full_name']) . '">' . strtoupper(substr($c['account_' . $which . '_full_name'], 0, 1)) . '</span>';?>
            <i class="<?=date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($c['account_' . $which . '_last_seen'] . ' + 5 minute'))?'offline':strtolower($c['account_' . $which . '_status'])?>"></i>
        </div>
        <div class="chat-widget-details">
            <h3 class="<?=strtolower($c['account_' . $which . '_role'])?>"><?=htmlspecialchars($c['account_' . $which . '_full_name'], ENT_QUOTES)?></h3>
            <p class="<?=$c['account_' . $which . '_unread']>0?'unread':''?>"><?=decode_emojis(htmlspecialchars(str_replace(['{name}', '{email}'], [$c['account_' . $which2 . '_full_name'], $c['account_' . $which2 . '_email']], $c['msg']), ENT_QUOTES))?></p>
        </div>
        <?php if ($c['msg_date']): ?>
        <div class="date"><?=date('Y/m/d') == date('Y/m/d', strtotime($c['msg_date'])) ? date('H:i', strtotime($c['msg_date'])) : date('d/m/y', strtotime($c['msg_date']))?></div>
        <?php else: ?>
        <div class="date"><?=date('Y/m/d') == date('Y/m/d', strtotime($c['submit_date'])) ? date('H:i', strtotime($c['submit_date'])) : date('d/m/y', strtotime($c['submit_date']))?></div>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>