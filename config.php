<?php

// Your MySQL database hostname.
define('db_host','localhost');
// Your MySQL database username.
define('db_user','root');
// Your MySQL database password.
define('db_pass','');
// Your MySQL database name.
define('db_name','shoppingcart_advanced');
// The title of your website.
define('site_name','Shopping Cart');
// Currency code (default is USD). 
define('currency_code','&dollar;');
// The default featured image that will appear on the homepage.
define('featured_image','uploads/featured-image.jpg');
// The default payment status for new orders.
define('default_payment_status','Completed');
// Account required for checkout?
define('account_required',false);
// Weight unit (default is lbs).
define('weight_unit','lbs');
// If enabled, the website will use the URL rewrite feature. You need to configure your web server to use the .htaccess file.
define('rewrite_url',false);
// The template editor to use for editing product descriptions, email templates, etc.
// List:tinymce=TinyMCE,textarea=Textarea
define('template_editor','tinymce');
// The secret key is used to generate unique reset codes for the forgot password feature.
define('secret_key','YOUR_SECRET_KEY');
// Determine the base URL
$protocol = ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? 'https' : 'http';
$host = rtrim($_SERVER['HTTP_HOST'], '/');
$port = (in_array($_SERVER['SERVER_PORT'], [80, 443]) || strpos($_SERVER['HTTP_HOST'], ':') !== false) ? '' : ':' . $_SERVER['SERVER_PORT'];
$path = '/' . ltrim(substr(str_replace('\\', '/', realpath(__DIR__)), strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/'))), '/');
define("base_url", rtrim("$protocol://$host$port$path", '/') . '/');
// If somehow the above URL fails to resolve the correct URL, you can simply comment out the below line and manually specifiy the URL to the system.
// define("base_url", 'http://yourdomain.com/shoppingcart/');

/* Mail */
// If enabled, the website will send an email to the customer when a new order is placed.
define('mail_enabled',false);
// Send mail from which address?
define('mail_from','noreply@example.com');
// The name of your website/business.
define('mail_name','Your Website/Business Name');
// If enabled, you will receive email notifications when a new payment is received.
define('notifications_enabled',true);
// The email address to send notification emails to.
define('notification_email','notifications@example.com');
// Is SMTP server?
define('SMTP',false);
// The SMTP Secure connection type (ssl, tls).
define('smtp_secure','ssl');
// SMTP Hostname
define('smtp_host','smtp.example.com');
// SMTP Port number
define('smtp_port',465);
// SMTP Username
define('smtp_user','user@example.com');
// SMTP Password
define('smtp_pass','secret');

/* Pay on Delivery */
// Accept pay on delivery payments?
define('pay_on_delivery_enabled',true);

/* PayPal */
// Accept payments with PayPal?
define('paypal_enabled',true);
// Your business email account, which is where you'll receive the payments.
define('paypal_email','payments@example.com');
// If the test mode is set to true it will use the PayPal sandbox website, which is used for testing purposes.
// Read more about PayPal sandbox here: https://developer.paypal.com/developer/accounts/
// Set this to false when you're ready to start accepting payments on your website.
define('paypal_testmode',true);
// Currency to use with PayPal (default is USD).
define('paypal_currency','USD');
// This should point to the IPN file located in the "ipn" directory.
define('paypal_ipn_url','https://example.com/ipn/paypal.php');
// The page the customer returns to when they cancel the payment.
define('paypal_cancel_url','https://example.com/index.php?page=cart');
// The page the customer returns to after the payment has been made.
define('paypal_return_url','https://example.com/index.php?page=placeorder');

/* Stripe */
// Accept payments with Stripe?
define('stripe_enabled',true);
// Stripe Publishable API Key
define('stripe_publish_key','');
// Stripe Secret API Key
define('stripe_secret_key','');
// Stripe currency
define('stripe_currency','USD');
// This should point to the IPN file located in the "ipn" directory.
define('stripe_ipn_url','https://example.com/ipn/stripe.php');
// The page the customer returns to when they cancel the payment.
define('stripe_cancel_url','https://example.com/index.php?page=cart');
// The page the customer returns to after the payment has been made.
define('stripe_return_url','https://example.com/index.php?page=placeorder');
// This is used to verify the webhook request. You can find this in the webhook settings in your stripe dashboard.
define('stripe_webhook_secret','');

/* Coinbase */
// Create a new webhook endpoint in the coinbase commerce dashboard and add the full url to the IPN file along with the key parameter
// Webhook endpoint URL example: https://example.com/shoppingcart/ipn/coinbase.php?key=SAME_AS_COINBASE_SECRET
// Accept payments with coinbase?
define('coinbase_enabled',false);
// Coinbase API Key
define('coinbase_key','');
// Coinbase Secret
define('coinbase_secret','');
// Coinbase currency
define('coinbase_currency','USD');
// The page the customer returns to when they cancel the payment.
define('coinbase_cancel_url','https://example.com/index.php?page=cart');
// The page the customer returns to after the payment has been made.
define('coinbase_return_url','https://example.com/index.php?page=placeorder');

// Uncomment the below if you're having issues with the IPN files or file uploads
// ini_set('memory_limit', '256M');
// ini_set('post_max_size', '64M');
// ini_set('upload_max_filesize', '64M');

// Uncomment the below to output all errors
// ini_set('log_errors', true);
// ini_set('error_log', 'error.log');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

?>