<?php
include 'main.php';
// Check if delete
if (isset($_GET['delete']) && $_SESSION['chat_account_role'] == 'Admin') {
    // Get messages
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE conversation_id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Delete attachments
    foreach ($messages as $message) {
        $attachments = array_filter(explode(',', $message['attachments']));
        foreach ($attachments as $attachment) {
            unlink('../' . $attachment);
        }
    }
    // Delete chat log
    $stmt = $pdo->prepare('DELETE c, m FROM conversations c LEFT JOIN messages m ON m.conversation_id = c.id WHERE c.id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    // Redirect to chat logs with success message
    header('Location: chat_logs.php?success_msg=3');
    exit;
}
// Determine acc id
$acc_id = isset($_GET['acc_id']) && $_SESSION['chat_account_role'] == 'Admin' ? $_GET['acc_id'] : $_SESSION['chat_account_id'];
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','account_sender_full_name','account_receiver_full_name','messages_total','submit_date','status'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? ' AND (a.full_name LIKE :search OR a2.full_name LIKE :search OR a.email LIKE :search OR a2.email LIKE :search) ' : '';
// Retrieve the total number of conversations
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM conversations c LEFT JOIN accounts a ON a.id = c.account_sender_id LEFT JOIN accounts a2 ON a2.id = c.account_receiver_id WHERE (c.account_sender_id = :acc_id OR c.account_receiver_id = :acc_id) ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->bindParam('acc_id', $_SESSION['chat_account_id'], PDO::PARAM_INT);
$stmt->execute();
$filters_total = $stmt->fetchColumn();
// SQL query to get all chat logs
$stmt = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) AS messages_total, a.full_name AS account_sender_full_name, a.email AS account_sender_email, a2.full_name AS account_receiver_full_name, a2.email AS account_receiver_email FROM conversations c LEFT JOIN accounts a ON a.id = c.account_sender_id LEFT JOIN accounts a2 ON a2.id = c.account_receiver_id WHERE (c.account_sender_id = :acc_id OR c.account_receiver_id = :acc_id) ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('acc_id', $acc_id, PDO::PARAM_INT);
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$chat_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Chat log deleted successfully!';
    }
}
// Determine the URL
$url = 'chat_logs.php?search=' . $search . '&acc_id=' . $acc_id;
// Chat logs template below
?>
<?=template_admin_header('Chat Logs', 'conversations', 'chat_logs')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <div class="txt">
            <h2>Chat Logs</h2>
            <p>View, manage, and search chat logs.</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>


<div class="content-header responsive-flex-column pad-top-5">
    <div></div>
    <form action="" method="get">
        <input type="hidden" name="page" value="chat_logs">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search chat logs..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <i class="fas fa-search"></i>
            </label>
        </div>
    </form>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?php if ($order_by=='id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=account_sender_full_name'?>">Sender<?php if ($order_by=='account_sender_full_name'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=account_receiver_full_name'?>">Receiver<?php if ($order_by=='account_receiver_full_name'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=messages_total'?>">Total Messages<?php if ($order_by=='messages_total'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=status'?>">Status<?php if ($order_by=='status'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=submit_date'?>">Date<?php if ($order_by=='submit_date'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chat_logs)): ?>
                <tr>
                    <td colspan="10" class="no-results">There are no chat logs.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($chat_logs as $chat_log): ?>
                <tr>
                    <td class="responsive-hidden"><?=$chat_log['id']?></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($chat_log['account_sender_full_name'])?>"><?=strtoupper(substr($chat_log['account_sender_full_name'], 0, 1))?></span>
                    </td>
                    <td><?=htmlspecialchars($chat_log['account_sender_full_name'], ENT_QUOTES)?><br><span class="alt"><?=htmlspecialchars($chat_log['account_sender_email'], ENT_QUOTES)?></span></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($chat_log['account_receiver_full_name'])?>"><?=strtoupper(substr($chat_log['account_receiver_full_name'], 0, 1))?></span>
                    </td>
                    <td><?=htmlspecialchars($chat_log['account_receiver_full_name'], ENT_QUOTES)?><br><span class="alt"><?=htmlspecialchars($chat_log['account_receiver_email'], ENT_QUOTES)?></span></td>
                    <td class="responsive-hidden"><?=$chat_log['messages_total']?></td>
                    <td class="responsive-hidden"><span class="<?=str_replace(['Open','Archived','Awaiting Transfer'],['green','grey','orange'],$chat_log['status'])?>"><?=$chat_log['status']?></span></td>
                    <td class="responsive-hidden"><?=$chat_log['submit_date']?></td>
                    <td>
                        <a href="chat_log.php?id=<?=$chat_log['id']?>" class="link1">View</a>
                        <a href="chat_logs.php?delete=<?=$chat_log['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this chat log?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($filters_total / $results_per_page) == 0 ? 1 : ceil($filters_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $filters_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>