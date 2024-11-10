<?php
defined('admin') or exit;
// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_FILES['file'], $_POST['table']) && !empty($_FILES['file']['tmp_name'])) {
    $table = in_array($_POST['table'], ['transactions', 'transactions_items']) ? $_POST['table'] : 'transactions';
    // check type
    $type = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $data = [];
    if ($type == 'csv') {
        $file = fopen($_FILES['file']['tmp_name'], 'r');
        $header = fgetcsv($file);
        while ($row = fgetcsv($file)) {
            $data[] = array_combine($header, $row);
        }
        fclose($file);
    } elseif ($type == 'json') {
        $data = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
    } elseif ($type == 'xml') {
        $xml = simplexml_load_file($_FILES['file']['tmp_name']);
        $data = json_decode(json_encode($xml), true)['item'];
    } elseif ($type == 'txt') {
        $file = fopen($_FILES['file']['tmp_name'], 'r');
        while ($row = fgetcsv($file)) {
            $data[] = $row;
        }
        fclose($file);
    }
    // insert into database
    if (isset($data) && !empty($data)) {    
        $i = 0;   
        foreach ($data as $k => $row) {
            // convert array to question marks for prepared statements
            $values = array_fill(0, count($row), '?');
            $values = implode(',', $values);
            // Convert date to MySQL format, if you have more datetime columns, add them here
            if (isset($row['created'])) {
                $row['created'] = date('Y-m-d H:i:s', strtotime(str_replace('/','-', $row['created'])));
            }
            // insert into database
            // tip: if you want to update existing records, use INSERT ... ON DUPLICATE KEY UPDATE instead
            $stmt = $pdo->prepare('INSERT IGNORE INTO ' . $table . ' VALUES (' . $values . ')');
            $stmt->execute(array_values($row));
            $i += $stmt->rowCount();
        }
        header('Location: index.php?page=orders&success_msg=4&imported=' . $i);
        exit;
    }
}
?>
<?=template_admin_header('Import Orders', 'orders', 'import')?>

<form action="" method="post" enctype="multipart/form-data">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100">Import Orders</h2>
        <a href="index.php?page=orders" class="btn alt mar-right-2">Cancel</a>
        <input type="submit" name="submit" value="Import" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="table"><span class="required">*</span> Table</label>
            <select id="table" name="table" required>
                <option value="transactions">Transactions</option>
                <option value="transactions_items">Transactions Items</option>
            </select>

            <label for="file"><span class="required">*</span> File</label>
            <input type="file" name="file" id="file" accept=".csv,.json,.xml,.txt" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>