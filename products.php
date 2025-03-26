<?php
// Prevent direct access to file
defined('shoppingcart') or exit;
// Get all the categories from the database
$stmt = $pdo->query('SELECT * FROM product_categories');
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Execute query to retrieve product options and group by the title
$stmt = $pdo->query('SELECT option_name, option_value FROM product_options WHERE option_type = "select" OR option_type = "radio" OR option_type = "checkbox" GROUP BY option_name, option_value ORDER BY option_name, option_value ASC');
$stmt->execute();
$product_options = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
// Get the current category from the GET request, if none exists set the default selected category to: all
$category_list = isset($_GET['category']) && $_GET['category'] ? $_GET['category'] : [];
$category_list = is_array($category_list) ? $category_list : [$category_list];
$category_sql = '';
if ($category_list) {
    $category_sql = 'JOIN product_category pc ON FIND_IN_SET(pc.category_id, :category_list) AND pc.product_id = p.id JOIN product_categories c ON c.id = pc.category_id';
}
// Get the options from the GET request, if none exists set the default selected options to: all
$options_list = isset($_GET['option']) && $_GET['option'] ? $_GET['option'] : [];
$options_list = is_array($options_list) ? $options_list : [$options_list];
$options_sql = '';
if ($options_list) {
    $options_sql = 'JOIN product_options po ON po.product_id = p.id AND FIND_IN_SET(CONCAT(po.option_name, "-", po.option_value), :option_list)';
}
// Availability options
$availability_list = isset($_GET['availability']) && $_GET['availability'] ? $_GET['availability'] : [];
$availability_list = is_array($availability_list) ? $availability_list : [$availability_list];
$availability_sql = '';
if ($availability_list) {
    $availability_sql = 'AND (p.quantity > 0 OR p.quantity = -1)';
    if (in_array('out-of-stock', $availability_list)) {
        $availability_sql = 'AND p.quantity = 0';
    }
}
// Get price min
$price_min = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? $_GET['price_min'] : '';
// Get price max
$price_max = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? $_GET['price_max'] : '';
$price_sql = '';
// If the price min is set, add the WHERE clause to the SQL query
if ($price_min) {
    $price_sql .= ' AND p.price >= :price_min ';
}
// If the price max is set, add the WHERE clause to the SQL query
if ($price_max) {
    $price_sql .= ' AND p.price <= :price_max ';
}
// Get the sort from GET request, will occur if the user changes an item in the select box
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
// The amounts of products to show on each page
$num_products_on_each_page = 12;
// The current page, in the URL this will appear as index.php?page=products&p=1, index.php?page=products&p=2, etc...
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
// Order by statement
$order_by = '';
// Select products ordered by the date added
if ($sort == 'a-z') {
    // sort1 = Alphabetical A-Z
    $order_by = 'ORDER BY p.title ASC';
} elseif ($sort == 'z-a') {
    // sort2 = Alphabetical Z-A
    $order_by = 'ORDER BY p.title DESC';
} elseif ($sort == 'newest') {
    // sort3 = Newest
    $order_by = 'ORDER BY p.created DESC';
} elseif ($sort == 'oldest') {
    // sort4 = Oldest
    $order_by = 'ORDER BY p.created ASC';
} elseif ($sort == 'highest') {
    // sort5 = Highest Price
    $order_by = 'ORDER BY p.price DESC';
} elseif ($sort == 'lowest') {
    // sort6 = Lowest Price
    $order_by = 'ORDER BY p.price ASC';
} elseif ($sort == 'popular') {
    // sort7 = Most Popular
    $order_by = 'ORDER BY (SELECT COUNT(*) FROM transaction_items ti WHERE ti.item_id = p.id) DESC';
}
$stmt = $pdo->prepare('SELECT p.*, (SELECT m.full_path FROM product_media_map pm JOIN product_media m ON m.id = pm.media_id WHERE pm.product_id = p.id ORDER BY pm.position ASC LIMIT 1) AS img FROM products p ' . $category_sql . ' ' . $options_sql . ' WHERE p.product_status = 1 ' . $price_sql . ' ' . $availability_sql . ' GROUP BY p.id, p.title, p.description, p.price, p.rrp, p.quantity, p.created, p.weight, p.url_slug, p.product_status, p.sku, p.subscription, p.subscription_period, p.subscription_period_type ' . $order_by . ' LIMIT :page,:num_products');
// bindValue will allow us to use integer in the SQL statement, we need to use for LIMIT
if ($category_list) {
    $stmt->bindValue(':category_list', implode(',', $category_list), PDO::PARAM_STR);
}
if ($options_list) {
    $stmt->bindValue(':option_list', implode(',', $options_list), PDO::PARAM_STR);
}
if ($price_min) {
    $stmt->bindValue(':price_min', $price_min, PDO::PARAM_STR);
}
if ($price_max) {
    $stmt->bindValue(':price_max', $price_max, PDO::PARAM_STR);
}
$stmt->bindValue(':page', ($current_page - 1) * $num_products_on_each_page, PDO::PARAM_INT);
$stmt->bindValue(':num_products', $num_products_on_each_page, PDO::PARAM_INT);
$stmt->execute();
// Fetch the products from the database and return the result as an Array
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of products
$stmt = $pdo->prepare('SELECT COUNT(*) FROM (SELECT p.id FROM products p ' . $category_sql . ' ' . $options_sql . ' WHERE p.product_status = 1  ' . $price_sql . ' ' . $availability_sql . ' GROUP BY p.id) q');
if ($category_list) {
    $stmt->bindValue(':category_list', implode(',', $category_list), PDO::PARAM_STR);
}
if ($options_list) {
    $stmt->bindValue(':option_list', implode(',', $options_list), PDO::PARAM_STR);
}
if ($price_min) {
    $stmt->bindValue(':price_min', $price_min, PDO::PARAM_STR);
}
if ($price_max) {
    $stmt->bindValue(':price_max', $price_max, PDO::PARAM_STR);
}
$stmt->execute();
$total_products = $stmt->fetchColumn();
?>
<?=template_header('Products')?>

<div class="products content-wrapper">

    <h1 class="page-title">Products</h1>

    <form action="<?=url('index.php?page=products')?>" method="get" class="products-form form">

        <?php if (!rewrite_url): ?>
        <input type="hidden" name="page" value="products">
        <?php endif; ?>

        <div class="products-filters">

            <?php if ($categories): ?>
            <div class="products-filter">
                <span class="filter-title">Category</span>
                <div class="filter-options checkbox-list">
                    <?=populate_categories($categories, $category_list)?>
                    <?php if (count($categories) > 4): ?>
                    <a href="#" class="show-more">+ Show more</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="products-filter">
                <span class="filter-title">Availability</span>
                <div class="filter-options checkbox-list">
                    <label class="checkbox">
                        <input type="checkbox" name="availability[]" value="in-stock"<?=(in_array('in-stock', $availability_list) ? ' checked' : '')?>>
                        In Stock
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="availability[]" value="out-of-stock"<?=(in_array('out-of-stock', $availability_list) ? ' checked' : '')?>>
                        Out of Stock
                    </label>
                </div>
            </div>

            <?php if ($product_options): ?>
            <?php foreach ($product_options as $option_name => $options): ?>
            <div class="products-filter<?=!in_array($option_name, array_map(function($v) { return explode('-', $v)[0]; }, $options_list)) ? ' closed' : ''?>">
                <span class="filter-title"><?=$option_name?></span>
                <div class="filter-options checkbox-list">
                    <?php foreach ($options as $n => $option): ?>
                    <label class="checkbox<?=$n > 4 ? ' hidden' : ''?>">
                        <input type="checkbox" name="option[]" value="<?=$option_name?>-<?=$option['option_value']?>"<?=(in_array($option_name . '-' . $option['option_value'], $options_list) ? ' checked' : '')?>>
                        <?=$option['option_value']?>
                    </label>
                    <?php endforeach; ?>
                    <?php if (count($options) > 4): ?>
                    <a href="#" class="show-more">+ Show more</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <div class="products-filter">
                <span class="filter-title">Price</span>
                <div class="filter-options price-range">
                    <input type="number" step=".01" min="0" name="price_min" placeholder="Min" value="<?=htmlspecialchars($price_min, ENT_QUOTES)?>" class="form-input">
                    <span>to</span>
                    <input type="number" step=".01" min="0" name="price_max" placeholder="Max" value="<?=htmlspecialchars($price_max, ENT_QUOTES)?>" class="form-input">
                </div>
            </div>

        </div>

        <div class="products-view">

            <div class="products-header">
                <p><?=$total_products?> Product<?=$total_products!=1?'s':''?></p>
                <div class="products-form form">
                    <?php if (!rewrite_url): ?>
                    <input type="hidden" name="page" value="products">
                    <?php endif; ?>
                    <label class="sortby form-select" for="sort">
                        Sort by:
                        <select name="sort" id="sort">
                            <option value="a-z"<?=($sort == 'a-z' ? ' selected' : '')?>>Alphabetically, A-Z</option>
                            <option value="z-a"<?=($sort == 'z-a' ? ' selected' : '')?>>Alphabetically, Z-A</option>
                            <option value="newest"<?=($sort == 'newest' ? ' selected' : '')?>>Date, new to old</option>
                            <option value="oldest"<?=($sort == 'oldest' ? ' selected' : '')?>>Date, old to new</option>
                            <option value="highest"<?=($sort == 'highest' ? ' selected' : '')?>>Price, high to low</option>
                            <option value="lowest"<?=($sort == 'lowest' ? ' selected' : '')?>>Price, low to high</option>
                            <option value="popular"<?=($sort == 'popular' ? ' selected' : '')?>>Most Popular</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="products-wrapper">
                <?php foreach ($products as $product): ?>
                <a href="<?=url('index.php?page=product&id=' . ($product['url_slug'] ? $product['url_slug']  : $product['id']))?>" class="product<?=$product['quantity']==0?' no-stock':''?>">
                    <?php if (!empty($product['img']) && file_exists($product['img'])): ?>
                    <div class="img">
                        <img src="<?=base_url?><?=$product['img']?>" width="180" height="180" alt="<?=$product['title']?>">
                    </div>
                    <?php endif; ?>
                    <span class="name"><?=$product['title']?></span>
                    <span class="price">
                        <?=currency_code?><?=num_format($product['price'],2)?>
                        <?php if ($product['rrp'] > 0): ?>
                        <span class="rrp"><?=currency_code?><?=num_format($product['rrp'],2)?></span>
                        <?php endif; ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="buttons">
                <?php if ($current_page > 1): ?>
                <?php
                $_GET['p'] = $current_page-1;
                $query = http_build_query($_GET);
                ?>
                <a href="?<?=$query?>" class="btn">Prev</a>
                <?php endif; ?>
                <?php if ($total_products > (($current_page+1) * $num_products_on_each_page) - $num_products_on_each_page): ?>
                <?php
                $_GET['p'] = $current_page+1;
                $query = http_build_query($_GET);
                ?>
                <a href="?<?=$query?>" class="btn">Next</a>
                <?php endif; ?>
            </div>

        </div>

    </form>

</div>

<?=template_footer()?>