<?php
defined('shoppingcart_admin') or exit;
// Current date in MySQL DATETIME format
$date = date('Y-m-d H:i:s');
// SQL query that will get all orders and sort by the date created
$orders = $pdo->query('SELECT t.*, COUNT(ti.id) AS total_products FROM transactions t JOIN transaction_items ti ON ti.txn_id = t.txn_id WHERE cast(t.created as DATE) = cast("' . $date . '" as DATE) GROUP BY t.id, t.txn_id, t.payment_amount, t.payment_status, t.created, t.payer_email, t.first_name, t.last_name, t.address_street, t.address_city, t.address_state, t.address_zip, t.address_country, t.account_id, t.payment_method, t.discount_code, t.shipping_method, t.shipping_amount, t.tax_amount ORDER BY t.created DESC')->fetchAll(PDO::FETCH_ASSOC);
// SQL query that will get all accounts created today
$accounts = $pdo->query('SELECT a.*, (SELECT COUNT(*) FROM transactions t WHERE t.account_id = a.id) AS orders FROM accounts a WHERE cast(registered as DATE) = cast("' . $date . '" as DATE)')->fetchAll(PDO::FETCH_ASSOC);
// Get the orders statistics
$order_stats = $pdo->query('SELECT SUM(payment_amount) AS earnings FROM transactions WHERE (payment_status = "Completed" OR payment_status = "Subscribed") AND cast(created as DATE) = cast("' . $date . '" as DATE)')->fetch(PDO::FETCH_ASSOC);
// Get the total number of accounts
$total_accounts = $pdo->query('SELECT COUNT(*) AS total FROM accounts')->fetchColumn();
// Get the total number of products
$total_products = $pdo->query('SELECT COUNT(*) AS total FROM products')->fetchColumn();
// Get the top selling products
$top_products = $pdo->query('SELECT p.title, p.sku, SUM(ti.item_quantity) AS total_sales, SUM(ti.item_quantity * ti.item_price) AS total_earnings, (SELECT m.full_path FROM product_media_map pm JOIN product_media m ON m.id = pm.media_id WHERE pm.product_id = p.id ORDER BY pm.position ASC LIMIT 1) AS img FROM products p JOIN transaction_items ti ON ti.item_id = p.id GROUP BY p.id, p.title, p.sku ORDER BY total_sales DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
// Get sales analytics
if (isset($_GET['date_start'], $_GET['date_end'])) {
    $start = new DateTime($_GET['date_start']);
    $end = new DateTime($_GET['date_end']);
    if ($end <= $start) {
        $end = new DateTime($_GET['date_start']);
    }
} else {
    $start = new DateTime();         
    $start->modify('-6 days'); 
    $end = new DateTime();
}
$end->modify('+1 day');
$period = new DatePeriod($start, new DateInterval('P1D'), $end);
$allDates = [];
foreach ($period as $dt) {
    $allDates[$dt->format('Y-m-d')] = 0;
}
$sale_analytics_total_sales = 0;
$sale_analytics_total_earnings = 0;
$sale_results = $pdo->query('SELECT DATE(created) AS date, COUNT(*) AS sales, SUM(payment_amount) AS earnings FROM transactions WHERE created BETWEEN "' . $start->format('Y-m-d 00:00:00') . '" AND "' . $end->format('Y-m-d 23:59:59') . '" GROUP BY DATE(created)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($sale_results as $row) {
    $allDates[$row['date']] = (int)$row['sales'];
    $sale_analytics_total_sales += (int)$row['sales'];
    $sale_analytics_total_earnings += (float)$row['earnings'];
}
$stats = [];
foreach ($allDates as $date => $sales) {
    $stats[] = ['date' => $date, 'sales' => $sales];
}
$max_sales = max(array_column($stats, 'sales'));
$max_sales = $max_sales < 15 ? 15 : $max_sales;
$num_increments = 5;
$raw_step = $max_sales / $num_increments;
if ($raw_step <= 5) {
    $step = 5;
} elseif ($raw_step <= 10) {
    $step = 10;
} elseif ($raw_step <= 20) {
    $step = 10 * ceil($raw_step / 10);
} else {
    $step = 10 * ceil($raw_step / 10);
}
$increments = [];
for ($i = 0; $i <= $max_sales; $i += $step) {
    $increments[] = $i;
}
if (end($increments) < $max_sales) {
    $increments[] = end($increments) + $step;
}
?>
<?=template_admin_header('Dashboard', 'dashboard')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
        </div>
        <div class="txt">
            <h2>Dashboard</h2>
            <p>View statistics, today's orders, and more</p>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>New Orders <span>today</span></h3>
            <p><?=num_format(count($orders))?></p>
        </div>
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>
        </div>    
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total orders for today
        </div>
    </div>

    <div class="content-block stat green">
        <div class="data">
            <h3>New Sales <span>today</span></h3>
            <p><?=currency_code?><?=num_format($order_stats['earnings'], 2)?></p>
        </div>
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2l0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5V176c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336V300.6c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4V304v5.7V336zm32 0V304 278.1c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5V272c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5V432c0 44.2-86 80-192 80S0 476.2 0 432V396.6c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z"/></svg>
        </div>    
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total earnings for today
        </div>
    </div>

    <div class="content-block stat cyan">
        <div class="data">
            <h3>Total Accounts</h3>
            <p><?=num_format($total_accounts)?></p>
        </div>
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total accounts
        </div>
    </div>

    <div class="content-block stat red">
        <div class="data">
            <h3>Total Products</h3>
            <p><?=num_format($total_products)?></p>
        </div>
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M248 0H208c-26.5 0-48 21.5-48 48V160c0 35.3 28.7 64 64 64H352c35.3 0 64-28.7 64-64V48c0-26.5-21.5-48-48-48H328V80c0 8.8-7.2 16-16 16H264c-8.8 0-16-7.2-16-16V0zM64 256c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H224c35.3 0 64-28.7 64-64V320c0-35.3-28.7-64-64-64H184v80c0 8.8-7.2 16-16 16H120c-8.8 0-16-7.2-16-16V256H64zM352 512H512c35.3 0 64-28.7 64-64V320c0-35.3-28.7-64-64-64H472v80c0 8.8-7.2 16-16 16H408c-8.8 0-16-7.2-16-16V256H352c-15 0-28.8 5.1-39.7 13.8c4.9 10.4 7.7 22 7.7 34.2V464c0 12.2-2.8 23.8-7.7 34.2C323.2 506.9 337 512 352 512z"/></svg>
        </div>    
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total products
        </div>
    </div>
</div>

<div class="content-block-wrapper" style="padding:0;">
    <div class="content-block" style="min-width:65%">
        <div class="block-header">
            <div class="content-left">
                <div class="icon">
                    <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3,22V8H7V22H3M10,22V2H14V22H10M17,22V14H21V22H17Z" /></svg>
                </div>
                Sales Analytics
            </div>
            <div class="content-right">
                <form action="index.php?page=dashboard" method="get" class="date-range">
                    <input type="hidden" name="page" value="dashboard">
                    <input type="date" name="date_start" value="<?=$start->format('Y-m-d')?>" onchange="this.form.submit()">
                    <span>to</span>
                    <input type="date" name="date_end" value="<?=$end->format('Y-m-d')?>" onchange="this.form.submit()">
                </form>
            </div>
        </div>
        <div class="sales-chart bar-chart">
            <div class="chart-stats">
                <div class="data">
                    <h3>Orders</h3>
                    <p><?=num_format($sale_analytics_total_sales)?>
                </div>
                <div class="data">
                    <h3>Earnings</h3>
                    <p>&dollar;<?=num_format($sale_analytics_total_earnings, 2)?>
                </div>
            </div>
            <div class="chart-wrapper">
                <div class="chart-scale">
                    <?php foreach ($increments as $inc): ?>
                    <?php $pos = $inc ? ($inc / end($increments)) * 100 : 0; ?>
                    <?php if ($pos == 0) continue; ?>
                    <div style="bottom:calc(<?=$pos?>% - 19px)"><?=$inc?></div>
                    <?php endforeach; ?>
                </div>
                <div class="chart-container">
                    <?php foreach ($increments as $inc): ?>
                    <?php $pos = $inc ? ($inc / end($increments)) * 100 : 0; ?>
                    <?php if ($pos == 0) continue; ?>
                    <div class="chart-line" style="bottom:<?=$pos?>%"></div>
                    <?php endforeach; ?>
                    <?php foreach ($stats as $day): ?>
                    <?php $height_percentage = $day['sales'] ? ($day['sales'] / end($increments)) * 100 : 0; ?>
                    <div class="chart-bar" style="height:<?=$height_percentage?>%;">
                        <?php if ($day['sales']): ?>
                        <span><?=date('M j', strtotime($day['date']))?>: <?=$day['sales']?> order<?=$day['sales']==1?'':'s'?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (count($stats) <= 7): ?>
            <div class="chart-wrapper">
                <div class="chart-labels">
                    <?php foreach ($stats as $day): ?>
                    <div><?=date('M j', strtotime($day['date']))?></div>
                    <?php endforeach; ?>
                </div>
            </div>  
            <?php endif; ?>
        </div>
    </div>
    <div class="content-block">
        <div class="block-header" style="margin-bottom:0">
            <div class="content-left">
                <div class="icon">
                    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16,6L18.29,8.29L13.41,13.17L9.41,9.17L2,16.59L3.41,18L9.41,12L13.41,16L19.71,9.71L22,12V6H16Z" /></svg>
                </div>
                Top Selling 
            </div>
        </div>
        <div class="top-sellers">
            <div class="table">
                <table>
                    <thead>
                        <tr>
                            <td colspan="2">Product</td>
                            <td>Sales</td>
                            <td class="right">Earnings</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                        <tr>
                            <td colspan="20" class="no-results">There are no top selling products.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td class="img">
                                <div class="profile-img">
                                    <?php if (!empty($product['img'])): ?>
                                    <?php if (file_exists('../'.$product['img'])): ?>
                                    <img src="../<?=$product['img']?>" width="32" height="32" alt="<?=$product['img']?>">
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="title-caption">
                                <?=$product['title']?>
                                <?php if ($product['sku']): ?>
                                <span><?=$product['sku']?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="grey small"><?=$product['total_sales']?></span></td>
                            <td class="strong right"><?=currency_code?><?=num_format($product['total_earnings'], 2)?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="content-title" style="margin-top:40px">
    <div class="title">
        <div class="icon alt">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 80c-8.8 0-16 7.2-16 16V416c0 8.8 7.2 16 16 16H512c8.8 0 16-7.2 16-16V96c0-8.8-7.2-16-16-16H64zM0 96C0 60.7 28.7 32 64 32H512c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zm96 64a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm104 0c0-13.3 10.7-24 24-24H448c13.3 0 24 10.7 24 24s-10.7 24-24 24H224c-13.3 0-24-10.7-24-24zm0 96c0-13.3 10.7-24 24-24H448c13.3 0 24 10.7 24 24s-10.7 24-24 24H224c-13.3 0-24-10.7-24-24zm0 96c0-13.3 10.7-24 24-24H448c13.3 0 24 10.7 24 24s-10.7 24-24 24H224c-13.3 0-24-10.7-24-24zm-72-64a32 32 0 1 1 0-64 32 32 0 1 1 0 64zM96 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
        </div>
        <div class="txt">
            <h2>Today's Orders</h2>
            <p>List of all orders placed today</p>
        </div>
    </div>
</div>

<div class="content-block no-pad">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2">Customer</td>
                    <td class="responsive-hidden">Email</td>
                    <td class="responsive-hidden">Products</td>
                    <td>Total</td>
                    <td class="responsive-hidden">Method</td>
                    <td class="responsive-hidden">Status</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no recent orders.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($order['first_name'])?>"><?=strtoupper(substr($order['first_name'], 0, 1))?></span>
                        </div>
                    </td>
                    <td><a href="#" class="view-customer-details link1" data-transaction-id="<?=$order['id']?>"><?=htmlspecialchars($order['first_name'], ENT_QUOTES)?> <?=htmlspecialchars($order['last_name'], ENT_QUOTES)?></a></td>
                    <td class="responsive-hidden alt"><?=htmlspecialchars($order['payer_email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><span class="grey small"><?=$order['total_products']?></span></td>
                    <td class="strong"><?=currency_code?><?=num_format($order['payment_amount'], 2)?></td>
                    <td class="responsive-hidden alt"><span class="grey"><?=$order['payment_method']?></span></td>
                    <td class="responsive-hidden"><span class="<?=str_replace(['completed','pending','cancelled','reversed','failed','refunded','shipped','unsubscribed','subscribed'],['green','orange','red','red','red','red','green','red','blue'], strtolower($order['payment_status']))?>"><?=$order['payment_status']?></span></td>
                    <td class="responsive-hidden alt"><?=date('F j, Y', strtotime($order['created']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="index.php?page=order&id=<?=$order['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>
                                    </span>
                                    View
                                </a>
                                <a href="index.php?page=order_manage&id=<?=$order['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="index.php?page=orders&delete=<?=$order['id']?>" onclick="return confirm('Are you sure you want to delete this order?')">
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-title" style="margin-top:40px">
    <div class="title">
        <div class="icon alt">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312V248H440c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V136c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H552v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
        </div>
        <div class="txt">
            <h2>New Accounts</h2>
            <p>Accounts created today</p>
        </div>
    </div>
</div>

<div class="content-block no-pad">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2">Name</td>
                    <td class="responsive-hidden">Email</td>
                    <td class="responsive-hidden">Role</td>
                    <td class="responsive-hidden"># Orders</td>
                    <td class="responsive-hidden">Registered Date</td>
                    <td class="align-center">Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$accounts): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no new accounts.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($account['first_name'])?>"><?=strtoupper(substr($account['first_name'], 0, 1))?></span>
                        </div>
                    </td>
                    <td><?=htmlspecialchars($account['first_name'] . ' ' . $account['last_name'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden alt"><?=htmlspecialchars($account['email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><span class="<?=str_replace(['Admin', 'Member'], ['red', 'blue'], $account['role'])?> small"><?=$account['role']?></span></td>
                    <td class="responsive-hidden"><a href="index.php?page=orders&account_id=<?=$account['id']?>" class="link1"><?=num_format($account['orders'])?></a></td>
                    <td class="responsive-hidden alt"><?=date('Y-m-d H:ia', strtotime($account['registered']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="index.php?page=account&id=<?=$account['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="index.php?page=accounts&delete=<?=$account['id']?>" onclick="return confirm('Are you sure you want to delete this account?')">
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

<?=template_admin_footer()?>