<?php
// Prevent direct access to file
defined('shoppingcart') or exit;
// Get the 4 most recent added products
$stmt = $pdo->prepare('SELECT p.*, (SELECT m.full_path FROM products_media pm JOIN media m ON m.id = pm.media_id WHERE pm.product_id = p.id ORDER BY pm.position ASC LIMIT 1) AS img FROM products p WHERE p.product_status = 1 ORDER BY p.created DESC LIMIT 4');
$stmt->execute();
$recently_added_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_header('Home')?>

<div class="featured" style="background-image:url(<?=featured_image?>)">

    <h2>Gadgets</h2>

    <p>Essential gadgets for everyday use</p>

</div>

<div class="recentlyadded content-wrapper">

    <h2>Recently Added Products</h2>

    <div class="products">

        <?php foreach ($recently_added_products as $product): ?>
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

<?=template_footer()?>