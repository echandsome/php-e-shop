<?php
defined('shoppingcart_admin') or exit;
if (!isset($_GET['id'])) {
    exit('Invalid ID!');
}
// Retrieve order items
$stmt = $pdo->prepare('SELECT ti.*, p.title, p.sku FROM transactions t JOIN transaction_items ti ON ti.txn_id = t.txn_id LEFT JOIN products p ON p.id = ti.item_id WHERE t.id = ?');
$stmt->execute([ $_GET['id'] ]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve order details
$stmt = $pdo->prepare('SELECT a.email, a.id AS a_id, a.first_name AS a_first_name, a.last_name AS a_last_name, a.address_street AS a_address_street, a.address_city AS a_address_city, a.address_state AS a_address_state, a.address_zip AS a_address_zip, a.address_country AS a_address_country, t.* FROM transactions t LEFT JOIN transaction_items ti ON ti.txn_id = t.txn_id LEFT JOIN accounts a ON a.id = t.account_id WHERE t.id = ?');
$stmt->execute([ $_GET['id'] ]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
// Delete transaction
if (isset($_GET['delete'])) {
    // Delete the transaction
    $stmt = $pdo->prepare('DELETE t, ti FROM transactions t LEFT JOIN transaction_items ti ON ti.txn_id = t.txn_id WHERE t.id = ?');
    $stmt->execute([ $_GET['id'] ]);
    header('Location: index.php?page=orders&success_msg=3');
    exit;
}
if (!$order) {
    exit('Invalid ID!');
}
?>
<?=template_admin_header('Orders', 'orders')?>

<div class="content-title">
    <h2 class="responsive-width-100 normal">
            <a href="index.php?page=orders">
                Orders
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.59,7.41L7,6L13,12L7,18L5.59,16.59L10.17,12L5.59,7.41M11.59,7.41L13,6L19,12L13,18L11.59,16.59L16.17,12L11.59,7.41Z" /></svg>
            </a>
            Order #<?=$_GET['id']?>
            <span class="<?=str_replace(['completed','pending','cancelled','reversed','failed','refunded','shipped','unsubscribed','subscribed'],['green','orange','red','red','red','red','green','red','blue'], strtolower($order['payment_status']))?>"><?=$order['payment_status']?></span>
        </h2>
    <div class="btns">
        <a href="index.php?page=orders" class="btn alt mar-right-1">Cancel</a>
        <a href="index.php?page=order&id=<?=$_GET['id']?>&delete=true" class="btn red mar-right-1" onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
        <a href="index.php?page=order_manage&id=<?=$_GET['id']?>" class="btn">Edit</a>
    </div>
</div>

<div class="content-block-wrapper">
    <div class="content-block order-details">
        <div class="block-header">
            <div class="content-left">
                <div class="icon">
                    <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>
                </div>
                Order Details
            </div>
        </div>
        <div class="order-detail">
            <h3>Order ID</h3>
            <p><?=$order['id']?></p>
        </div>
        <div class="order-detail">
            <h3>Transaction ID</h3>
            <p><?=$order['txn_id']?></p>
        </div>
        <?php if ($order['shipping_method']): ?>
        <div class="order-detail">
            <h3>Shipping Method</h3>
            <p><?=$order['shipping_method'] ? htmlspecialchars($order['shipping_method'], ENT_QUOTES) : '--'?></p>
        </div>
        <?php endif; ?>
        <div class="order-detail">
            <h3>Payment Method</h3>
            <p><?=$order['payment_method']?></p>
        </div>
        <div class="order-detail">
            <h3>Date</h3>
            <p><?=date('F j, Y H:ia', strtotime($order['created']))?></p>
        </div>
        <?php if ($order['discount_code']): ?>
        <div class="order-detail">
            <h3>Discount Code</h3>
            <p><?=htmlspecialchars($order['discount_code'], ENT_QUOTES)?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="content-block order-details">
        <div class="block-header">
            <div class="content-left">
                <div class="icon">
                    <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                </div>
                Account Details
            </div>
        </div>
        <?php if ($order['email']): ?>
        <div class="order-detail">
            <h3>Email</h3>
            <p><a href="index.php?page=account&id=<?=$order['a_id']?>" target="_blank" class="link1" style="margin:0"><?=htmlspecialchars($order['email'], ENT_QUOTES)?></a></p>
        </div>
        <div class="order-detail">
            <h3>Name</h3>
            <p><?=htmlspecialchars($order['a_first_name'], ENT_QUOTES)?> <?=htmlspecialchars($order['a_last_name'], ENT_QUOTES)?></p>
        </div>
        <div class="order-detail">
            <h3>Address</h3>
            <p style="text-align:right;"><?=nl2br(htmlspecialchars(implode(PHP_EOL, array_filter([$order['a_address_street'], $order['a_address_city'], $order['a_address_state'], $order['a_address_zip'], $order['a_address_country']])), ENT_QUOTES))?></p>
        </div>
        <?php else: ?>
        <p>The order is not associated with an account.</p>
        <?php endif; ?>
    </div>

    <div class="content-block order-details">
        <div class="block-header">
            <div class="content-left">
                <div class="icon">
                    <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                </div>
                Customer Details
            </div>
        </div>
        <div class="order-detail">
            <h3>Email</h3>
            <p><?=htmlspecialchars($order['payer_email'], ENT_QUOTES)?></p>
        </div>
        <div class="order-detail">
            <h3>Name</h3>
            <p><?=htmlspecialchars($order['first_name'], ENT_QUOTES)?> <?=htmlspecialchars($order['last_name'], ENT_QUOTES)?></p>
        </div>
        <div class="order-detail">
            <h3>Address</h3>
            <p style="text-align:right;"><?=htmlspecialchars($order['address_street'], ENT_QUOTES)?><br>
                <?=htmlspecialchars($order['address_city'], ENT_QUOTES)?><br>
                <?=htmlspecialchars($order['address_state'], ENT_QUOTES)?><br>
                <?=htmlspecialchars($order['address_zip'], ENT_QUOTES)?><br>
                <?=htmlspecialchars($order['address_country'], ENT_QUOTES)?>
            </p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="block-header">
        <div class="content-left">
            <div class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z" /></svg>
            </div>
            Order
        </div>
    </div>
    <div class="table order-table">
        <table>
            <thead>
                <tr>
                    <td>Product</td>
                    <td class="responsive-hidden">SKU</td>
                    <td>Options</td>
                    <td>Qty</td>
                    <td class="responsive-hidden">Price</td>
                    <td style="text-align:right;">Total</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($order_items)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no order items.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?=$item['title'] ? htmlspecialchars($item['title'], ENT_QUOTES) : '(Product ' . $item['item_id'] . ')'?></td>
                    <td class="responsive-hidden alt"><?=$item['sku'] ? htmlspecialchars($item['sku'], ENT_QUOTES) : '--'?></td>
                    <td><?=$item['item_options'] ? htmlspecialchars(str_replace(',', ', ', $item['item_options']), ENT_QUOTES) : '--'?></td>
                    <td><?=$item['item_quantity']?></td>
                    <td class="responsive-hidden"><?=currency_code?><?=num_format($item['item_price'], 2)?></td>
                    <td style="text-align:right;"><?=currency_code?><?=num_format($item['item_price']*$item['item_quantity'], 2)?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <tr class="nobg">
                    <td colspan="6" class="item-list-end"></td>
                </tr>
                <tr class="nobg">
                    <td colspan="5" class="subtotal">Subtotal</td>
                    <td class="num"><?=currency_code?><?=num_format($order['payment_amount']-$order['shipping_amount'], 2)?></td>
                </tr>
                <tr class="nobg">
                    <td colspan="5" class="shipping">Shipping</td>
                    <td class="num"><?=currency_code?><?=num_format($order['shipping_amount'], 2)?></td>
                </tr>
                <tr class="nobg">
                    <td colspan="5" class="tax">Tax</td>
                    <td class="num"><?=currency_code?><?=num_format($order['tax_amount'], 2)?></td>
                </tr>
                <tr class="nobg">
                    <td colspan="5" class="total">Total</td>
                    <td class="num"><?=currency_code?><?=num_format($order['payment_amount'], 2)?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?=template_admin_footer()?>