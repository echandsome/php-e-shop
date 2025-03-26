<?php
// Prevent direct access to file
defined('shoppingcart') or exit;
// Check if the user is logged in
if (!isset($_SESSION['account_loggedin'])) {
    // Not logged in
    header('Location: ' . url('index.php?page=myaccount'));
    exit;
}
if (isset($_GET['method'], $_SESSION['sub'])) {
    // Get product details
    $stmt = $pdo->prepare('SELECT * FROM products p WHERE p.id = ? AND p.product_status = 1');
    $stmt->execute([ $_SESSION['sub']['id'] ]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        // Product doesn't exist
        header('Location: ' . url('index.php?page=home'));
        exit;
    }
    if ($_GET['method'] == 'paypal' && paypal_enabled) {
        // Sub variables
        $price = $_SESSION['sub']['options_price'];
        $susbcription_period_type = 'D';
        $susbcription_period_type = $product['subscription_period_type'] == 'week' ? 'W' : $susbcription_period_type;
        $susbcription_period_type = $product['subscription_period_type'] == 'month' ? 'M' : $susbcription_period_type;
        $susbcription_period_type = $product['subscription_period_type'] == 'year' ? 'Y' : $susbcription_period_type;
        // Process PayPal standard subscription
        $data = [
            'cmd'			=> '_xclick-subscriptions',
            'charset'		=> 'UTF-8',
            'business' 		=> paypal_email,
            'cancel_return'	=> paypal_cancel_url,
            'notify_url'	=> paypal_ipn_url,
            'currency_code'	=> paypal_currency,
            'return'        => paypal_return_url,
            'no_shipping'	=> 1,
            'no_note'		=> 1,
            'custom'		=> $_SESSION['account_id'],
            'a3'			=> $price,
            'p3'			=> $product['subscription_period'],
            't3'			=> $susbcription_period_type,
            'src'			=> 1,
            'sra'			=> 1,
            'item_name'		=> $product['title'],
            'item_number'	=> $product['id'],
            'on0'	        => 'Options',
            'os0'	        => $_SESSION['sub']['options']
        ];
        // Redirect the user to the PayPal checkout screen
        header('Location: ' . (paypal_testmode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr') . '?' . http_build_query($data));
        exit;
    } else if ($_GET['method'] == 'stripe' && stripe_enabled) {
        // Include the stripe lib
        require_once 'lib/stripe/init.php';
        $stripe = new \Stripe\StripeClient(stripe_secret_key);
        // Assign the webhook secret if not already set
        stripe_update_webhook($stripe);
        // Create the new subscription using the Stripe API
        $line_items = [
            [
                'quantity' => 1,
                'price_data' => [
                    'currency' => stripe_currency,
                    'unit_amount' => round((float)$_SESSION['sub']['options_price'] * 100),
                    'recurring' => [
                        'interval' => $product['subscription_period_type'],
                        'interval_count' => $product['subscription_period']
                    ],
                    'product_data' => [
                        'name' => $product['title'],
                        'metadata' => [
                            'item_id' => $product['id'],
                            'item_options' => $_SESSION['sub']['options']
                        ]
                    ]
                ]
            ]
        ];
        // Create the stripe checkout session and redirect the customer
        $session = $stripe->checkout->sessions->create([
            'success_url' => stripe_return_url,
            'cancel_url' => stripe_cancel_url,
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'subscription',
            'metadata' => [ 'account_id' => $_SESSION['account_id'] ]
        ]);
        // Redirect to Stripe checkout
        header('Location: ' . $session['url']);
        exit;
    } else {
        // Invalid method
        header('Location: ' . url('index.php?page=home'));
        exit;
    }
} else {
    // No method or sub session
    header('Location: ' . url('index.php?page=home'));
    exit;
}
?>