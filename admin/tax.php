<?php
defined('admin') or exit;
// Default input tax values
$tax = [
    'country' => '',
    'rate' => ''
];
if (isset($_GET['id'])) {
    // ID param exists, edit an existing tax
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the tax
        $categories_list = isset($_POST['categories']) ? implode(',', $_POST['categories']) : '';
        $products_list = isset($_POST['products']) ? implode(',', $_POST['products']) : '';
        $stmt = $pdo->prepare('UPDATE taxes SET country = ?, rate = ? WHERE id = ?');
        $stmt->execute([ $_POST['country'], $_POST['rate'], $_GET['id'] ]);
        header('Location: index.php?page=taxes&success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the tax
        header('Location: index.php?page=taxes&delete='.$_GET['id']);
        exit;
    }
    // Get the tax from the database
    $stmt = $pdo->prepare('SELECT * FROM taxes WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $tax = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Create a new tax
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO taxes (country,rate) VALUES (?,?)');
        $stmt->execute([ $_POST['country'], $_POST['rate'] ]);
        header('Location: index.php?page=taxes&success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Tax', 'taxes', 'manage')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Tax</h2>
        <a href="index.php?page=taxes" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this tax?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="country"><span class="required">*</span> Country</label>
            <select name="country" required>
                <?php foreach (get_countries() as $country): ?>
                <option value="<?=$country?>"<?=$country==$tax['country']?' selected':''?>><?=$country?></option>
                <?php endforeach; ?>
            </select>

            <label for="rate"><span class="required">*</span> Rate</label>
            <input id="rate" type="number" name="rate" step=".01" placeholder="Rate %" value="<?=$tax['rate']?>" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>