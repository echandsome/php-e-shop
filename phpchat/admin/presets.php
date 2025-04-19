<?php
include 'main.php';
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','msg'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? ' AND (msg LIKE :search) ' : '';
// Retrieve the total number of presets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM presets WHERE (acc_id = :acc_id OR acc_id IS NULL) ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->bindParam('acc_id', $_SESSION['chat_account_id'], PDO::PARAM_INT);
$stmt->execute();
$presets_total = $stmt->fetchColumn();
// SQL query to get all presets from the "presets" table
$stmt = $pdo->prepare('SELECT * FROM presets WHERE (acc_id = :acc_id OR acc_id IS NULL) ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->bindParam('acc_id', $_SESSION['chat_account_id'], PDO::PARAM_INT);
$stmt->execute();
// Retrieve query results
$presets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Preset created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Preset updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Preset deleted successfully!';
    }
}
// Determine the URL
$url = 'presets.php?search=' . $search;
// Presets template below
?>
<?=template_admin_header('Presets', 'settings', 'presets')?>

<div class="content-title">
    <h2>Presets</h2>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>


<div class="content-header responsive-flex-column pad-top-5">
    <a href="preset.php" class="btn">Create Preset</a>
    <form action="" method="get">
        <input type="hidden" name="page" value="presets">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search preset..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
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
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=msg'?>">Message<?php if ($order_by=='msg'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($presets)): ?>
                <tr>
                    <td colspan="10" class="no-results">There are no presets.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($presets as $preset): ?>
                <tr>
                    <td><?=htmlspecialchars($preset['msg'], ENT_QUOTES)?></td>
                    <td>
                        <?php if ($_SESSION['chat_account_role'] == 'Admin' || $preset['acc_id'] == $_SESSION['chat_account_id']): ?>
                        <a href="preset.php?id=<?=$preset['id']?>" class="link1">Edit</a>
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
    <span>Page <?=$pagination_page?> of <?=ceil($presets_total / $results_per_page) == 0 ? 1 : ceil($presets_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $presets_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>