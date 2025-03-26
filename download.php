<?php
// Prevent direct access
defined('shoppingcart') or exit;
// Disable time limit for large files
set_time_limit(0);
// Validate input: customer must be logged in and a download key must be provided
if (!isset($_GET['id'], $_SESSION['account_loggedin'])) {
    exit('Invalid request!');
}
// Prepare and execute the SQL query to fetch the product download
$stmt = $pdo->prepare('SELECT pd.* FROM product_downloads pd JOIN transactions t ON t.account_id = ? JOIN transaction_items ti ON t.txn_id = ti.txn_id AND ti.item_id = pd.product_id AND MD5(CONCAT(ti.txn_id, pd.id)) = ?');
$stmt->execute([ $_SESSION['account_id'], $_GET['id'] ]);
$product_download = $stmt->fetch(PDO::FETCH_ASSOC);
// If no record is found, exit with an error
if (!$product_download) {
    exit('Invalid ID!');
}
// Check if the file exists and is readable
$file_path = $product_download['file_path'];
if (!file_exists($file_path) || !is_readable($file_path)) {
    exit('File not found or inaccessible!');
}
// Clear (and disable) any previous output buffers to avoid corrupting the file download
if (ob_get_length()) {
    ob_clean();
}
flush();
// Set headers for file download
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($file_path));
// Output the file content
if (readfile($file_path) === false) {
    exit('Error reading file!');
}
exit;
?>