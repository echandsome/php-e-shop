<?php
include 'main.php';
// Get the user's departments
$departments = explode(',', $account['departments']);
// Build SQL query that checks if the account is in any of the departments
$department_where = '';
if ($departments) {
    $department_where = 'AND (a.departments = "" OR ';
    foreach ($departments as $k => $department) {
        $department_where .= 'FIND_IN_SET("' . $department . '", a.departments)' . ($k+1 < count($departments) ? ' OR ' : '');
    }
    $department_where .= ')';
}
// SQL query to get all accounts that are waiting
$stmt = $pdo->prepare('SELECT a.*, (SELECT GROUP_CONCAT(d.title SEPARATOR ", ") FROM departments d WHERE FIND_IN_SET(d.id, a.departments)) AS departments FROM accounts a WHERE a.status = "Waiting" AND a.last_seen > date_sub(?, interval 15 minute) ' . $department_where . ' ORDER BY a.last_seen');
$stmt->execute([ date('Y-m-d H:i:s') ]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get accounts awaiting transfer
$stmt = $pdo->prepare('SELECT 
    a.*, 
    (SELECT GROUP_CONCAT(d.title SEPARATOR ", ") FROM departments d WHERE FIND_IN_SET(d.id, a.departments)) AS departments,  
    c.transfer_method, 
    c.transfer_reason, 
    c.id AS conversation_id, 
    (SELECT email FROM accounts WHERE id = c.transfer_from) transfer_from 
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
$transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Requests template below
?>
<?=template_admin_header(number_format(count($accounts)+count($transfers)) . ' Requests', 'requests', 'view')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-user-check"></i>
        <div class="txt">
            <h2><?=number_format(count($accounts)+count($transfers))?> Requests</h2>
            <p>View users and accept chat requests.</p>
        </div>
    </div>
</div>

<div class="content-block cover">
    <div class="requests">
        <div class="list">
            <div class="form responsive-width-100">
                <label for="search">
                    <input type="text" class="search" placeholder="Search...">
                    <i class="fas fa-search"></i>
                </label>
            </div>
            <div class="users scroll">
                <?php if ($transfers): ?>
                <h5>Transfers</h5>
                <?php foreach ($transfers as $account): ?>
                <a href="#" class="user" data-conversation-id="<?=$account['conversation_id']?>" data-transfer-method="<?=$account['transfer_method']?>" data-transfer-from="<?=$account['transfer_from']?>" data-transfer-reason="<?=htmlspecialchars($account['transfer_reason'], ENT_QUOTES)?>" data-id="<?=$account['id']?>" data-status="<?=$account['status']?>" data-departments="<?=$account['departments'] ? htmlspecialchars($account['departments'], ENT_QUOTES) : '--'?>" data-ip="<?=$account['ip']?>" data-useragent="<?=htmlspecialchars($account['user_agent'], ENT_QUOTES)?>" data-role="<?=$account['role']?>" data-email="<?=$account['email']?>" data-registered="<?=$account['registered']?>">
                    <div class="profile-img">
                        <?=!empty($account['photo_url']) ? '<img src="' . htmlspecialchars($account['photo_url'], ENT_QUOTES) . '" alt="' . htmlspecialchars($account['photo_url'], ENT_QUOTES) . '\'s Profile Image">' : '<span style="background-color:' . color_from_string($account['full_name']) . '">' . strtoupper(substr($account['full_name'], 0, 1)) . '</span>';?>
                        <i class="<?=date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($account['last_seen'] . ' + 5 minute'))?'offline':strtolower($account['status'])?>"></i>
                    </div>
                    <div class="details">
                        <h3 class="<?=strtolower($account['role'])?>"><?=htmlspecialchars($account['full_name'], ENT_QUOTES)?></h3>
                        <p><?=time_elapsed_string($account['last_seen'])?></p>
                    </div>
                </a>
                <?php endforeach; ?>     
                <?php if ($accounts): ?> 
                <h5>Others</h5>   
                <?php endif; ?> 
                <?php endif; ?>       
                <?php foreach ($accounts as $account): ?>
                <a href="#" class="user" data-id="<?=$account['id']?>" data-status="<?=$account['status']?>" data-departments="<?=$account['departments'] ? htmlspecialchars($account['departments'], ENT_QUOTES) : '--'?>" data-ip="<?=$account['ip']?>" data-useragent="<?=htmlspecialchars($account['user_agent'], ENT_QUOTES)?>" data-role="<?=$account['role']?>" data-email="<?=$account['email']?>" data-registered="<?=$account['registered']?>">
                    <div class="profile-img">
                        <?=!empty($account['photo_url']) ? '<img src="' . htmlspecialchars($account['photo_url'], ENT_QUOTES) . '" alt="' . htmlspecialchars($account['photo_url'], ENT_QUOTES) . '\'s Profile Image">' : '<span style="background-color:' . color_from_string($account['full_name']) . '">' . strtoupper(substr($account['full_name'], 0, 1)) . '</span>';?>
                        <i class="<?=date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($account['last_seen'] . ' + 5 minute'))?'offline':strtolower($account['status'])?>"></i>
                    </div>
                    <div class="details">
                        <h3 class="<?=strtolower($account['role'])?>"><?=htmlspecialchars($account['full_name'], ENT_QUOTES)?></h3>
                        <p><?=time_elapsed_string($account['last_seen'])?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="info scroll"></div>
    </div>
</div>

<?=template_admin_footer('initRequests()')?>