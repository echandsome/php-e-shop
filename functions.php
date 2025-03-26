<?php
// Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Function that will connect to the MySQL database
function pdo_connect_mysql() {
    try {
        // Connect to the MySQL database using the PDO interface
    	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=utf8mb4', db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// Could not connect to the MySQL database! If you encounter this error, ensure your db settings are correct in the config file!
    	exit('Failed to connect to database! ' . $exception->getMessage());
    }
    return $pdo;
}
// Template header, feel free to customize this
function template_header($title, $head = '') {
// Get the amount of items in the shopping cart, this will be displayed in the header.
$num_items_in_cart = isset($_SESSION['cart']) && $_SESSION['cart'] ? '<span>' . array_sum(array_column($_SESSION['cart'], 'quantity')) . '</span>' : '';
$admin_link = isset($_SESSION['account_loggedin'], $_SESSION['account_role']) && $_SESSION['account_role'] == 'Admin' ? '<a href="' . base_url . 'admin/index.php" target="_blank">Admin</a>' : '';
$logout_link = isset($_SESSION['account_loggedin']) ? '<a title="Logout" href="' . url('index.php?page=logout') . '"><svg width="22" height="22" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 7L15.59 8.41L18.17 11H8V13H18.17L15.59 15.58L17 17L22 12M4 5H12V3H4C2.9 3 2 3.9 2 5V19C2 20.1 2.9 21 4 21H12V19H4V5Z" /></svg></a>' : '';
// DO NOT INDENT THE BELOW CODE
echo '<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>' . $title . '</title>
        <link rel="icon" type="image/png" href="' . base_url . 'favicon.png">
		<link href="' . base_url . 'style.css" rel="stylesheet" type="text/css">
        ' . $head . '
	</head>
	<body>
        <header>
            <div class="content-wrapper">
                <h1>' . site_name . '</h1>
                <nav>
                    <a href="' . url('index.php') . '">Home</a>
                    <a href="' . url('index.php?page=products') . '">Products</a>
					<a href="' . url('index.php?page=myaccount') . '">My Account</a>
                    ' . $admin_link . '
                </nav>
                <div class="link-icons">
                    <div class="search">
                        <div class="icon search-toggle" title="Search">
                            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>                    
                        </div>
						<input id="search" type="text" placeholder="Search..." data-url="' . url('index.php?page=search&query=') . '">
					</div>
                    <a href="' . url('index.php?page=cart') . '" title="Shopping Cart">
                        <svg width="23" height="23" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 6H17C17 3.2 14.8 1 12 1S7 3.2 7 6H5C3.9 6 3 6.9 3 8V20C3 21.1 3.9 22 5 22H19C20.1 22 21 21.1 21 20V8C21 6.9 20.1 6 19 6M12 3C13.7 3 15 4.3 15 6H9C9 4.3 10.3 3 12 3M19 20H5V8H19V20M12 12C10.3 12 9 10.7 9 9H7C7 11.8 9.2 14 12 14S17 11.8 17 9H15C15 10.7 13.7 12 12 12Z" /></svg>
						' . $num_items_in_cart . '
					</a>
                    ' . $logout_link . '
					<a class="responsive-toggle" href="#">
                        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z" /></svg>
					</a>
                </div>
            </div>
        </header>
        <main>';
}
// Template footer
function template_footer() {
// DO NOT INDENT THE BELOW CODE
echo '
        </main>
        <footer>
            <div class="content-wrapper">
                <p>&copy; ' . date('Y') . ', <a href="" target="_blank"></a></p>
            </div>
        </footer>
        <script>
        const currency_code = "' . currency_code . '";
        </script>
        <script src="' . base_url . 'script.js"></script>
    </body>
</html>';
}
// Template admin header
function template_admin_header($title, $selected = 'orders', $selected_child = 'view') {
    global $accounts_total, $orders_total;
    // Admin links
    $admin_links = '
        <a href="index.php?page=dashboard"' . ($selected == 'dashboard' ? ' class="selected"' : '') . ' title="Dashboard">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>            
            </span>
            <span class="txt">Dashboard</span>
        </a>
        <a href="index.php?page=orders"' . ($selected == 'orders' ? ' class="selected"' : '') . ' title="Orders">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>
            </span>
            <span class="txt">Orders</span>
            <span class="note">' . num_format($orders_total) . '</span>
        </a>
        <div class="sub">
            <a href="index.php?page=orders"' . ($selected == 'orders' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Orders</a>
            <a href="index.php?page=order_manage"' . ($selected == 'orders' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Order</a>
            <a href="index.php?page=orders_export"' . ($selected == 'orders' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Orders</a>
            <a href="index.php?page=orders_import"' . ($selected == 'orders' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Orders</a>
        </div>
        <a href="index.php?page=products"' . ($selected == 'products' ? ' class="selected"' : '') . ' title="Products">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M58.9 42.1c3-6.1 9.6-9.6 16.3-8.7L320 64 564.8 33.4c6.7-.8 13.3 2.7 16.3 8.7l41.7 83.4c9 17.9-.6 39.6-19.8 45.1L439.6 217.3c-13.9 4-28.8-1.9-36.2-14.3L320 64 236.6 203c-7.4 12.4-22.3 18.3-36.2 14.3L37.1 170.6c-19.3-5.5-28.8-27.2-19.8-45.1L58.9 42.1zM321.1 128l54.9 91.4c14.9 24.8 44.6 36.6 72.5 28.6L576 211.6v167c0 22-15 41.2-36.4 46.6l-204.1 51c-10.2 2.6-20.9 2.6-31 0l-204.1-51C79 419.7 64 400.5 64 378.5v-167L191.6 248c27.8 8 57.6-3.8 72.5-28.6L318.9 128h2.2z"/></svg>
            </span>
            <span class="txt">Products</span>
        </a>
        <div class="sub">
            <a href="index.php?page=products"' . ($selected == 'products' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Products</a>
            <a href="index.php?page=product"' . ($selected == 'products' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Product</a>
            <a href="index.php?page=products_export"' . ($selected == 'products' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Products</a>
            <a href="index.php?page=products_import"' . ($selected == 'products' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Products</a>
        </div>
        <a href="index.php?page=categories"' . ($selected == 'categories' ? ' class="selected"' : '') . ' title="Categories">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M7 7H9V9H7V7M7 11H9V13H7V11M7 15H9V17H7V15M17 17H11V15H17V17M17 13H11V11H17V13M17 9H11V7H17V9Z" /></svg>
            </span>
            <span class="txt">Categories</span>
        </a>
        <div class="sub">
            <a href="index.php?page=categories"' . ($selected == 'categories' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Categories</a>
            <a href="index.php?page=category"' . ($selected == 'categories' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Category</a>
        </div>
        <a href="index.php?page=accounts"' . ($selected == 'accounts' ? ' class="selected"' : '') . ' title="Accounts">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg>
            </span>
            <span class="txt">Accounts</span>
            <span class="note">' . num_format($accounts_total) . '</span>
        </a>
        <div class="sub">
            <a href="index.php?page=accounts"' . ($selected == 'accounts' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Accounts</a>
            <a href="index.php?page=account"' . ($selected == 'accounts' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Account</a>
            <a href="index.php?page=accounts_export"' . ($selected == 'accounts' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Accounts</a>
            <a href="index.php?page=accounts_import"' . ($selected == 'accounts' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Accounts</a>
        </div>
        <a href="index.php?page=shipping"' . ($selected == 'shipping' ? ' class="selected"' : '') . ' title="Shipping">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M112 0C85.5 0 64 21.5 64 48V96H16c-8.8 0-16 7.2-16 16s7.2 16 16 16H64 272c8.8 0 16 7.2 16 16s-7.2 16-16 16H64 48c-8.8 0-16 7.2-16 16s7.2 16 16 16H64 240c8.8 0 16 7.2 16 16s-7.2 16-16 16H64 16c-8.8 0-16 7.2-16 16s7.2 16 16 16H64 208c8.8 0 16 7.2 16 16s-7.2 16-16 16H64V416c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H112zM544 237.3V256H416V160h50.7L544 237.3zM160 368a48 48 0 1 1 0 96 48 48 0 1 1 0-96zm272 48a48 48 0 1 1 96 0 48 48 0 1 1 -96 0z"/></svg>
            </span>
            <span class="txt">Shipping</span>
        </a>
        <div class="sub">
            <a href="index.php?page=shipping"' . ($selected == 'shipping' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Shipping Methods</a>
            <a href="index.php?page=shipping_process"' . ($selected == 'shipping' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Shipping Method</a>
        </div>
        <a href="index.php?page=discounts"' . ($selected == 'discounts' ? ' class="selected"' : '') . ' title="Discounts">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>
            </span>
            <span class="txt">Discounts</span>
        </a>
        <div class="sub">
            <a href="index.php?page=discounts"' . ($selected == 'discounts' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Discounts</a>
            <a href="index.php?page=discount"' . ($selected == 'discounts' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Discount</a>
        </div>
        <a href="index.php?page=taxes"' . ($selected == 'taxes' ? ' class="selected"' : '') . ' title="Taxes">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M374.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-320 320c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l320-320zM128 128A64 64 0 1 0 0 128a64 64 0 1 0 128 0zM384 384a64 64 0 1 0 -128 0 64 64 0 1 0 128 0z"/></svg>
            </span>
            <span class="txt">Taxes</span>
        </a>
        <div class="sub">
            <a href="index.php?page=taxes"' . ($selected == 'taxes' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Taxes</a>
            <a href="index.php?page=tax"' . ($selected == 'taxes' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Tax</a>
        </div>
        <a href="index.php?page=media"' . ($selected == 'media' ? ' class="selected"' : '') . ' title="Media">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M160 32c-35.3 0-64 28.7-64 64V320c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H160zM396 138.7l96 144c4.9 7.4 5.4 16.8 1.2 24.6S480.9 320 472 320H328 280 200c-9.2 0-17.6-5.3-21.6-13.6s-2.9-18.2 2.9-25.4l64-80c4.6-5.7 11.4-9 18.7-9s14.2 3.3 18.7 9l17.3 21.6 56-84C360.5 132 368 128 376 128s15.5 4 20 10.7zM192 128a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM48 120c0-13.3-10.7-24-24-24S0 106.7 0 120V344c0 75.1 60.9 136 136 136H456c13.3 0 24-10.7 24-24s-10.7-24-24-24H136c-48.6 0-88-39.4-88-88V120z"/></svg>
            </span>
            <span class="txt">Media</span>
        </a>
        <a href="index.php?page=email_templates"' . ($selected == 'email_templates' ? ' class="selected"' : '') . ' title="Email Templates">
            <span class="icon">
                <svg width="17" height="17" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22,6V4L14,9L6,4V6L14,11L22,6M22,2A2,2 0 0,1 24,4V16A2,2 0 0,1 22,18H6C4.89,18 4,17.1 4,16V4C4,2.89 4.89,2 6,2H22M2,6V20H20V22H2A2,2 0 0,1 0,20V6H2Z" /></svg>
            </span>
            <span class="txt">Email Templates</span>
        </a>
        <a href="index.php?page=settings"' . ($selected == 'settings' ? ' class="selected"' : '') . ' title="Settings">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z" /></svg>
            </span>
            <span class="txt">Settings</span>
        </a>
    ';
    // Profile image
    $profile_img = '
    <div class="profile-img">
        <span style="background-color:' . color_from_string($_SESSION['account_name']) . '">' . strtoupper(substr($_SESSION['account_name'], 0, 1)) . '</span>
        <i class="online"></i>
    </div>
    ';
// Indenting the below code may cause an error
echo '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
        <title>' . $title . '</title>
        <link rel="icon" type="image/png" href="../favicon.png">
        <link href="admin.css" rel="stylesheet" type="text/css">
    </head>
    <body class="admin">
        <aside>
            <h1>
                <span class="icon">A</span>
                <span class="title">Admin</span>
            </h1>
            ' . $admin_links . '
            <div class="footer">
                <a href="" target="_blank"></a>
                Version 4.0.0
            </div>
        </aside>
        <main class="responsive-width-100">
            <header>
                <a class="responsive-toggle" href="#" title="Toggle Menu"></a>
                <div class="space-between"></div>
                <div class="dropdown right">
                    ' . $profile_img . '
                    <div class="list">
                        <a href="index.php?page=account&id=' . $_SESSION['account_id'] . '">Edit Profile</a>
                        <a href="index.php?page=logout" class="red">Logout</a>
                    </div>
                </div>
            </header>';
}
// Template admin footer
function template_admin_footer($footer_code = '') {
// DO NOT INDENT THE BELOW CODE
echo '  </main>
        <script src="admin.js"></script>
        ' . $footer_code . '
    </body>
</html>';
}
// Function to retrieve a product from cart by the ID and options string
function &get_cart_product($id, $options) {
    $p = null;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as &$product) {
            if ($product['id'] == $id && $product['options'] == $options) {
                $p = &$product;
                return $p;
            }
        }
    }
    return $p;
}
// Function to get the total quantity of a product option in the cart
function get_cart_option_quantity($id, $option) {
    $quantity = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product) {
            if ($product['id'] == $id && strpos($product['options'], $option) !== false) {
                $quantity += $product['quantity'];
            }
        }
    }
    return $quantity;
}
// Function to get the total quantity of a product in the cart
function get_cart_product_quantity($id) {
    $quantity = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product) {
            if ($product['id'] == $id) {
                $quantity += $product['quantity'];
            }
        }
    }
    return $quantity;
}
// Populate categories function
function populate_categories($categories, $category_list, $selected = 0, $parent_id = 0, $n = 0) {
    $html = '';
    foreach ($categories as $i => $c) {
        if ($parent_id == $c['parent_id']) {
            $padding = $n < 2 ? $n * 10 : 20;
            $html .='
            <label class="checkbox' . ($i > 4 ? ' hidden' : '') . '"' . ($padding  ? ' style="padding-left:' . $padding . 'px;"' : '') . '>
                <input type="checkbox" name="category[]" value="' . $c['id'] . '"' . (in_array($c['id'], $category_list) ? ' checked' : '') . '>
                ' . $c['title'] . '
            </label>
            ';
            $html .= populate_categories($categories, $category_list, $selected, $c['id'], $n+1);
        }
    }
    return $html;
}
// Get country list
function get_countries() {
    return ["Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe"];
}
// Helper function to check an individual rule
function check_rule($field_value, $operator, $expected_value) {
    // Normalize values for case-insensitivity if required
    $field_value = strtolower($field_value);
    $expected_value = strtolower($expected_value);
    switch ($operator) {
        case 'includes':
            return (strpos($field_value, $expected_value) !== false);
        case 'excludes':
            return (strpos($field_value, $expected_value) === false);
        case 'equals':
            return ($field_value === $expected_value);
        case 'not_equals':
            return ($field_value !== $expected_value);
        case 'starts_with':
            return (substr($field_value, 0, strlen($expected_value)) === $expected_value);
        case 'ends_with':
            return (substr($field_value, -strlen($expected_value)) === $expected_value);
        default:
            // Unknown operator, return false or handle error
            return false;
    }
}
// Helper function to check all rules for a given tax entry
function check_rules($rules, $fields) {
    foreach ($rules as $rule) {
        $field = $rule['field'];
        $operator = $rule['operator'];
        $value = $rule['value'];
        // If any rule fails, return false immediately
        if (!isset($fields[$field]) || !check_rule($fields[$field], $operator, $value)) {
            return false;
        }
    }
    // If we reach here, all rules passed
    return true;
}
// Send order details email function
function send_order_details_email($email, $products, $first_name, $last_name, $address_street, $address_city, $address_state, $address_zip, $address_country, $subtotal, $order_id) {
	if (!mail_enabled) return;
    // Escapte variables
    $first_name = htmlspecialchars($first_name, ENT_QUOTES);
    $last_name = htmlspecialchars($last_name, ENT_QUOTES);
    $address_street = htmlspecialchars($address_street, ENT_QUOTES);
    $address_city = htmlspecialchars($address_city, ENT_QUOTES);
    $address_state = htmlspecialchars($address_state, ENT_QUOTES);
    $address_zip = htmlspecialchars($address_zip, ENT_QUOTES);
    $address_country = htmlspecialchars($address_country, ENT_QUOTES);
	// Include PHPMailer library
	include_once __DIR__ . '/lib/phpmailer/Exception.php';
	include_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
	include_once __DIR__ . '/lib/phpmailer/SMTP.php';
	// Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);
	try {
		// Server settings
		if (SMTP) {
			$mail->isSMTP();
			$mail->Host = smtp_host;
			$mail->SMTPAuth = empty(smtp_user) && empty(smtp_pass) ? false : true;
			$mail->Username = smtp_user;
			$mail->Password = smtp_pass;
			$mail->SMTPSecure = smtp_secure == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = smtp_port;
		}
		// Recipients
		$mail->setFrom(mail_from, mail_name);
		$mail->addAddress($email);
		$mail->addReplyTo(mail_from, mail_name);
		// Content
		$mail->isHTML(true);
        // Set UTF-8 charset
        $mail->CharSet = 'UTF-8';
        // Set email subject
		$mail->Subject = 'Order Details (#' . $order_id . ')';
        // Products template
        $products_template = '';
        foreach($products as $product) {
            $products_template .= '<tr>
                <td style="padding:25px 0;">' . htmlspecialchars($product['meta']['title'], ENT_QUOTES) . '<div style="color:#989b9e">' . htmlspecialchars($product['options'], ENT_QUOTES) . '</div></td>
                <td>' . num_format($product['final_price'],2) . '</td>
                <td>' . $product['quantity'] . '</td>
                <td style="text-align:right;">' . num_format($product['final_price'] * $product['quantity'],2) . '</td>
            </tr>';
        }
        $products_template = '
        <table style="border-collapse:collapse;width:100%;">
            <thead style="border-bottom:1px solid #eee;">
                <tr>
                    <td style="padding:25px 0;font-weight:500;font-size:14px;">Product</td>
                    <td style="font-weight:500;font-size:14px;">Price</td>
                    <td style="font-weight:500;font-size:14px;">Quantity</td>
                    <td style="text-align:right;font-weight:500;font-size:14px;">Total</td>
                </tr>
            </thead>
            <tbody>' . $products_template . '</tbody>
        </table>';
		// Read the template contents and replace the placeholders with the variables
		$email_template = str_replace(
            ['%order_id%', '%first_name%', '%last_name%', '%address_street%', '%address_city%', '%address_state%', '%address_zip%', '%address_country%', '%subtotal%', '%products_template%'], 
            [$order_id, $first_name, $last_name, $address_street, $address_city, $address_state, $address_zip, $address_country, num_format($subtotal, 2), $products_template],
            file_get_contents(__DIR__ . '/order-details-template.html')
        );
        // Add main tags to html
        $email_template = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,minimum-scale=1"><title>Order Details (#' . $order_id . ')</title></head><body style="margin:0;padding:0">' . $email_template . '</body></html>';
		// Set email body
		$mail->Body = $email_template;
		$mail->AltBody = strip_tags($email_template);
		// Send mail
		$response = $mail->send();
        // Send notification email
        if ($response) {
            send_order_details_notification_email($products, $first_name, $last_name, $address_street, $address_city, $address_state, $address_zip, $address_country, $subtotal, $order_id);
        }
	} catch (Exception $e) {
		// Output error message
		exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
	}
}
// Send notification email function
function send_order_details_notification_email($products, $first_name, $last_name, $address_street, $address_city, $address_state, $address_zip, $address_country, $subtotal, $order_id) {
	if (!mail_enabled || !notifications_enabled) return;
	// Include PHPMailer library
	include_once __DIR__ . '/lib/phpmailer/Exception.php';
	include_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
	include_once __DIR__ . '/lib/phpmailer/SMTP.php';
	// Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);
	try {
		// Server settings
		if (SMTP) {
			$mail->isSMTP();
			$mail->Host = smtp_host;
			$mail->SMTPAuth = empty(smtp_user) && empty(smtp_pass) ? false : true;
			$mail->Username = smtp_user;
			$mail->Password = smtp_pass;
			$mail->SMTPSecure = smtp_secure == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = smtp_port;
		}
		// Recipients
		$mail->setFrom(mail_from, mail_name);
		$mail->addAddress(notification_email);
		$mail->addReplyTo(mail_from, mail_name);
		// Content
		$mail->isHTML(true);
        // Set UTF-8 charset
        $mail->CharSet = 'UTF-8';
        // Set email subject
		$mail->Subject = 'New Order Received (#' . $order_id . ')';
        // Products template
        $products_template = '';
        foreach($products as $product) {
            $products_template .= '<tr>
                <td style="padding:25px 0;">' . htmlspecialchars($product['meta']['title'], ENT_QUOTES) . '<div style="color:#989b9e">' . htmlspecialchars($product['options'], ENT_QUOTES) . '</div></td>
                <td>' . num_format($product['final_price'],2) . '</td>
                <td>' . $product['quantity'] . '</td>
                <td style="text-align:right;">' . num_format($product['final_price'] * $product['quantity'],2) . '</td>
            </tr>';
        }
        $products_template = '
        <table style="border-collapse:collapse;width:100%;">
            <thead style="border-bottom:1px solid #eee;">
                <tr>
                    <td style="padding:25px 0;font-weight:500;font-size:14px;">Product</td>
                    <td style="font-weight:500;font-size:14px;">Price</td>
                    <td style="font-weight:500;font-size:14px;">Quantity</td>
                    <td style="text-align:right;font-weight:500;font-size:14px;">Total</td>
                </tr>
            </thead>
            <tbody>' . $products_template . '</tbody>
        </table>';
		// Read the template contents and replace the placeholders with the variables
		$email_template = str_replace(
            ['%order_id%', '%first_name%', '%last_name%', '%address_street%', '%address_city%', '%address_state%', '%address_zip%', '%address_country%', '%subtotal%', '%products_template%'], 
            [$order_id, $first_name, $last_name, $address_street, $address_city, $address_state, $address_zip, $address_country, num_format($subtotal, 2), $products_template],
            file_get_contents(__DIR__ . '/order-notification-template.html')
        );
        // Add main tags to html
        $email_template = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,minimum-scale=1"><title>New Order Received (#' . $order_id . ')</title></head><body style="margin:0;padding:0">' . $email_template . '</body></html>';
		// Set email body
		$mail->Body = $email_template;
		$mail->AltBody = strip_tags($email_template);
		// Send mail
		$mail->send();
	} catch (Exception $e) {
		// Output error message
		exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
	}
}
// Send password reset email function
function send_password_reset_email($email, $code) {
	if (!mail_enabled) return;
	// Include PHPMailer library
	include_once __DIR__ . '/lib/phpmailer/Exception.php';
	include_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
	include_once __DIR__ . '/lib/phpmailer/SMTP.php';
	// Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);
	try {
		// Server settings
		if (SMTP) {
			$mail->isSMTP();
			$mail->Host = smtp_host;
			$mail->SMTPAuth = empty(smtp_user) && empty(smtp_pass) ? false : true;
			$mail->Username = smtp_user;
			$mail->Password = smtp_pass;
			$mail->SMTPSecure = smtp_secure == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = smtp_port;
		}
		// Recipients
		$mail->setFrom(mail_from, mail_name);
		$mail->addAddress($email);
		$mail->addReplyTo(mail_from, mail_name);
		// Content
		$mail->isHTML(true);
        // Set UTF-8 charset
        $mail->CharSet = 'UTF-8';
        // Set email subject
		$mail->Subject = 'Password Reset';
		// Password reset link
		$reset_link = url('index.php?page=forgotpassword', ['code' => $code]);
		// Read the template contents and replace the "%link%" placeholder with the above variable
		$email_template = str_replace(['%link%'], [$reset_link], file_get_contents(__DIR__ . '/resetpass-email-template.html'));
        // Add main tags to html
        $email_template = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,minimum-scale=1"><title>Password Reset</title></head><body style="margin:0;padding:0">' . $email_template . '</body></html>';
		// Set email body
		$mail->Body = $email_template;
		$mail->AltBody = strip_tags($email_template);
		// Send mail
		$mail->send();
	} catch (Exception $e) {
		// Output error message
		exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
	}
}
// Determine URL function
function url($url, $params = []) {
    if (rewrite_url) {
        $url = preg_replace('/\&(.*?)\=/', '/', str_replace(['index.php?page=', 'index.php'], '', $url));
    }
    if ($params) {
        $url_params = parse_url($url);
        if (isset($url_params['query'])) {
            parse_str($url_params['query'], $query_params);
            $params = array_merge($query_params, $params);
            $url = $url_params['path'];
        }
        $url .= '?' . http_build_query($params);
    }
    return base_url . $url;
}
// Routeing function
function routes($urls) {
    foreach ($urls as $url => $file_path) {
        $url = '/' . ltrim($url, '/');
        $prefix = dirname($_SERVER['PHP_SELF']);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, 0, strlen($prefix)) == $prefix) {
            $uri = substr($uri, strlen($prefix));
        }
        $uri = '/' . ltrim($uri, '/');
        $path = explode('/', parse_url($uri)['path']);
        $routes = explode('/', $url);
        $values = [];
        foreach ($path as $pk => $pv) {
            if (isset($routes[$pk]) && preg_match('/{(.*?)}/', $routes[$pk])) {
                $var = str_replace(['{','}'], '', $routes[$pk]);
                $routes[$pk] = preg_replace('/{(.*?)}/', $pv, $routes[$pk]);
                $values[$var] = $pv;
            }
        }
        if ($routes === $path && rewrite_url) {
            parse_str(parse_url($file_path)['query'], $params);
            foreach ($values as $k => $v) {
                $_GET[$k] = $v;
            }
            foreach ($params as $k => $v) {
                if (!isset($_GET[$k]) && $k != 'page') {
                    $_GET[$k] = $v;
                }
            }
            return isset($params['page']) && file_exists($params['page'] . '.php') ? $params['page'] . '.php' : 'home.php';
        }
    }
    if (rewrite_url) {
        header('Location: ' . url('index.php'));
        exit;
    }
    return null;
}
// Format bytes to human-readable format
function format_bytes($bytes) {
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), [0,0,2,2,3][$i]).['B','KB','MB','GB','TB'][$i];
}
// The following function will be used to assign a unique icon color to our users
function color_from_string($string) {
    // The list of hex colors
    $colors = ['#34568B','#FF6F61','#6B5B95','#88B04B','#F7CAC9','#92A8D1','#955251','#B565A7','#009B77','#DD4124','#D65076','#45B8AC','#EFC050','#5B5EA6','#9B2335','#DFCFBE','#BC243C','#C3447A','#363945','#939597','#E0B589','#926AA6','#0072B5','#E9897E','#B55A30','#4B5335','#798EA4','#00758F','#FA7A35','#6B5876','#B89B72','#282D3C','#C48A69','#A2242F','#006B54','#6A2E2A','#6C244C','#755139','#615550','#5A3E36','#264E36','#577284','#6B5B95','#944743','#00A591','#6C4F3D','#BD3D3A','#7F4145','#485167','#5A7247','#D2691E','#F7786B','#91A8D0','#4C6A92','#838487','#AD5D5D','#006E51','#9E4624'];
    // If string empty, return
    if (empty($string)) {
        return '#000000';
    }
    // Find color based on the string
    $colorIndex = hexdec(substr(sha1($string), 0, 10)) % count($colors);
    // Return the hex color
    return $colors[$colorIndex];
}
// Convert date to elapsed string function
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $w = floor($diff->d / 7);
    $diff->d -= $w * 7;
    $string = ['y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second'];
    foreach ($string as $k => &$v) {
        if ($k == 'w' && $w) {
            $v = $w . ' week' . ($w > 1 ? 's' : '');
        } else if (isset($diff->$k) && $diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
// Remove param from URL function
function remove_url_param($url, $param) {
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
    return $url;
}
// Num format function
function num_format($num, $decimals  = 0, $decimal_separator = '.', $thousands_separator = ',') {
    return number_format(empty($num) || $num == null || !is_numeric($num) ? 0 : $num, $decimals, $decimal_separator, $thousands_separator);
}
// Stripe update webhook function
function stripe_update_webhook($stripe) {
    if (empty(stripe_webhook_secret)) {
        // Get the config.php file contents
        $contents = file_get_contents('config.php');
        if ($contents) {
            // Define the required events
            $required_events = [ 'checkout.session.completed', 'customer.subscription.deleted', 'invoice.payment_failed', 'charge.refunded' ];
            // Try to find an existing webhook endpoint with the desired URL
            $existing = $stripe->webhookEndpoints->all(['limit' => 100]);
            $webhook = null;
            foreach ($existing->data as $endpoint) {
                if ($endpoint->url === stripe_ipn_url) {
                    $webhook = $endpoint;
                    break;
                }
            }
            if (!$webhook) {
                // No matching endpoint exists; create one with all required events.
                $webhook = $stripe->webhookEndpoints->create([
                    'url' => stripe_ipn_url,
                    'description' => 'shoppingcart',
                    'enabled_events' => $required_events
                ]);
            } else {
                // Get current enabled events from the endpoint.
                $current_events = (array)$webhook->enabled_events;
                // Merge current events with required events.
                $new_events = array_unique(array_merge($current_events, $required_events));
                // Update the webhook only if the events list has changed.
                if (count($new_events) !== count($current_events)) {
                    $webhook = $stripe->webhookEndpoints->update(
                        $webhook->id,
                        ['enabled_events' => $new_events]
                    );
                }
            }
            // Update the "stripe_webhook_secret" constant in the config.php file with the new secret
            $contents = preg_replace('/define\(\'stripe_webhook_secret\'\, ?(.*?)\)/s', 'define(\'stripe_webhook_secret\',\'' . $webhook->secret . '\')', $contents);
            if (!file_put_contents('config.php', $contents)) {
                // Could not write to config.php file
                exit('Failed to automatically assign the Stripe webhook secret! Please set it manually in the config.php file.');
            }
        } else {
            exit('Failed to automatically assign the Stripe webhook secret! Please set it manually in the config.php file.');
        }
    }
}
?>