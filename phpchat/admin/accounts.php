<?php
include 'main.php';
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
// Get department ID
$department = isset($_GET['department']) ? $_GET['department'] : '';
// Get role
$role = isset($_GET['role']) ? $_GET['role'] : '';
// Get status
$status = isset($_GET['status']) ? $_GET['status'] : '';
// Search term
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','email','full_name','role','last_seen','status','registered','messages_total','departments'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
if ($_SESSION['chat_account_role'] == 'Admin') {
    $where .= $search ? 'WHERE (a.email LIKE :search OR a.full_name LIKE :search) ' : '';
    $where .= $department ? ($search ? 'AND FIND_IN_SET(:department, a.departments) ' : 'WHERE FIND_IN_SET(:department, a.departments) ') : '';
    $where .= $role ? ($where ? 'AND a.role = :role ' : 'WHERE a.role = :role ') : '';
    $where .= $status ? ($where ? 'AND a.status = :status ' : 'WHERE a.status = :status ') : '';
} else {
    $where .= $search ? 'WHERE (a.email LIKE :search OR a.full_name LIKE :search) AND a.role != "Admin" ' : 'WHERE a.role != "Admin" ';
    $where .= $department ? 'AND FIND_IN_SET(:department, a.departments) ' : '';
    $where .= $role ? 'AND a.role = :role ' : '';
    $where .= $status ? 'AND a.status = :status ' : '';
}
// Retrieve the total number of accounts
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM accounts a ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($department) $stmt->bindParam('department', $department, PDO::PARAM_STR);
if ($role) $stmt->bindParam('role', $role, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
$stmt->execute();
$accounts_total = $stmt->fetchColumn();
// SQL query to get all accounts from the "accounts" table
$stmt = $pdo->prepare('SELECT 
    a.*, 
    (SELECT COUNT(*) FROM messages WHERE account_id = a.id) AS messages_total, 
    (SELECT GROUP_CONCAT(d.title SEPARATOR ", ") FROM departments d WHERE FIND_IN_SET(d.id, a.departments)) AS departments 
    FROM accounts a ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($department) $stmt->bindParam('department', $department, PDO::PARAM_STR);
if ($role) $stmt->bindParam('role', $role, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get all departments
$departments = $pdo->query('SELECT * FROM departments ORDER BY title')->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Account created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Account updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Account deleted successfully!';
    }
}
// Determine the URL
$url = 'accounts.php?search=' . $search . '&department=' . $department . '&role=' . $role . '&status=' . $status;
?>
<?=template_admin_header('Accounts', 'accounts', 'view')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-users"></i>
        <div class="txt">
            <h2>Accounts</h2>
            <p>View, manage, and search accounts.</p>
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
    <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
    <a href="account.php" class="btn">Create Account</a>
    <?php else: ?>
    <div></div>
    <?php endif; ?>
    <form action="" method="get">
        <input type="hidden" name="page" value="accounts">
        <div class="filters">
            <a href="#"><i class="fas fa-sliders-h"></i> Filters</a>
            <div class="list">
                <label>
                    Role
                    <select name="role">
                        <option value=""<?=$role==''?' selected':''?>>All</option>
                        <option value="Guest"<?=$role=='Guest'?' selected':''?>>Guest</option>
                        <option value="Operator"<?=$role=='Operator'?' selected':''?>>Operator</option>
                        <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
                        <option value="Admin"<?=$role=='Admin'?' selected':''?>>Admin</option>
                        <?php endif; ?>
                    </select>
                </label>
                <label>
                    Department
                    <select name="department">
                        <option value="">All</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?=$d['id']?>"<?=$department==$d['id']?' selected':''?>><?=$d['title']?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value=""<?=$status==''?' selected':''?>>All</option>
                        <option value="Idle"<?=$status=='Idle'?' selected':''?>>Idle</option>
                        <option value="Occupied"<?=$status=='Occupied'?' selected':''?>>Occupied</option>
                        <option value="Waiting"<?=$status=='Waiting'?' selected':''?>>Waiting</option>
                        <option value="Away"<?=$status=='Away'?' selected':''?>>Away</option>
                    </select>
                </label>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search account..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <i class="fas fa-search"></i>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($role != ''): ?>
    <div class="filter"><a href="<?=str_replace('&role=' . $role, '', $url)?>"><i class="fa-solid fa-xmark"></i></a> Role : <?=htmlspecialchars($role, ENT_QUOTES)?></div>
    <?php endif; ?>
    <?php if ($department != ''): ?>
    <div class="filter"><a href="<?=str_replace('&department=' . $department, '', $url)?>"><i class="fa-solid fa-xmark"></i></a> Department : <?=array_column($departments, 'title', 'id')[$department]?></div>
    <?php endif; ?>
    <?php if ($status != ''): ?>
    <div class="filter"><a href="<?=str_replace('&status=' . $status, '', $url)?>"><i class="fa-solid fa-xmark"></i></a> Status : <?=htmlspecialchars($status, ENT_QUOTES)?></div>
    <?php endif; ?>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?php if ($order_by=='id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=full_name'?>">Name<?php if ($order_by=='full_name'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=email'?>">Email<?php if ($order_by=='email'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=role'?>">Role<?php if ($order_by=='role'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=status'?>">Status<?php if ($order_by=='status'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=departments'?>">Departments<?php if ($order_by=='departments'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>    
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=messages_total'?>">Sent Messages<?php if ($order_by=='messages_total'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=last_seen'?>">Last Seen<?php if ($order_by=='last_seen'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=registered'?>">Registered<?php if ($order_by=='registered'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no accounts.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($accounts as $a): ?>
                <tr>
                    <td class="responsive-hidden"><?=$a['id']?></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($a['full_name'])?>"><?=strtoupper(substr($a['full_name'], 0, 1))?></span>
                    </td>
                    <td><?=htmlspecialchars($a['full_name'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($a['email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=$a['role']?></td>
                    <td class="responsive-hidden"><span class="<?=str_replace(['Idle','Occupied','Waiting','Away'], ['green','red','grey','orange'], $a['status'])?>"><?=$a['status']?></span></td>
                    <td class="responsive-hidden"><?=$a['departments'] ? htmlspecialchars($a['departments'], ENT_QUOTES) : '--'?></td>
                    <td class="responsive-hidden">
                        <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
                        <a href="chat_logs.php?acc_id=<?=$a['id']?>" class="link1"><?=$a['messages_total']?></a>
                        <?php else: ?>
                        <?=$a['messages_total']?>
                        <?php endif; ?>
                    </td>
                    <td class="responsive-hidden" title="<?=$a['last_seen']?>"><?=time_elapsed_string($a['last_seen'])?></td>
                    <td class="responsive-hidden"><?=date('Y-m-d H:ia', strtotime($a['registered']))?></td>
                    <td><a href="account.php?id=<?=$a['id']?>" class="link1">Edit</a></td>
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
    <span>Page <?=$pagination_page?> of <?=ceil($accounts_total / $results_per_page) == 0 ? 1 : ceil($accounts_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $accounts_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>