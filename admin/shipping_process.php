<?php
defined('admin') or exit;
// Default input shipping values
$shipping = [
    'title' => '',
    'price_from' => '',
    'price_to' => '',
    'weight_from' => '',
    'weight_to' => '',
    'price' => '',
    'shipping_type' => 'Single Product',
    'countries' => ''
];
$types = ['Single Product', 'Entire Order'];
if (isset($_GET['id'])) {
    // ID param exists, edit an existing shipping method
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the shipping method
        $countries_list = isset($_POST['countries']) ? implode(',', $_POST['countries']) : '';
        $stmt = $pdo->prepare('UPDATE shipping SET title = ?, price_from = ?, price_to = ?, weight_from = ?, weight_to = ?, price = ?, shipping_type = ?, countries = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['price_from'], $_POST['price_to'], $_POST['weight_from'], $_POST['weight_to'], $_POST['price'], $_POST['type'], $countries_list, $_GET['id'] ]);
        header('Location: index.php?page=shipping&success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete shipping method
        header('Location: index.php?page=shipping&delete=' . $_GET['id']);
        exit;
    }
    // Get the shipping method from the database
    $stmt = $pdo->prepare('SELECT * FROM shipping WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $shipping = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Create a new shipping method
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $countries_list = isset($_POST['countries']) ? implode(',', $_POST['countries']) : '';
        $stmt = $pdo->prepare('INSERT INTO shipping (title, price_from, price_to, weight_from, weight_to, price, shipping_type, countries) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['title'], $_POST['price_from'], $_POST['price_to'], $_POST['weight_from'], $_POST['weight_to'], $_POST['price'], $_POST['type'], $countries_list ]);
        header('Location: index.php?page=shipping&success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Shipping Method', 'shipping', 'manage')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Shipping Method</h2>
        <a href="index.php?page=shipping" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this shipping method?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="title"><span class="required">*</span> Title</label>
            <input type="text" name="title" placeholder="Title" value="<?=$shipping['title']?>" required>

            <label for="type"><span class="required">*</span> Type</label>
            <select name="type" id="type" required>
                <?php foreach ($types as $type): ?>
                <option value="<?=$type?>"<?=$shipping['shipping_type'] == $type ? ' selected' : ''?>><?=$type?></option>
                <?php endforeach; ?>
            </select>

            <label for="countries">Countries</label>
            <div class="multiselect" data-name="countries[]">
                <?php foreach (explode(',', $shipping['countries']) as $c): ?>
                <?php if (empty($c)) continue; ?>
                <span class="item" data-value="<?=$c?>">
                    <i class="remove">&times;</i><?=$c?>
                    <input type="hidden" name="countries[]" value="<?=$c?>">
                </span>
                <?php endforeach; ?>
                <input type="text" class="search" id="countries" placeholder="Countries">
                <div class="list">
                    <?php foreach (get_countries() as $country): ?>
                    <span data-value="<?=$country?>"><?=$country?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <label for="price"><span class="required">*</span> Product Price Range</label>
            <div style="display:flex;margin:0;">
                <input type="number" name="price_from" placeholder="From" min="0" step=".01" value="<?=$shipping['price_from']?>" required>
                <span style="padding-top:15px">&nbsp;&nbsp;&nbsp;&mdash;&nbsp;&nbsp;&nbsp;</span>
                <input type="number" name="price_to" placeholder="To" min="0" step=".01" value="<?=$shipping['price_to']?>" required>
            </div>

            <label for="price"><span class="required">*</span> Product Weight Range (<?=weight_unit?>)</label>
            <div style="display:flex;margin:0;">
                <input type="number" name="weight_from" placeholder="From" min="0" step=".01" value="<?=$shipping['weight_from']?>" required>
                <span style="padding-top:15px">&nbsp;&nbsp;&nbsp;&mdash;&nbsp;&nbsp;&nbsp;</span>
                <input type="number" name="weight_to" placeholder="To" min="0" step=".01" value="<?=$shipping['weight_to']?>" required>
            </div>

            <label for="name"><span class="required">*</span> Total Shipping Price</label>
            <input type="number" name="price" placeholder="3.99" min="0" step=".01" value="<?=$shipping['price']?>" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>