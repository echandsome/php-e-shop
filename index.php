<?php
define('shoppingcart', true);
// Determine the base URL
$base_url = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http';
$base_url .= '://' . rtrim($_SERVER['HTTP_HOST'], '/');
$base_url .= $_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443 || strpos($_SERVER['HTTP_HOST'], ':') !== false ? '' : ':' . $_SERVER['SERVER_PORT'];
$base_url .= '/' . ltrim(substr(str_replace('\\', '/', realpath(__DIR__)), strlen($_SERVER['DOCUMENT_ROOT'])), '/');
define('base_url', rtrim($base_url, '/') . '/');
// If somehow the above URL fails to resolve the correct URL, you can simply comment out the below line and manually specifiy the URL to the system.
// define('base_url', 'http://yourdomain.com/shoppingcart/');
// Initialize a new session
session_start();
// Include the configuration file, this contains settings you can change.
include 'config.php';
// Include functions and connect to the database using PDO MySQL
include 'functions.php';
// Connect to MySQL database
$pdo = pdo_connect_mysql();
// Output error variable
$error = '';
// Define all the routes for all pages
$url = routes([
    '/' => 'index.php?page=home',
    '/home' => 'index.php?page=home',
    '/product/{id}' => 'index.php?page=product&id={id}',
    '/products' => 'index.php?page=products',
    '/myaccount' => 'index.php?page=myaccount',
    '/myaccount/{tab}' => 'index.php?page=myaccount&tab={tab}',
    '/download/{id}' => 'index.php?page=download&id={id}',
    '/cart' => 'index.php?page=cart',
    '/checkout' => 'index.php?page=checkout',
    '/subscribe/{method}' => 'index.php?page=subscribe&method={method}',
    '/placeorder' => 'index.php?page=placeorder',
    '/search/{query}' => 'index.php?page=search&query={query}',
    '/logout' => 'index.php?page=logout'
]);
// Check if route exists
if ($url) {
    include $url;
} else {
    // Page is set to home (home.php) by default, so when the visitor visits that will be the page they see.
    $page = isset($_GET['page']) && file_exists($_GET['page'] . '.php') ? $_GET['page'] : 'home';
    // Include the requested page
    include $page . '.php';
}
?>