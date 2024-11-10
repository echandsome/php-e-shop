<?php
// Prevent direct access to file
defined('shoppingcart') or exit;
// Disable the time limit (for large files)
set_time_limit(0);
// Customer must be logged-in and must provide the secret ID
if (isset($_GET['id'], $_SESSION['account_loggedin'])) {
    // Get the product download URL and check if the ID matches
    $stmt = $pdo->prepare('SELECT pd.* FROM products_downloads pd JOIN transactions t ON t.account_id = ? JOIN transactions_items ti ON t.txn_id = ti.txn_id AND ti.item_id = pd.product_id AND MD5(CONCAT(ti.txn_id, pd.id)) = ?');
    $stmt->execute([ $_SESSION['account_id'], $_GET['id'] ]);
    // Fetch the product from the database and return the result as an Array
    $product_download = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product_download) {
        exit('Invalid ID!');
    }
} else {
    exit('Invalid ID!');
}
// Create the headers for the downloadable file
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: public');
header('Content-Description: File Transfer');
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($product_download['file_path']) . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($product_download['file_path']));
ob_end_flush();
// Download file
@readfile($product_download['file_path']);
exit;
?>