<?php
defined('shoppingcart_admin') or exit;
// Remove the time limit (for media uploads)
set_time_limit(0);
// Media Endpoint
if (isset($_GET['action']) && $_GET['action'] == 'media') {
    // Upload media
    if (isset($_POST['total_files']) && (int)$_POST['total_files'] > 0) {
        // Iterate the uploaded files
        for ($i = 0; $i < (int)$_POST['total_files']; $i++) {
            // Ensure the file exists
            if (isset($_FILES['file_' . $i]) && !empty($_FILES['file_' . $i]['tmp_name'])) {
                $file_name = $_FILES['file_' . $i]['name'];
                // Rename file if file exists with same name
                $j = 1;
                while (file_exists('../uploads/' . $file_name)) {           
                    $file_name = $j . '-' . $_FILES['file_' . $i]['name'];
                    $j++;
                }
                $media_path = '../uploads/' . $file_name;
                move_uploaded_file($_FILES['file_' . $i]['tmp_name'], $media_path);
                $stmt = $pdo->prepare('INSERT INTO product_media (title, caption, date_uploaded, full_path) VALUES (?, ?, ?, ?)');
                $stmt->execute([ $file_name, '', date('Y-m-d H:i:s'), substr($media_path, strlen('../')) ]);
            }
        }
    }
    // Select media
    if (isset($_GET['id'])) {
        // Retrieve media by id
        $stmt = $pdo->prepare('SELECT * FROM product_media WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Update media
    if (isset($_GET['id'], $_POST['title'])) {
        // Determine full path
        $full_path = $media['full_path'];
        // If captured path is different from original path, attempt to move and rename the file
        if ($media['full_path'] != $_POST['full_path']) {
            if (!is_dir(dirname('../' . $_POST['full_path']))) {
                mkdir(dirname('../' . $_POST['full_path']), 0777, true);
            }
            if (rename('../' . $media['full_path'], '../' . $_POST['full_path'])) {
                $full_path = $_POST['full_path'];
            }
        }
        // Update media in the database
        $stmt = $pdo->prepare('UPDATE product_media SET title = ?, caption = ?, date_uploaded = ?, full_path = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['caption'], date('Y-m-d H:i:s', strtotime($_POST['date_uploaded'])), $full_path, $_GET['id'] ]);      
    }
    // Delete media
    if (isset($_GET['id'], $_GET['delete'])) {
        // Delete media file
        unlink('../' . $media['full_path']);
        // Delete from database
        $stmt = $pdo->prepare('DELETE m, pm FROM product_media m LEFT JOIN product_media_map pm ON pm.media_id = m.id WHERE m.id = ?');
        $stmt->execute([ $_GET['id'] ]);
    }
    // Get all media from database
    $stmt = $pdo->prepare('SELECT *, DATE_FORMAT(date_uploaded, "%Y-%m-%d %H:%i") AS date_uploaded FROM product_media ORDER BY date_uploaded DESC');
    $stmt->execute();
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Output JSON
    header('Content-Type: application/json; charset=utf-8');
    // Encode results to JSON format
    echo json_encode($media);
}
// Digital Downloads Endpoint
if (isset($_GET['action']) && $_GET['action'] == 'fileexists' && $_GET['path']) {
    // Output JSON
    header('Content-Type: application/json; charset=utf-8');
    // Check if the file exists
    if (!file_exists('../' . $_GET['path']) || !is_file('../' . $_GET['path'])) {
        echo '{"result":"The file does not exist!"}';
    } else {
        echo '{"result":""}';
    }
}
// Get customer details endpoint
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'get_customer_details') {
    // Output JSON
    header('Content-Type: application/json; charset=utf-8');
    // check if transaction exists - we can retrieve the customer details from the transaction
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$transaction) {
        echo '{"status":"error","message":"Transaction not found!"}';
        exit;
    }
    // get account associated with the transaction
    $stmt = $pdo->prepare('SELECT a.*, (SELECT COUNT(*) FROM transactions t WHERE t.account_id = a.id) AS total_orders FROM accounts a WHERE a.id = ?');
    $stmt->execute([ $transaction['account_id'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // Output JSON
    echo json_encode(['transaction' => $transaction, 'account' => $account]);
}
?>