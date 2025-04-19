<?php
include 'main.php';
// SQL query to get all accounts online in the last 5 mins
$stmt = $pdo->prepare('SELECT a.*, (SELECT GROUP_CONCAT(d.title SEPARATOR ", ") FROM departments d WHERE FIND_IN_SET(d.id, a.departments)) AS departments FROM accounts a WHERE a.last_seen > date_sub(?, interval 5 minute) ORDER BY a.full_name');
// Bind params
$stmt->execute([ date('Y-m-d H:i:s') ]);
// Retrieve query results
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Users online template below
?>
<?=template_admin_header(number_format(count($accounts)) . ' Users Online', 'users_online', 'view')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-user-clock"></i>
        <div class="txt">
            <h2><?=number_format(count($accounts))?> User<?=count($accounts)!=1?'s':''?> Online</h2>
            <p>View and manage online users.</p>
        </div>
    </div>
</div>

<div class="content-block cover">
    <div class="users-online">
        <div class="list">
            <div class="form responsive-width-100">
                <label for="search">
                    <input type="text" class="search" placeholder="Search...">
                    <i class="fas fa-search"></i>
                </label>
            </div>
            <div class="users scroll">
                <?php foreach ($accounts as $account): ?>
                <a href="#" class="user" data-id="<?=$account['id']?>" data-status="<?=$account['status']?>" data-departments="<?=$account['departments'] ? htmlspecialchars($account['departments'], ENT_QUOTES) : '--'?>" data-ip="<?=$account['ip']?>" data-useragent="<?=htmlspecialchars($account['user_agent'], ENT_QUOTES)?>" data-role="<?=$account['role']?>" data-email="<?=$account['email']?>" data-registered="<?=$account['registered']?>" data-edit="<?=$_SESSION['chat_account_role'] != 'Admin' && $account['role'] == 'Admin' ? 'false' : 'true'?>">
                    <div class="profile-img">
                        <?=!empty($account['photo_url']) ? '<img src="' . htmlspecialchars($account['photo_url'], ENT_QUOTES) . '" alt="' . htmlspecialchars($account['photo_url'], ENT_QUOTES) . '\'s Profile Image">' : '<span style="background-color:' . color_from_string($account['full_name']) . '">' . strtoupper(substr($account['full_name'], 0, 1)) . '</span>';?>
                        <i class="<?=strtolower($account['status'])?>"></i>
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

<?=template_admin_footer('initUsersOnline()')?>