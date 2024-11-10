<?php
// Prevent direct access to file
defined('shoppingcart') or exit;
// Check for search query
if (isset($_GET['query']) && $_GET['query'] != '') {
    // Escape the user query, prevent XSS attacks
    $search_query = htmlspecialchars($_GET['query'], ENT_QUOTES);
    // Select products ordered by the date added
    $stmt = $pdo->prepare('SELECT p.*, (SELECT m.full_path FROM products_media pm JOIN media m ON m.id = pm.media_id WHERE pm.product_id = p.id ORDER BY pm.position ASC LIMIT 1) AS img FROM products p WHERE p.product_status = 1 AND p.title LIKE ? ORDER BY p.created DESC');
    // bindValue will allow us to use integer in the SQL statement, we need to use for LIMIT
    $stmt->execute(['%' . $search_query . '%']);
    // Fetch the products from the database and return the result as an Array
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get the total number of products
    $total_products = count($products);
} else {
    // Simple error, if no search query was specified why is the user on this page?
    $error = 'No search query was specified!';
}
?>
<?=template_header('Search')?>

<?php if ($error): ?>

<p class="content-wrapper error"><?=$error?></p>

<?php else: ?>

<div class="products content-wrapper">

    <h1 class="page-title">Search Results for "<?=$search_query?>"</h1>

    <p><?=$total_products?> Product<?=$total_products!=1?'s':''?></p>

    <div class="products-wrapper">
        <?php foreach ($products as $product): ?>
        <a href="<?=url('index.php?page=product&id=' . ($product['url_slug'] ? $product['url_slug']  : $product['id']))?>" class="product<?=$product['quantity']==0?' no-stock':''?>">
            <?php if (!empty($product['img']) && file_exists($product['img'])): ?>
            <div class="img">
                <img src="<?=base_url?><?=$product['img']?>" width="200" height="200" alt="<?=$product['title']?>">
            </div>
            <?php endif; ?>
            <span class="name"><?=$product['title']?></span>
            <span class="price">
                <?=currency_code?><?=number_format($product['price'],2)?>
                <?php if ($product['rrp'] > 0): ?>
                <span class="rrp"><?=currency_code?><?=number_format($product['rrp'],2)?></span>
                <?php endif; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

</div>

<?php endif; ?>

<?=template_footer()?>