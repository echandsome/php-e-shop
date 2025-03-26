<?php
// Remove time limit
set_time_limit(0);
// Include the necessary files
include '../config.php';
include '../functions.php';
// Retrieve input data
$payload = file_get_contents('php://input');
if (empty($payload)) {
    exit('No payload received!');
}
// Decode the JSON payload
$coinbase = json_decode($payload, true);
if (!is_array($coinbase)) {
    exit('Invalid JSON payload!');
}
// Check if event and key exist
if (!isset($_GET['key'], $coinbase['event'])) {
    exit('Required parameters missing!');
}
// Validate the key
if ($_GET['key'] != coinbase_secret) {
    exit('Invalid key! Please set the correct key defined in the config.php file!');
}
// Validate the key and process known event types
if ($_GET['key'] == coinbase_secret) {
    $event_type = $coinbase['event']['type'];
    $pdo = pdo_connect_mysql();
    // Handle refunded event
    if ($event_type == 'charge:refunded') {
        $id = $coinbase['event']['data']['id'];
        // Update the transaction status to "Refunded"
        $stmt = $pdo->prepare('UPDATE transactions SET payment_status = ? WHERE txn_id = ?');
        $stmt->execute(['Refunded', $id]);
        exit;
    }
    // Handle confirmed or resolved events (successful transactions)
    if ($event_type == 'charge:confirmed' || $event_type == 'charge:resolved') {
        $id = $coinbase['event']['data']['id'];
        $data = $coinbase['event']['data']['metadata'];
        $products_in_cart = [];
        // Iterate over cart items
        for ($i = 1; $i < (intval($data['num_cart_items']) + 1); $i++) {
            // Update product quantity
            $stmt = $pdo->prepare('UPDATE products SET quantity = GREATEST(quantity - ?, 0) WHERE quantity > 0 AND id = ?');
            $stmt->execute([$data['qty_' . $i], $data['item_' . $i]]);
            // Product related variables
            $option = $data['option_' . $i];
            $item_price = floatval($data['amount_' . $i]);
            // Deduct option quantities
            if ($option) {
                $options = explode(',', $option);
                foreach ($options as $opt) {
                    $option_name = explode('-', $opt)[0];
                    $option_value = explode('-', $opt)[1];
                    $stmt = $pdo->prepare('UPDATE product_options SET quantity = GREATEST(quantity - ?, 0) WHERE quantity > 0 AND option_name = ? AND (option_value = ? OR option_value = "") AND product_id = ?');
                    $stmt->execute([$data['qty_' . $i], $option_name, $option_value, $data['item_' . $i]]);
                }
            }
            // Insert into transaction_items table
            $stmt = $pdo->prepare('INSERT INTO transaction_items (txn_id, item_id, item_price, item_quantity, item_options) VALUES (?,?,?,?,?)');
            $stmt->execute([$id, $data['item_' . $i], $item_price, $data['qty_' . $i], $option]);
            // Add product to array
            $products_in_cart[] = [
                'id' => $data['item_' . $i],
                'quantity' => $data['qty_' . $i],
                'options' => $option,
                'final_price' => $item_price,
                'meta' => [
                    'title' => $data['item_name_' . $i],
                    'price' => $item_price
                ]
            ];
        }
        // Insert or update transaction record
        $stmt = $pdo->prepare('INSERT INTO transactions (txn_id, payment_amount, payment_status, created, payer_email, first_name, last_name, address_street, address_city, address_state, address_zip, address_country, account_id, payment_method, shipping_method, shipping_amount, discount_code, tax_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE payment_status = VALUES(payment_status)');
        $stmt->execute([
            $id,
            floatval($coinbase['event']['data']['pricing']['local']['amount']),
            default_payment_status,
            date('Y-m-d H:i:s'),
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['address_street'],
            $data['address_city'],
            $data['address_state'],
            $data['address_zip'],
            $data['address_country'],
            $data['account_id'],
            'coinbase',
            $data['shipping_method'],
            floatval($data['shipping']),
            $data['discount_code'],
            floatval($data['tax'])
        ]);
        // Get order ID
        $order_id = $pdo->lastInsertId();
        // Send order details to the customer's email address
        send_order_details_email($data['payer_email'], $products_in_cart, $data['first_name'], $data['last_name'], $data['address_street'], $data['address_city'], $data['address_state'], $data['address_zip'], $data['address_country'], floatval($coinbase['event']['data']['pricing']['local']['amount']), $order_id);
    }
}
?>