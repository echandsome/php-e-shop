<?php
include 'main.php';
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','title','total_accounts_operators','total_accounts','created'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (d.title LIKE :search) ' : '';
// Retrieve the total number of departments
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM departments d ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$departments_total = $stmt->fetchColumn();
// SQL query to get all departments from the "departments" table
$stmt = $pdo->prepare('SELECT 
    d.*, 
    (SELECT COUNT(*) FROM accounts WHERE (role = "Admin") AND FIND_IN_SET(d.id, departments)) AS total_accounts_admins, 
    (SELECT COUNT(*) FROM accounts WHERE (role = "Operator") AND FIND_IN_SET(d.id, departments)) AS total_accounts_operators, 
    (SELECT COUNT(*) FROM accounts WHERE role = "Guest" AND FIND_IN_SET(d.id, departments)) AS total_accounts 
    FROM departments d ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Department created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Department updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Department deleted successfully!';
    }
}
// Determine the URL
$url = 'departments.php?search=' . $search;
?>
<?=template_admin_header('Departments', 'departments', 'view')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-building"></i>
        <div class="txt">
            <h2>Departments</h2>
            <p>View, manage, and search departments.</p>
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
    <a href="department.php" class="btn">Create Department</a>
    <?php else: ?>
    <div></div>
    <?php endif; ?>
    <form action="" method="get">
        <input type="hidden" name="page" value="accounts">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search department..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
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
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">Title<?php if ($order_by=='title'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_accounts_admins'?>"># Admins<?php if ($order_by=='total_accounts_admins'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <?php endif; ?>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_accounts_operators'?>"># Operators<?php if ($order_by=='total_accounts_operators'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_accounts'?>"># Users<?php if ($order_by=='total_accounts'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=created'?>">Created<?php if ($order_by=='created'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                <tr>
                    <td colspan="10" class="no-results">There are no departments.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($departments as $d): ?>
                <tr>
                    <td class="responsive-hidden"><?=$d['id']?></td>
                    <td><?=$d['title']?></td>
                    <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
                    <td><a href="accounts.php?department=<?=$d['id']?>&role=Admin" class="link1"><?=number_format($d['total_accounts_admins'])?></a></td>
                    <?php endif; ?>
                    <td><a href="accounts.php?department=<?=$d['id']?>&role=Operator" class="link1"><?=number_format($d['total_accounts_operators'])?></a></td>
                    <td><a href="accounts.php?department=<?=$d['id']?>&role=Guest" class="link1"><?=number_format($d['total_accounts'])?></a></td>
                    <td class="responsive-hidden"><?=date('Y-m-d H:ia', strtotime($d['created']))?></td>
                    <td>
                        <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
                        <a href="department.php?id=<?=$d['id']?>" class="link1">Edit</a>
                        <?php endif; ?>
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
    <span>Page <?=$pagination_page?> of <?=ceil($departments_total / $results_per_page) == 0 ? 1 : ceil($departments_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $departments_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>