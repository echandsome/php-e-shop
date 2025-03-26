<?php
defined('shoppingcart_admin') or exit;
// Default input product values
$product = [
    'title' => '',
    'description' => '',
    'price' => '',
    'rrp' => '',
    'quantity' => '',
    'created' => date('Y-m-d\TH:i'),
    'media' => [],
    'categories' => [],
    'options' => [],
    'downloads' => [],
    'weight' => '',
    'url_slug' => '',
    'product_status' => 1,
    'sku' => '',
    'subscription' => 0,
    'subscription_period' => 1,
    'subscription_period_type' => 'day'
];
// Get all the categories from the database
$stmt = $pdo->query('SELECT * FROM product_categories');
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Add product images to the database
function addProductImages($pdo, $product_id) {
    // Get the total number of media
    if (isset($_POST['media']) && is_array($_POST['media']) && count($_POST['media']) > 0) {
        // Iterate media
        $delete_list = [];
        for ($i = 0; $i < count($_POST['media']); $i++) {
            // If the media doesnt exist in the database
            if (!intval($_POST['media_product_id'][$i])) {
                // Insert new media
                $stmt = $pdo->prepare('INSERT INTO product_media_map (product_id,media_id,position) VALUES (?,?,?)');
                $stmt->execute([ $product_id, $_POST['media'][$i], $_POST['media_position'][$i] ]);
                $delete_list[] = $pdo->lastInsertId();
            } else {
                // Update existing media
                $stmt = $pdo->prepare('UPDATE product_media_map SET position = ? WHERE id = ?');
                $stmt->execute([ $_POST['media_position'][$i], $_POST['media_product_id'][$i] ]);    
                $delete_list[] = $_POST['media_product_id'][$i];          
            }
        }
        // Delete media
        $in  = str_repeat('?,', count($delete_list) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM product_media_map WHERE product_id = ? AND id NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $product_id ], $delete_list));
    } else {
        // No media exists, delete all
        $stmt = $pdo->prepare('DELETE FROM product_media_map WHERE product_id = ?');
        $stmt->execute([ $product_id ]);       
    }
}
// Add product categories to the database
function addProductCategories($pdo, $product_id) {
    if (isset($_POST['categories']) && is_array($_POST['categories']) && count($_POST['categories']) > 0) {
        $in  = str_repeat('?,', count($_POST['categories']) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM product_category WHERE product_id = ? AND category_id NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $product_id ], $_POST['categories']));
        foreach ($_POST['categories'] as $cat) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO product_category (product_id,category_id) VALUES (?,?)');
            $stmt->execute([ $product_id, $cat ]);
        }
    } else {
        $stmt = $pdo->prepare('DELETE FROM product_category WHERE product_id = ?');
        $stmt->execute([ $product_id ]);       
    }
}
// Add product options to the database
function addProductOptions($pdo, $product_id) {
    if (isset($_POST['option_name']) && is_array($_POST['option_name']) && count($_POST['option_name']) > 0) {
        $delete_list = [];
        for ($i = 0; $i < count($_POST['option_name']); $i++) {
            $delete_list[] = $_POST['option_name'][$i] . '__' . $_POST['option_value'][$i];
            $qty = empty($_POST['option_quantity'][$i]) && (int)$_POST['option_quantity'][$i] != 0 ? -1 : $_POST['option_quantity'][$i];
            $stmt = $pdo->prepare('INSERT INTO product_options (option_name,option_value,quantity,price,price_modifier,weight,weight_modifier,option_type,required,position,product_id) VALUES (?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), price = VALUES(price), price_modifier = VALUES(price_modifier), weight = VALUES(weight), weight_modifier = VALUES(weight_modifier), option_type = VALUES(option_type), required = VALUES(required), position = VALUES(position)');
            $stmt->execute([ $_POST['option_name'][$i], $_POST['option_value'][$i], $qty, empty($_POST['option_price'][$i]) ? 0.00 : $_POST['option_price'][$i], $_POST['option_price_modifier'][$i], empty($_POST['option_weight'][$i]) ? 0.00 : $_POST['option_weight'][$i], $_POST['option_weight_modifier'][$i], $_POST['option_type'][$i], $_POST['option_required'][$i], $_POST['option_position'][$i], $product_id ]);           
        }
        $in  = str_repeat('?,', count($delete_list) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM product_options WHERE product_id = ? AND CONCAT(option_name, "__", option_value) NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $product_id ], $delete_list));  
    } else {
        $stmt = $pdo->prepare('DELETE FROM product_options WHERE product_id = ?');
        $stmt->execute([ $product_id ]);       
    }
}
// Add product downloads to the database
function addProductDownloads($pdo, $product_id) {
    if (isset($_POST['download_file_path']) && is_array($_POST['download_file_path']) && count($_POST['download_file_path']) > 0) {
        $delete_list = [];
        for ($i = 0; $i < count($_POST['download_file_path']); $i++) {
            $delete_list[] = $_POST['download_file_path'][$i];
            $stmt = $pdo->prepare('INSERT INTO product_downloads (product_id,file_path,position) VALUES (?,?,?) ON DUPLICATE KEY UPDATE position = VALUES(position)');
            $stmt->execute([ $product_id, $_POST['download_file_path'][$i], $_POST['download_position'][$i] ]);           
        }
        $in  = str_repeat('?,', count($delete_list) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM product_downloads WHERE product_id = ? AND file_path NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $product_id ], $delete_list));  
    } else {
        $stmt = $pdo->prepare('DELETE FROM product_downloads WHERE product_id = ?');
        $stmt->execute([ $product_id ]);       
    }
}
if (isset($_GET['id'])) {
    // ID param exists, edit an existing product
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the product
        $stmt = $pdo->prepare('UPDATE products SET title = ?, description = ?, price = ?, rrp = ?, quantity = ?, created = ?, weight = ?, url_slug = ?, product_status = ?, sku = ?, subscription = ?, subscription_period = ?, subscription_period_type = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['description'], empty($_POST['price']) ? 0.00 : $_POST['price'], empty($_POST['rrp']) ? 0.00 : $_POST['rrp'], $_POST['quantity'], date('Y-m-d H:i:s', strtotime($_POST['date'])), empty($_POST['weight']) ? 0.00 : $_POST['weight'], $_POST['url_slug'], $_POST['status'], $_POST['sku'], $_POST['subscription'], empty($_POST['subscription_period']) ? 0 : $_POST['subscription_period'], $_POST['subscription_period_type'], $_GET['id'] ]);
        addProductImages($pdo, $_GET['id']);
        addProductCategories($pdo, $_GET['id']);
        addProductOptions($pdo, $_GET['id']);
        addProductDownloads($pdo, $_GET['id']);
        // Clear session cart
        if (isset($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
        header('Location: index.php?page=products&success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete product
        header('Location: index.php?page=products&delete=' . $_GET['id']);
        exit;
    }
    // Get the product and its images from the database
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    // get product media
    $stmt = $pdo->prepare('SELECT m.*, pm.position, pm.id AS product_id FROM product_media m JOIN product_media_map pm ON pm.media_id = m.id JOIN products p ON p.id = pm.product_id WHERE p.id = ? ORDER BY pm.position');
    $stmt->execute([ $_GET['id'] ]);
    $product['media'] = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    // Get the product categories
    $stmt = $pdo->prepare('SELECT c.title, c.id FROM product_category pc JOIN product_categories c ON c.id = pc.category_id WHERE pc.product_id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $product['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get the product options
    $stmt = $pdo->prepare('SELECT option_name, option_type, GROUP_CONCAT(option_value ORDER BY id) AS list FROM product_options WHERE product_id = ? GROUP BY option_name, option_type, position ORDER BY position');
    $stmt->execute([ $_GET['id'] ]);
    $product['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get the product full options
    $stmt = $pdo->prepare('SELECT * FROM product_options WHERE product_id = ? ORDER BY id');
    $stmt->execute([ $_GET['id'] ]);
    $product['options_full'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get the product downloads
    $stmt = $pdo->prepare('SELECT * FROM product_downloads WHERE product_id = ? ORDER BY position');
    $stmt->execute([ $_GET['id'] ]);
    $product['downloads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Create a new product
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO products (title,description,price,rrp,quantity,created,weight,url_slug,product_status,sku,subscription,subscription_period,subscription_period_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['title'], $_POST['description'], empty($_POST['price']) ? 0.00 : $_POST['price'], empty($_POST['rrp']) ? 0.00 : $_POST['rrp'], $_POST['quantity'], date('Y-m-d H:i:s', strtotime($_POST['date'])), empty($_POST['weight']) ? 0.00 : $_POST['weight'], $_POST['url_slug'], $_POST['status'], $_POST['sku'], $_POST['subscription'], empty($_POST['subscription_period']) ? 0 : $_POST['subscription_period'], $_POST['subscription_period_type'] ]);
        $id = $pdo->lastInsertId();
        addProductImages($pdo, $id);
        addProductCategories($pdo, $id);
        addProductOptions($pdo, $id);
        addProductDownloads($pdo, $id);
        // Clear session cart
        if (isset($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
        header('Location: index.php?page=products&success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Product', 'products', 'manage')?>

<form method="post">

    <div class="content-title">
        <h2 class="responsive-width-100"><?=$page?> Product</h2>
        <div class="btns">
            <a href="index.php?page=products" class="btn alt mar-right-1">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn red mar-right-1" onclick="return confirm('Are you sure you want to delete this product?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn">
        </div>
    </div>

    <div class="tabs">
        <a href="#" class="active">General</a>
        <a href="#">Media</a>
        <a href="#">Options</a>
        <a href="#">Downloads</a>
        <a href="#">Subscription</a>
    </div>

    <!-- general tab -->
    <div class="content-block tab-content active">

        <div class="form responsive-width-100 size-md">

            <div class="group">
                <div class="item">
                    <label for="title"><span class="required">*</span> Title</label>
                    <input id="title" type="text" name="title" placeholder="Title" value="<?=$product['title']?>" required>
                </div>
                <div class="item">
                    <label for="url_slug">URL Slug</label>
                    <input id="url_slug" type="text" name="url_slug" placeholder="your-product-name" value="<?=$product['url_slug']?>" title="If the rewrite URL setting is enabled, the URL slug will appear after the trailing slash as opposed to the product ID.">
                </div>
            </div>

            <label for="description">Description</label>
            <?php if (template_editor == 'tinymce'): ?>
            <div style="width:100%;margin:15px 0 25px;">
                <textarea id="description" name="description" style="width:100%;height:400px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?=$product['description']?></textarea>
            </div>
            <?php else: ?>
            <textarea id="description" name="description" placeholder="Product Description..."><?=$product['description']?></textarea>
            <?php endif; ?>

            <label for="sku">SKU</label>
            <input id="sku" type="text" name="sku" placeholder="SKU" value="<?=$product['sku']?>">

            <div class="group">
                <div class="item">
                    <label for="price"><span class="required">*</span> Price</label>
                    <input id="price" type="number" name="price" placeholder="Price" min="0" step=".01" value="<?=$product['price']?>" required>
                </div>
                <div class="item">
                    <label for="rrp">RRP</label>
                    <input id="rrp" type="number" name="rrp" placeholder="RRP" min="0" step=".01" value="<?=$product['rrp']?>">
                </div>
            </div>

            <div class="group">
                <div class="item">
                    <label for="quantity"><span class="required">*</span> Quantity</span></label>
                    <input id="quantity" type="number" name="quantity" placeholder="Quantity" min="-1" value="<?=$product['quantity']?>" title="-1 = unlimited" required>
                </div>
                <div class="item pad-top-5">
                    <label for="unlimited" class="switch">
                        <input type="checkbox" id="unlimited" name="unlimited" class="switch" value="1"<?=$product['quantity'] == -1 ? ' checked' : ''?>>
                        <span class="slider round"></span>
                        <span class="txt">Unlimited Stock</span>
                    </label>
                </div>
            </div>

            <label for="category">Categories</label>
            <div class="multiselect" data-name="categories[]">
                <?php foreach ($product['categories'] as $cat): ?>
                <span class="item" data-value="<?=$cat['id']?>">
                    <i class="remove">&times;</i><?=$cat['title']?>
                    <input type="hidden" name="categories[]" value="<?=$cat['id']?>">
                </span>
                <?php endforeach; ?>
                <input type="text" class="search" id="category" placeholder="Categories">
                <div class="list">
                    <?php foreach ($categories as $cat): ?>
                    <span data-value="<?=$cat['id']?>"><?=$cat['title']?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <label for="weight">Weight (<?=weight_unit?>)</span></label>
            <input id="weight" type="number" name="weight" placeholder="Weight (<?=weight_unit?>)" min="0" step=".01" value="<?=$product['weight']?>">

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="1"<?=$product['product_status']==1?' selected':''?>>Enabled</option>
                <option value="0"<?=$product['product_status']==0?' selected':''?>>Disabled</option>
            </select>

            <label for="date"><span class="required">*</span> Date</label>
            <input id="date" type="datetime-local" name="date" placeholder="Date" value="<?=date('Y-m-d\TH:i', strtotime($product['created']))?>" required>

        </div>

    </div>

    <!-- product media tab -->
    <div class="content-block tab-content">

        <div class="pad-3 product-media-tab responsive-width-100">

            <h3 class="title1 mar-bot-5">Images</h3>

            <div class="product-media-container">
                <?php if (isset($product['media'])): ?>
                <?php foreach ($product['media'] as $i => $media): ?>
                <div class="product-media">
                    <span class="media-index responsive-hidden"><?=$i+1?></span>
                    <a class="media-img" href="../<?=$media['full_path']?>" target="_blank">
                        <img src="../<?=$media['full_path']?>" alt="<?=basename($media['full_path'])?>">
                    </a>
                    <div class="media-text">
                        <h3 class="responsive-hidden"><?=$media['title']?></h3>
                        <p class="responsive-hidden"><?=$media['caption']?></p>
                    </div>
                    <div class="media-position">
                        <a href="#" class="media-delete" title="Delete">
                            <svg width="22" height="22" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                        </a>
                        <a href="#" class="move-up" title="Move Up">
                            <svg width="26" height="26" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7.41,15.41L12,10.83L16.59,15.41L18,14L12,8L6,14L7.41,15.41Z" /></svg>
                        </a>
                        <a href="#" class="move-down" title="Move Down">
                            <svg width="26" height="26" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" /></svg>
                        </a>
                    </div>
                    <input type="hidden" class="input-media-id" name="media[]" value="<?=$media['id']?>">
                    <input type="hidden" class="input-media-product-id" name="media_product_id[]" value="<?=$media['product_id']?>">
                    <input type="hidden" class="input-media-position" name="media_position[]" value="<?=$media['position']?>">
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($product['media'])): ?>
                <p class="no-images-msg">There are no images.</p>
                <?php endif; ?>
            </div>

            <a href="#" class="btn open-media-library-modal mar-bot-2 mar-top-4">
                <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
                Add Media
            </a>

        </div>

    </div>

    <!-- options tab -->
    <div class="content-block tab-content">

        <div class="pad-3 product-options-tab responsive-width-100">

            <h3 class="title1 mar-bot-5">Options</h3>

            <div class="product-options-container">
                <?php if (isset($product['options'])): ?>
                <?php foreach ($product['options'] as $i => $option): ?>
                <div class="product-option">
                    <span class="option-index responsive-hidden"><?=$i+1?></span>
                    <div class="option-text">
                        <h3><?=$option['option_name']?> (<?=$option['option_type']?>)</h3>
                        <p><?=str_replace(',', ', ', $option['list'])?></p>
                    </div>
                    <div class="option-position">
                        <a href="#" class="option-edit" title="Edit">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" /></svg>
                        </a>
                        <a href="#" class="option-delete" title="Delete">
                            <svg width="22" height="22" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                        </a>
                        <a href="#" class="move-up" title="Move Up">
                            <svg width="26" height="26" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7.41,15.41L12,10.83L16.59,15.41L18,14L12,8L6,14L7.41,15.41Z" /></svg>
                        </a>
                        <a href="#" class="move-down" title="Move Down">
                            <svg width="26" height="26" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" /></svg>
                        </a>
                    </div>
                    <?php foreach ($product['options_full'] as $option_full): ?>
                    <?php if ($option['option_name'] != $option_full['option_name']) continue; ?>
                    <div class="input-option">
                        <input type="hidden" class="input-option-name" name="option_name[]" value="<?=$option_full['option_name']?>">
                        <input type="hidden" class="input-option-value" name="option_value[]" value="<?=$option_full['option_value']?>">
                        <input type="hidden" class="input-option-quantity" name="option_quantity[]" value="<?=$option_full['quantity']?>">
                        <input type="hidden" class="input-option-price" name="option_price[]" value="<?=$option_full['price']?>">
                        <input type="hidden" class="input-option-price-modifier" name="option_price_modifier[]" value="<?=$option_full['price_modifier']?>">
                        <input type="hidden" class="input-option-weight" name="option_weight[]" value="<?=$option_full['weight']?>">
                        <input type="hidden" class="input-option-weight-modifier" name="option_weight_modifier[]" value="<?=$option_full['weight_modifier']?>">
                        <input type="hidden" class="input-option-type" name="option_type[]" value="<?=$option_full['option_type']?>">
                        <input type="hidden" class="input-option-required" name="option_required[]" value="<?=$option_full['required']?>">
                        <input type="hidden" class="input-option-position" name="option_position[]" value="<?=$option_full['position']?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($product['options'])): ?>
                <p class="no-options-msg">There are no options.</p>
                <?php endif; ?>
            </div>

            <a href="#" class="btn open-options-modal mar-bot-2 mar-top-4">
                <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
                Add Option
            </a>

        </div>

    </div>

    <!-- digital downloads tab -->
    <div class="content-block tab-content">

        <div class="pad-3 product-options-tab responsive-width-100">

            <h3 class="title1 mar-bot-5">Digital Downloads</h3>

            <div class="product-downloads-container">
                <?php if (isset($product['downloads'])): ?>
                <?php foreach ($product['downloads'] as $i => $download): ?>
                <?php if (!file_exists('../' . $download['file_path'])) continue; ?>
                <div class="product-download">
                    <span class="download-index responsive-hidden"><?=$i+1?></span>
                    <div class="download-text">
                        <h3><?=$download['file_path']?></h3>
                        <p><?=mime_content_type('../' . $download['file_path'])?>, <?=format_bytes(filesize('../' . $download['file_path']))?></p>
                    </div>
                    <div class="download-position">
                        <a href="#" class="download-delete" title="Delete">
                            <svg width="22" height="22" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                        </a>
                        <a href="#" class="move-up" title="Move Up">
                            <svg width="26" height="26" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7.41,15.41L12,10.83L16.59,15.41L18,14L12,8L6,14L7.41,15.41Z" /></svg>
                        </a>
                        <a href="#" class="move-down" title="Move Down">
                            <svg width="26" height="26" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" /></svg>
                        </a>
                    </div>
                    <div class="input-option">
                        <input type="hidden" class="input-download-file-path" name="download_file_path[]" value="<?=$download['file_path']?>">
                        <input type="hidden" class="input-download-position" name="download_position[]" value="<?=$download['position']?>">
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($product['downloads'])): ?>
                <p class="no-downloads-msg">There are no digital downloads.</p>
                <?php endif; ?>
            </div>

            <a href="#" class="btn open-downloads-modal mar-bot-2 mar-top-4">
                <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
                Add Digital Download
            </a>

        </div>

    </div>

    <!-- subscription tab -->
    <div class="content-block tab-content">

        <div class="form responsive-width-100">

            <label for="subscription">Subscription</label>
            <select id="subscription" name="subscription">
                <option value="0"<?=$product['subscription']==0?' selected':''?>>No</option>
                <option value="1"<?=$product['subscription']==1?' selected':''?>>Yes</option>
            </select>

            <label for="subscription_period">Subscription Period</label>
            <input id="subscription_period" type="number" name="subscription_period" placeholder="Subscription Period" min="0" value="<?=$product['subscription_period']?>">

            <label for="subscription_period_type">Subscription Period Type</label>
            <select id="subscription_period_type" name="subscription_period_type">
                <option value="day"<?=$product['subscription_period_type']=='day'?' selected':''?>>Day</option>
                <option value="week"<?=$product['subscription_period_type']=='week'?' selected':''?>>Week</option>
                <option value="month"<?=$product['subscription_period_type']=='month'?' selected':''?>>Month</option>
                <option value="year"<?=$product['subscription_period_type']=='year'?' selected':''?>>Year</option>
            </select>

            <div class="subscription-info">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160 352 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l111.5 0c0 0 0 0 0 0l.4 0c17.7 0 32-14.3 32-32l0-112c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1L16 432c0 17.7 14.3 32 32 32s32-14.3 32-32l0-35.1 17.6 17.5c0 0 0 0 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.8c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352l34.4 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L48.4 288c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
                <p>...</p>
            </div>

        </div>

    </div>

</form>

<?php if (template_editor == 'tinymce'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.3.0/tinymce.min.js" integrity="sha512-RUZ2d69UiTI+LdjfDCxqJh5HfjmOcouct56utQNVRjr90Ea8uHQa+gCxvxDTC9fFvIGP+t4TDDJWNTRV48tBpQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
tinymce.init({
    selector: '#description',
    plugins: 'image table lists media link code',
    toolbar: 'undo redo | blocks bold italic forecolor | align outdent indent numlist bullist | image link | fontfamily fontsize backcolor underline strikethrough lineheight table code removeformat',
    menubar: false,
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    content_css: false,
    height: 400,
    branding: false,
    promotion: false,
    automatic_uploads: false,
    image_title: true,
    image_description: true,
    license_key: 'gpl'
});
</script>
<?php endif; ?>

<?=template_admin_footer('<script>initProduct()</script>')?>