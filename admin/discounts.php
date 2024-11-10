<?php
defined('admin') or exit;
// Get the current date
$current_date = strtotime((new DateTime())->format('Y-m-d H:i:s'));
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Filters parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$active = isset($_GET['active']) ? $_GET['active'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','category_names','product_names','discount_code','discount_type','discount_value','start_date','end_date'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination pagination_page
$results_per_pagination_page = 20;
// discounts array
$discounts = [];
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_pagination_page;
$param2 = $results_per_pagination_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (d.discount_code LIKE :search OR d.discount_value LIKE :search OR d.discount_type LIKE :search) ' : '';
// Add filters
// Type filter
if ($type) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'd.discount_type = :type ';
}
// Active filter
if ($active) {
    // check if active based on the start and end date
    if ($active == 'yes') {
        $where .= ($where ? 'AND ' : 'WHERE ') . '(d.start_date <= :current_date AND d.end_date >= :current_date) ';
    } else {
        $where .= ($where ? 'AND ' : 'WHERE ') . '(d.start_date > :current_date OR d.end_date < :current_date) ';
    }
    $active_date = date('Y-m-d H:i:s');
}
// Retrieve the total number of discounts
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM discounts d ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($type) $stmt->bindParam('type', $type, PDO::PARAM_STR);
if ($active) $stmt->bindParam('current_date', $active_date, PDO::PARAM_STR);
$stmt->execute();
$total_discounts = $stmt->fetchColumn();
// Prepare discounts query
$stmt = $pdo->prepare('SELECT d.*, GROUP_CONCAT(DISTINCT p.title) product_names, GROUP_CONCAT(DISTINCT c.title) category_names FROM discounts d LEFT JOIN products p ON FIND_IN_SET(p.id, d.product_ids) LEFT JOIN categories c ON FIND_IN_SET(c.id, d.category_ids) ' . $where . ' GROUP BY d.id, d.category_ids, d.product_ids, d.discount_code, d.discount_type, d.discount_type, d.discount_value, d.start_date, d.end_date ORDER BY ' . $order_by . ' ' . $order . '  LIMIT :start_results,:num_results');
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($type) $stmt->bindParam('type', $type, PDO::PARAM_STR);
if ($active) $stmt->bindParam('current_date', $active_date, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Delete discounts
if (isset($_GET['delete'])) {
    // Delete the discounts
    $stmt = $pdo->prepare('DELETE FROM discounts WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    // Remove session discount code
    if (isset($_SESSION['discount'])) {
        unset($_SESSION['discount']);
    }
    header('Location: index.php?page=discounts&success_msg=3');
    exit;
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Discount created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Discount updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Discount deleted successfully!';
    }
}
// Create URL
$url = 'index.php?page=discounts&search_query=' . $search . '&type=' . $type . '&active=' . $active;
?>
<?=template_admin_header('Discounts', 'discounts', 'view')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>
        </div>
        <div class="txt">
            <h2>Discounts</h2>
            <p>View, edit, and create discounts.</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <a href="index.php?page=discount" class="btn">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create Discount
    </a>
    <form action="" method="get">
        <input type="hidden" name="page" value="discounts">
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label for="type">Type</label>
                <select name="type" id="type">
                    <option value=""<?=$type==''?' selected':''?>>All</option>
                    <option value="Percentage"<?=$type=='Percentage'?' selected':''?>>Percentage</option>
                    <option value="Fixed"<?=$type=='Fixed'?' selected':''?>>Fixed</option>
                </select>
                <label for="active">Active</label>
                <select name="active" id="active">
                    <option value=""<?=$active==''?' selected':''?>>All</option>
                    <option value="yes"<?=$active=='yes'?' selected':''?>>Yes</option>
                    <option value="no"<?=$active=='no'?' selected':''?>>No</option>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search discounts..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($type != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'type')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Type : <?=htmlspecialchars($type, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($active != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'active')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Active : <?=$active == 1 ? 'Yes' : 'No'?>
    </div>
    <?php endif; ?>
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>   
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=discount_code'?>">Code<?=$order_by=='discount_code' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td>Active</td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=category_names'?>">Categories<?=$order_by=='category_names' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=product_names'?>">Products<?=$order_by=='product_names' ? $table_icons[strtolower($order)] : ''?></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=discount_type'?>">Type<?=$order_by=='discount_type' ? $table_icons[strtolower($order)] : ''?></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=discount_value'?>">Value<?=$order_by=='discount_value' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=start_date'?>">Start Date<?=$order_by=='start_date' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=end_date'?>">End Date<?=$order_by=='end_date' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="align-center">Action</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$discounts): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no discounts.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($discounts as $discount): ?>
                <tr>
                    <td class="responsive-hidden"><?=$discount['id']?></td>
                    <td><?=$discount['discount_code']?></td>
                    <td>
                        <?php if ($current_date >= strtotime($discount['start_date']) && $current_date <= strtotime($discount['end_date'])): ?>
                        <svg stroke="#34aa6b" fill="#34aa6b" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" /></svg>
                        <?php else: ?>
                        <svg stroke="#b64343" fill="#b64343" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                        <?php endif; ?>
                    </td>
                    <td class="responsive-hidden" style="max-width:300px">
                    <?php
                    if (empty($discount['category_names'])) {
                        echo '<span class="grey">All</span>';
                    } else {
                        $categories = explode(',', $discount['category_names']);
                        foreach ($categories as $c) {
                            echo '<span class="grey mar-right-1 mar-bot-1">' . $c . '</span>';
                        }
                    }
                    ?>
                    </td>
                    <td class="responsive-hidden" style="max-width:300px">
                    <?php
                    if (empty($discount['product_names'])) {
                        echo '<span class="grey">All</span>';
                    } else {
                        $products = explode(',', $discount['product_names']);
                        foreach ($products as $p) {
                            echo '<span class="grey mar-right-1 mar-bot-1">' . $p . '</span>';
                        }
                    }
                    ?>
                    </td>
                    <td><?=$discount['discount_type']?></td>
                    <td><?=$discount['discount_value']?></td>
                    <td class="responsive-hidden alt"><?=date('Y-m-d h:ia', strtotime($discount['start_date']))?></td>
                    <td class="responsive-hidden alt"><?=date('Y-m-d h:ia', strtotime($discount['end_date']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="index.php?page=discount&id=<?=$discount['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="index.php?page=discounts&delete=<?=$discount['id']?>" onclick="return confirm('Are you sure you want to delete this discount?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                    </span>    
                                    Delete
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($total_discounts / $results_per_pagination_page) == 0 ? 1 : ceil($total_discounts / $results_per_pagination_page)?></span>
    <?php if ($pagination_page * $results_per_pagination_page < $total_discounts): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>