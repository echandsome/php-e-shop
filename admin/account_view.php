<?php
defined('shoppingcart_admin') or exit;
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Filters parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$method = isset($_GET['method']) ? $_GET['method'] : '';
$account_id = isset($_GET['account_id']) ? $_GET['account_id'] : '';
// If no account ID specified
if (!$account_id) {
    exit('Account ID not specified!');
}
// Retrieve account
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $account_id ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','first_name','total_products','payment_amount','payment_method','payment_status','created','payer_email'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination pagination_page
$results_per_pagination_page = 15;
// orders array
$orders = [];
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_pagination_page;
$param2 = $results_per_pagination_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (t.first_name LIKE :search OR t.last_name LIKE :search OR t.id LIKE :search OR t.txn_id LIKE :search OR t.payer_email LIKE :search)  ' : '';
// Add filters
// Status filter
if ($status) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 't.payment_status = :status ';
}
// Method filter
if ($method) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 't.payment_method = :method ';
}
// Retrieve the total number of orders
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT t.id) AS total FROM transactions t JOIN accounts a ON a.id = t.account_id AND a.id = :account_id LEFT JOIN transaction_items ti ON ti.txn_id = t.txn_id ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($method) $stmt->bindParam('method', $method, PDO::PARAM_STR);
if ($account_id) $stmt->bindParam('account_id', $account_id, PDO::PARAM_INT);
$stmt->execute();
$total_orders = $stmt->fetchColumn();
// Prepare orders query
$stmt = $pdo->prepare('SELECT t.*, COUNT(ti.id) AS total_products FROM transactions t JOIN accounts a ON a.id = t.account_id AND a.id = :account_id LEFT JOIN transaction_items ti ON ti.txn_id = t.txn_id ' . $where . ' GROUP BY t.id, t.txn_id, t.payment_amount, t.payment_status, t.created, t.payer_email, t.first_name, t.last_name, t.address_street, t.address_city, t.address_state, t.address_zip, t.address_country, t.account_id, t.payment_method, t.discount_code, t.shipping_method, t.shipping_amount, t.tax_amount ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($method) $stmt->bindParam('method', $method, PDO::PARAM_STR);
if ($account_id) $stmt->bindParam('account_id', $account_id, PDO::PARAM_INT);
$stmt->execute();
// Retrieve query results
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Create URL
$url = 'index.php?page=account_view&search_query=' . $search . '&status=' . $status . '&method=' . $method . '&account_id=' . $account_id;
?>
<?=template_admin_header('Account #' . $account['id'], 'accounts', 'view')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>
        </div>
        <div class="txt">
            <h2>Account #<?=$account['id']?></h2>
            <p>View account details, orders, and more</p>
        </div>
    </div>
</div>

<div class="content-block-wrapper">

    <div class="content-block order-details">
        <div class="block-header">
            <div class="content-left">
                <div class="icon">
                    <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                </div>
                Account Details
            </div>
        </div>
        <div class="group pad-2">
            <div class="item responsive-width-100">
                <div class="order-detail alt">
                    <h3>Email</h3>
                    <p><?=htmlspecialchars($account['email'], ENT_QUOTES)?></p>
                </div>
                <div class="order-detail alt">
                    <h3>Name</h3>
                    <p><?=htmlspecialchars($account['first_name'], ENT_QUOTES)?> <?=htmlspecialchars($account['last_name'], ENT_QUOTES)?></p>
                </div>
                <div class="order-detail alt">
                    <h3>Role</h3>
                    <p><?=htmlspecialchars($account['role'], ENT_QUOTES)?></p>
                </div>
            </div>
            <div class="item responsive-width-100">
                <div class="order-detail alt">
                    <h3>Address</h3>
                    <p><?=nl2br(htmlspecialchars(implode(PHP_EOL, array_filter([$account['address_street'], $account['address_city'], $account['address_state'], $account['address_zip'], $account['address_country']])), ENT_QUOTES))?></p>
                </div>
            </div>
            <div class="item responsive-width-100">
                <div class="order-detail alt">
                    <h3>Registered</h3>
                    <p><?=htmlspecialchars($account['registered'], ENT_QUOTES)?></p>
                </div>
                <div class="order-detail alt">
                    <h3># Orders</h3>
                    <p><?=num_format($total_orders)?></p>
                </div>            
            </div>
        </div>
    </div>

</div>

<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
        <a href="index.php?page=account&id=<?=$account['id']?>" class="btn mar-right-2">
            <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"/></svg>
            Edit Account
        </a>
        <a href="index.php?page=order_manage&account_id=<?=$account['id']?>" class="btn">
            <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
            Create Order
        </a>
    </div>
    <form method="get">
        <input type="hidden" name="page" value="account_view">
        <?php if ($account_id != ''): ?>
        <input type="hidden" name="account_id" value="<?=$account_id?>">
        <?php endif; ?>
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value=""<?=$status==''?' selected':''?>>All</option>
                    <option value="Completed"<?=$status=='Completed'?' selected':''?>>Completed</option>
                    <option value="Pending"<?=$status=='Pending'?' selected':''?>>Pending</option>
                    <option value="Cancelled"<?=$status=='Cancelled'?' selected':''?>>Cancelled</option>
                    <option value="Reversed"<?=$status=='Reversed'?' selected':''?>>Reversed</option>
                    <option value="Failed"<?=$status=='Failed'?' selected':''?>>Failed</option>
                    <option value="Refunded"<?=$status=='Refunded'?' selected':''?>>Refunded</option>
                    <option value="Shipped"<?=$status=='Shipped'?' selected':''?>>Shipped</option>
                    <option value="Subscribed"<?=$status=='Subscribed'?' selected':''?>>Subscribed</option>
                    <option value="Unsubscribed"<?=$status=='Unsubscribed'?' selected':''?>>Unsubscribed</option>
                </select>
                <label for="method">Method</label>
                <select name="method" id="method">
                    <option value=""<?=$method==''?' selected':''?>>All</option>
                    <option value="website"<?=$method=='website'?' selected':''?>>Website</option>
                    <option value="paypal"<?=$method=='paypal'?' selected':''?>>PayPal</option>
                    <option value="stripe"<?=$method=='stripe'?' selected':''?>>Stripe</option>
                    <option value="coinbase"<?=$method=='coinbase'?' selected':''?>>Coinbase</option>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search orders..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($status != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'status')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Status : <?=htmlspecialchars($status, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($method != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'method')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Method : <?=htmlspecialchars($method, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>   
</div>

<div class="content-block no-pad">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=first_name'?>">Customer<?=$order_by=='first_name' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=payer_email'?>">Email<?=$order_by=='payer_email' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_products'?>">Products<?=$order_by=='total_products' ? $table_icons[strtolower($order)] : ''?></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=payment_amount'?>">Total<?=$order_by=='payment_amount' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=payment_method'?>">Method<?=$order_by=='payment_method' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=payment_status'?>">Status<?=$order_by=='payment_status' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=created'?>">Date<?=$order_by=='created' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="align-center">Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$orders): ?>
                <tr>
                    <td colspan="20" class="no-results">This account hasn't made any orders.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?=$o['id']?></td>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($o['first_name'])?>"><?=strtoupper(substr($o['first_name'], 0, 1))?></span>
                        </div>
                    </td>
                    <td><a href="#" class="view-customer-details link1" data-transaction-id="<?=$o['id']?>"><?=htmlspecialchars($o['first_name'], ENT_QUOTES)?> <?=htmlspecialchars($o['last_name'], ENT_QUOTES)?></a></td>
                    <td class="responsive-hidden alt"><?=htmlspecialchars($o['payer_email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><span class="grey small"><?=$o['total_products']?></span></td>
                    <td class="strong"><?=currency_code?><?=num_format($o['payment_amount'], 2)?></td>
                    <td class="responsive-hidden"><span class="grey"><?=$o['payment_method']?></span></td>
                    <td class="responsive-hidden"><span class="<?=str_replace(['completed','pending','cancelled','reversed','failed','refunded','shipped','unsubscribed','subscribed'],['green','orange','red','red','red','red','green','red','blue'], strtolower($o['payment_status']))?>"><?=$o['payment_status']?></span></td>
                    <td class="responsive-hidden alt"><?=date('F j, Y', strtotime($o['created']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="index.php?page=order&id=<?=$o['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>
                                    </span>
                                    View
                                </a>
                                <a href="index.php?page=order_manage&id=<?=$o['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="index.php?page=orders&delete=<?=$o['id']?>" onclick="return confirm('Are you sure you want to delete this order?')">
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
    <span>Page <?=$pagination_page?> of <?=ceil($total_orders / $results_per_pagination_page) == 0 ? 1 : ceil($total_orders / $results_per_pagination_page)?></span>
    <?php if ($pagination_page * $results_per_pagination_page < $total_orders): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>