<?php
defined('shoppingcart_admin') or exit;
// Default input discount values
$discount = [
    'category_ids' => '',
    'product_ids' => '',
    'discount_code' => '',
    'discount_type' => 'Percentage',
    'discount_value' => '',
    'start_date' => date('Y-m-d\TH:i'),
    'end_date' => date('Y-m-d\TH:i', strtotime('+1 month', strtotime(date('Y-m-d\TH:i')))), 
    'categories' => [],
    'products' => []
];
$types = ['Percentage', 'Fixed'];
// Get all the categories from the database
$stmt = $pdo->query('SELECT id, title FROM product_categories');
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get all the products from the database
$stmt = $pdo->query('SELECT id, title FROM products');
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['id'])) {
    // ID param exists, edit an existing discount
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the discount
        $categories_list = isset($_POST['categories']) ? implode(',', $_POST['categories']) : '';
        $products_list = isset($_POST['products']) ? implode(',', $_POST['products']) : '';
        $stmt = $pdo->prepare('UPDATE discounts SET category_ids = ?, product_ids = ?, discount_code = ?, discount_type = ?, discount_value = ?, start_date = ?, end_date = ? WHERE id = ?');
        $stmt->execute([ $categories_list, $products_list, $_POST['discount_code'], $_POST['discount_type'], $_POST['discount_value'], date('Y-m-d H:i:s', strtotime($_POST['start_date'])), date('Y-m-d H:i:s', strtotime($_POST['end_date'])), $_GET['id'] ]);
        // Remove session discount code
        if (isset($_SESSION['discount'])) {
            unset($_SESSION['discount']);
        }
        header('Location: index.php?page=discounts&success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the discount
        header('Location: index.php?page=discounts&delete='.$_GET['id']);
        exit;
    }
    // Get the discount from the database
    $stmt = $pdo->prepare('SELECT * FROM discounts WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get the discount categories
    $stmt = $pdo->prepare('SELECT c.title, c.id FROM discounts d JOIN product_categories c ON FIND_IN_SET(c.id, d.category_ids) WHERE d.id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $discount['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get the discount products
    $stmt = $pdo->prepare('SELECT p.title, p.id FROM discounts d JOIN products p ON FIND_IN_SET(p.id, d.product_ids) WHERE d.id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $discount['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Create a new discount
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $categories_list = isset($_POST['categories']) ? implode(',', $_POST['categories']) : '';
        $products_list = isset($_POST['products']) ? implode(',', $_POST['products']) : '';
        $stmt = $pdo->prepare('INSERT INTO discounts (category_ids,product_ids,discount_code,discount_type,discount_value,start_date,end_date) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([ $categories_list, $products_list, $_POST['discount_code'], $_POST['discount_type'], $_POST['discount_value'], date('Y-m-d H:i:s', strtotime($_POST['start_date'])), date('Y-m-d H:i:s', strtotime($_POST['end_date'])) ]);
        // Remove session discount code
        if (isset($_SESSION['discount'])) {
            unset($_SESSION['discount']);
        }
        header('Location: index.php?page=discounts&success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Discount', 'discounts', 'manage')?>

<form method="post">

    <div class="content-title">
        <h2><?=$page?> Discount</h2>
        <div class="btns">
            <a href="index.php?page=discounts" class="btn alt mar-right-1">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn red mar-right-1" onclick="return confirm('Are you sure you want to delete this discount?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn">
        </div>
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="code"><span class="required">*</span> Code</label>
            <input id="code" type="text" name="discount_code" placeholder="Code" value="<?=$discount['discount_code']?>" required>

            <label for="categories">Categories</label>
            <div class="multiselect" data-name="categories[]">
                <?php foreach ($discount['categories'] as $cat): ?>
                <span class="item" data-value="<?=$cat['id']?>">
                    <i class="remove">&times;</i><?=$cat['title']?>
                    <input type="hidden" name="categories[]" value="<?=$cat['id']?>">
                </span>
                <?php endforeach; ?>
                <input type="text" class="search" id="categories" placeholder="Categories">
                <div class="list">
                    <?php foreach ($categories as $cat): ?>
                    <span data-value="<?=$cat['id']?>"><?=$cat['title']?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <label for="products">Products</label>
            <div class="multiselect" data-name="products[]">
                <?php foreach ($discount['products'] as $product): ?>
                <span class="item" data-value="<?=$product['id']?>">
                    <i class="remove">&times;</i><?=$product['title']?>
                    <input type="hidden" name="products[]" value="<?=$product['id']?>">
                </span>
                <?php endforeach; ?>
                <input type="text" class="search" id="products" placeholder="Products">
                <div class="list">
                    <?php foreach ($products as $product): ?>
                    <span data-value="<?=$product['id']?>"><?=$product['title']?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <label for="type"><span class="required">*</span> Type</label>
            <select id="type" name="discount_type">
                <?php foreach ($types as $type): ?>
                <option value="<?=$type?>"<?=$discount['discount_type']==$type?' selected':''?>><?=$type?></option>
                <?php endforeach; ?>
            </select>

            <label for="discount_value"><span class="required">*</span> Value</label>
            <input id="discount_value" type="number" name="discount_value" placeholder="Value" min="0" step=".01" value="<?=$discount['discount_value']?>" required>

            <div class="group">
                <div class="item">
                    <label for="start_date"><span class="required">*</span> Start Date</label>
                    <input id="start_date" type="datetime-local" name="start_date" placeholder="Start Date" value="<?=date('Y-m-d\TH:i', strtotime($discount['start_date']))?>" required>
                </div>
                <div class="item">
                    <label for="end_date"><span class="required">*</span> End Date</label>
                    <input id="end_date" type="datetime-local" name="end_date" placeholder="End Date" value="<?=date('Y-m-d\TH:i', strtotime($discount['end_date']))?>" required>
                </div>
            </div>

        </div>

    </div>

</form>

<?=template_admin_footer()?>