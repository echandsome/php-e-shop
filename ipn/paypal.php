<?php
// Remove time limit
set_time_limit(0);
// Include the necessary files
include '../config.php';
include '../functions.php';
// Get all input variables and convert them all to URL string variables
$raw_post_data = file_get_contents('php://input');
if (empty($raw_post_data)) exit;
$raw_post_array = explode('&', $raw_post_data);
$post_data = [];
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2) {
		if ($keyval[0] === 'payment_date') {
			if (substr_count($keyval[1], '+') === 1) {
				$keyval[1] = str_replace('+', '%2B', $keyval[1]);
			}
		}
		$post_data[$keyval[0]] = urldecode($keyval[1]);
	}
}
$req = 'cmd=_notify-validate';
foreach ($post_data as $key => $value) {
	$value = urlencode($value);
    $req .= "&$key=$value";
}
// Below will verify the transaction with PayPal
$ch = curl_init(paypal_testmode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr');
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
$res = curl_exec($ch);
curl_close($ch);
// Check if the transaction is verified
if (strcmp($res, 'VERIFIED') == 0) {
    // Transaction is verified and successful...
    $pdo = pdo_connect_mysql();
    // Handle Refunds
    if (isset($_POST['txn_type']) && $_POST['txn_type'] == 'refund') {
        // $_POST['parent_txn_id'] is the original transaction ID for the refunded payment
        $stmt = $pdo->prepare('UPDATE transactions SET payment_status = ? WHERE txn_id = ?');
        $stmt->execute(['Refunded', $_POST['parent_txn_id']]);
        exit;
    }
    // Check if the transaction type is a cart
    if ($_POST['txn_type'] == 'cart') {
        // Variables
        $products = [];
        $subtotal = 0.00;
        $shipping_total = isset($_POST['mc_shipping1']) ? floatval($_POST['mc_shipping1']) : 0.00;
        $handling_total = isset($_POST['mc_handling1']) ? floatval($_POST['mc_handling1']) : 0.00;
        $payment_status = $_POST['payment_status'] == 'Completed' ? default_payment_status : $_POST['payment_status'];
        // Retrieve custom data (account_id, discount_code)
        $custom = isset($_POST['custom']) ? json_decode($_POST['custom'], true) : [];
        // Assign custom variables
        $account_id = isset($custom['account_id']) ? $custom['account_id'] : null;
        $discount_code = isset($custom['discount_code']) ? $custom['discount_code'] : '';
        $shipping_method = isset($custom['shipping_method']) ? $custom['shipping_method'] : '';
        // Customer variables
        $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
        $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
        $payer_email = isset($_POST['payer_email']) ? $_POST['payer_email'] : '';
        $address_street = isset($_POST['address_street']) ? $_POST['address_street'] : '';
        $address_city = isset($_POST['address_city']) ? $_POST['address_city'] : '';
        $address_state = isset($_POST['address_state']) ? $_POST['address_state'] : '';
        $address_zip = isset($_POST['address_zip']) ? $_POST['address_zip'] : '';
        $address_country = isset($_POST['address_country']) ? $_POST['address_country'] : '';
        // Check if account exists with the account_id
        if ($account_id) {
            $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
            $stmt->execute([ $account_id ]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($account) {
                // Assign account variables
                $payer_email = empty($payer_email) ? $account['email'] : $payer_email;
                $first_name = empty($first_name) ? $account['first_name'] : $first_name;
                $last_name = empty($last_name) ? $account['last_name'] : $last_name;
                $address_street = empty($address_street) ? $account['address_street'] : $address_street;
                $address_city = empty($address_city) ? $account['address_city'] : $address_city;
                $address_state = empty($address_state) ? $account['address_state'] : $address_state;
                $address_zip = empty($address_zip) ? $account['address_zip'] : $address_zip;
                $address_country = empty($address_country) ? $account['address_country'] : $address_country;
            }
        }
        // Tax calculation
        $tax_amount = 0.00;
        // Iterate the cart items and insert the transaction items into the MySQL database
        for ($i = 1; $i < (intval($_POST['num_cart_items'])+1); $i++) {
            // Check if item is tax
            if ($_POST['item_number' . $i] == 'tax') {
                $tax_amount = floatval($_POST['mc_gross_' . $i]);
                continue;
            }
            // Update product quantity in the products table
            $stmt = $pdo->prepare('UPDATE products SET quantity = GREATEST(quantity - ?, 0) WHERE quantity > 0 AND id = ?');
            $stmt->execute([ $_POST['quantity' . $i], $_POST['item_number' . $i] ]);
            // Product related variables
            $option = isset($_POST['option_selection1_' . $i]) ? $_POST['option_selection1_' . $i] : '';
            $option = $option == 'N/A' ? '' : $option;
            // Deduct option quantities
            if ($option) {
                $options = explode(',', $option);
                foreach ($options as $opt) {
                    $option_name = explode('-', $opt)[0];
                    $option_value = explode('-', $opt)[1];
                    $stmt = $pdo->prepare('UPDATE product_options SET quantity = GREATEST(quantity - ?, 0) WHERE quantity > 0 AND option_name = ? AND (option_value = ? OR option_value = "") AND product_id = ?');
                    $stmt->execute([ $_POST['quantity' . $i], $option_name, $option_value, $_POST['item_number' . $i] ]);         
                }
            }
            // For some reason PayPal sends the shipping amount with the first item, so we need to deduct it from the first item's price
            $gross = $i == 1 ? floatval($_POST['mc_gross_' . $i]) - $shipping_total - $handling_total : floatval($_POST['mc_gross_' . $i]);
            // If thats not the case, uncomment the line below
            // $gross = floatval($_POST['mc_gross_' . $i]);
            // Determine the price of the item
            $item_price = $gross / intval($_POST['quantity' . $i]);
            // Insert product into the "transaction_items" table
            $stmt = $pdo->prepare('INSERT INTO transaction_items (txn_id, item_id, item_price, item_quantity, item_options) VALUES (?,?,?,?,?)');
            $stmt->execute([ $_POST['txn_id'], $_POST['item_number' . $i], $item_price, $_POST['quantity' . $i], $option ]);
            // Add product to array
            $products[] = [
                'id' => $_POST['item_number' . $i],
                'quantity' => $_POST['quantity' . $i],
                'options' => $option,
                'final_price' => $item_price,
                'meta' => [
                    'title' => $_POST['item_name' . $i],
                    'price' => $item_price
                ]
            ];
            // Add product price to the subtotal variable
            $subtotal += $item_price * intval($_POST['quantity' . $i]);
        }
        // Calculate total
        $total = $subtotal + $shipping_total + $tax_amount;
        // Insert the transaction into our transactions table, as the payment status changes the query will execute again and update it, make sure the "txn_id" column is unique
        $stmt = $pdo->prepare('INSERT INTO transactions (txn_id, payment_amount, payment_status, created, payer_email, first_name, last_name, address_street, address_city, address_state, address_zip, address_country, account_id, payment_method, shipping_method, shipping_amount, discount_code, tax_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE payment_status = VALUES(payment_status)');
        $stmt->execute([ $_POST['txn_id'], $total, $payment_status, date('Y-m-d H:i:s'), $payer_email, $first_name, $last_name, $address_street, $address_city, $address_state, $address_zip, $address_country, $account_id, 'paypal', $shipping_method, $shipping_total, $discount_code, $tax_amount ]);
        $order_id = $pdo->lastInsertId();
        // Send order details to the customer's email address
        if ($_POST['payment_status'] == 'Completed') {
            $send_to_email = isset($account) && $account ? $account['email'] : $payer_email;
            send_order_details_email($send_to_email, $products, $first_name, $last_name, $address_street, $address_city, $address_state, $address_zip, $address_country, $total, $order_id);
        }
    }
    // Check if the transaction type is a subscription
    if ($_POST['txn_type'] == 'subscr_payment' && isset($_POST['payment_status'])) {
        // Variables
        $account_id = $_POST['custom'];
        $product_id = $_POST['item_number'];
        $product_name = $_POST['item_name'];
        $product_options = isset($_POST['option_selection1']) && !empty($_POST['option_selection1']) ? $_POST['option_selection1'] : '';
        $product_options = $product_options == 'N/A' ? '' : $product_options;
        $subscription_id = $_POST['subscr_id'];
        $subscription_status = $_POST['payment_status'] == 'Completed' ? 'Subscribed' : $_POST['payment_status'];
        $subscription_price = $_POST['mc_gross'];
        $subscription_created = date('Y-m-d H:i:s');
        // Check if account exists with the account_id
        if ($account_id) {
            $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
            $stmt->execute([ $account_id ]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($account) {
                // Update product quantity in the products table
                $stmt = $pdo->prepare('UPDATE products SET quantity = GREATEST(quantity - ?, 0) WHERE quantity > 0 AND id = ?');
                $stmt->execute([ 1, $product_id ]);
                // Deduct option quantities
                if ($product_options) {
                    $options = explode(',', $product_options);
                    foreach ($options as $opt) {
                        $option_name = explode('-', $opt)[0];
                        $option_value = explode('-', $opt)[1];
                        $stmt = $pdo->prepare('UPDATE product_options SET quantity = GREATEST(quantity - ?, 0) WHERE quantity > 0 AND option_name = ? AND (option_value = ? OR option_value = "") AND product_id = ?');
                        $stmt->execute([ 1, $option_name, $option_value, $product_id ]);         
                    }
                }
                // Insert into transactions
                $stmt = $pdo->prepare('INSERT INTO transactions (txn_id, payment_amount, payment_status, created, payer_email, first_name, last_name, address_street, address_city, address_state, address_zip, address_country, account_id, payment_method, shipping_method, shipping_amount, discount_code) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE payment_status = VALUES(payment_status)');
                $stmt->execute([ $subscription_id, $subscription_price, $subscription_status, $subscription_created, isset($_POST['payer_email']) ? $_POST['payer_email'] : $account['email'], $account['first_name'], $account['last_name'], $account['address_street'], $account['address_city'], $account['address_state'], $account['address_zip'], $account['address_country'], $account_id, 'paypal', '', 0.00, '' ]);
                // Get insert id
                $order_id = $pdo->lastInsertId();
                // Insert product into the "transaction_items" table
                $stmt = $pdo->prepare('INSERT INTO transaction_items (txn_id, item_id, item_price, item_quantity, item_options) VALUES (?,?,?,?,?)');
                $stmt->execute([ $subscription_id, $product_id, $subscription_price, 1, $product_options ]);
                // Send subscription details to the customer's email address
                if ($subscription_status == 'Subscribed') {
                    $products = [
                        [
                            'id' => $product_id,
                            'quantity' => 1,
                            'options' => $product_options,
                            'final_price' => $subscription_price,
                            'meta' => [
                                'title' => $product_name,
                                'price' => $subscription_price
                            ]
                        ]
                    ];
                    send_order_details_email($account['email'], $products, $account['first_name'], $account['last_name'], $account['address_street'], $account['address_city'], $account['address_state'], $account['address_zip'], $account['address_country'], $subscription_price, $order_id);
                }
            }
        }
    }
    // Check if the transaction type is a subscription cancellation, end of term, or failed
    if ($_POST['txn_type'] == 'subscr_cancel' || $_POST['txn_type'] == 'subscr_eot' || $_POST['txn_type'] == 'subscr_failed') {
        // Variables
        $stmt = $pdo->prepare('UPDATE transactions SET payment_status = ? WHERE txn_id = ?');
        $stmt->execute([ 'Unsubscribed', $_POST['subscr_id'] ]);
    }
}
?>