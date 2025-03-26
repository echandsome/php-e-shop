<?php
// Prevent direct access to file
defined('shoppingcart') or exit;
// Validation error variable
$validation_error = '';
// Check to make sure the id parameter is specified in the URL
if (isset($_GET['id'])) {
    // Prepare statement and execute, prevents SQL injection
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_status = 1 AND (BINARY id = ? OR url_slug = ?)');
    $stmt->execute([ $_GET['id'], $_GET['id'] ]);
    // Fetch the product from the database and return the result as an Array
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if the product exists (array is not empty)
    if (!$product) {
        // Output simple error if the id for the product doesn't exists (array is empty)
        http_response_code(404);
        exit('Product does not exist!');
    }
    // Select the product images (if any) from the products_images table
    $stmt = $pdo->prepare('SELECT m.*, pm.position FROM product_media_map pm JOIN product_media m ON m.id = pm.media_id WHERE pm.product_id = ? ORDER BY pm.position ASC');
    $stmt->execute([ $product['id'] ]);
    // Fetch the product images from the database and return the result as an Array
    $product_media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Select the product options (if any) from the product_options table
    $stmt = $pdo->prepare('SELECT CONCAT(option_name, "::", option_type, "::", required) AS k, option_value, quantity, price, price_modifier, weight, weight_modifier, option_type, required FROM product_options WHERE product_id = ? ORDER BY position ASC, id');
    $stmt->execute([ $product['id'] ]);
    // Fetch the product options from the database and return the result as an Array
    $product_options = $stmt->fetchAll(PDO::FETCH_GROUP);
    // Check if product is on wishlist
    $on_wishlist = false;
    // Check if the user is logged in
    if (isset($_SESSION['account_loggedin'])) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE product_id = ? AND account_id = ?');
        $stmt->execute([ $product['id'], $_SESSION['account_id'] ]);
        $on_wishlist = $stmt->fetchColumn() > 0 ? true : false;
    }
    // Add the HTML meta data (for SEO purposes)
    $meta = '
        <meta property="og:url" content="' . url('index.php?page=product&id=' . ($product['url_slug'] ? $product['url_slug']  : $product['id'])) . '">
        <meta property="og:title" content="' . $product['title'] . '">
    ';
    if (isset($product_media[0]) && file_exists($product_media[0]['full_path'])) {
        $meta .= '<meta property="og:image" content="' . base_url . $product_media[0]['full_path'] . '">';
    }
    // Check if the user clicked the add to wishlist button
    if (isset($_POST['add_to_wishlist'])) {
        // Check if the user is logged in
        if (isset($_SESSION['account_loggedin'])) {
            // Prepare statement and execute, prevents SQL injection
            $stmt = $pdo->prepare('SELECT * FROM wishlist WHERE product_id = ? AND account_id = ?');
            $stmt->execute([ $product['id'], $_SESSION['account_id'] ]);
            // Fetch the product from the database and return the result as an Array
            $wishlist_item = $stmt->fetch(PDO::FETCH_ASSOC);
            // Check if the product is already in the wishlist
            if ($wishlist_item) {
                // Product is already in the wishlist
                $validation_error = 'Product is already in your wishlist!';
            } else {
                // Product is not in the wishlist, add it
                $stmt = $pdo->prepare('INSERT INTO wishlist (product_id, account_id, created) VALUES (?, ?, ?)');
                $stmt->execute([ $product['id'], $_SESSION['account_id'], date('Y-m-d H:i:s') ]);
                // Redirect to the same page to prevent form resubmission issues
                header('Location: ' . url('index.php?page=product&id=' . ($product['url_slug'] ? $product['url_slug']  : $product['id'])));
                exit;
            }
        } else {
            header('Location: ' . url('index.php?page=myaccount'));
            exit;
        }
    // Check if the user clicked the remove from wishlist button
    } else if (isset($_POST['remove_from_wishlist'])) {
        // Check if the user is logged in
        if (isset($_SESSION['account_loggedin'])) {
            // Prepare statement and execute, prevents SQL injection
            $stmt = $pdo->prepare('DELETE FROM wishlist WHERE product_id = ? AND account_id = ?');
            $stmt->execute([ $product['id'], $_SESSION['account_id'] ]);
            // Redirect to the same page to prevent form resubmission issues
            header('Location: ' . url('index.php?page=product&id=' . ($product['url_slug'] ? $product['url_slug']  : $product['id'])));
            exit;
        } else {
            header('Location: ' . url('index.php?page=myaccount'));
            exit;
        }
    // If the user clicked the add to cart button
    } else if ($_POST) {
        // abs() function will prevent minus quantity and (int) will ensure the value is an integer (number)
        $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? abs((int)$_POST['quantity']) : 1;
        // Get product options
        $options = '';
        $options_price = (float)$product['price'];
        $options_weight = (float)$product['weight'];
        // Iterate post data
        foreach ($_POST as $k => $v) {
            // Validate options
            if (strpos($k, 'option-') !== false) {
                if (is_array($v)) {
                    // Option is checkbox or radio element
                    foreach ($v as $vv) {
                        if (empty($vv)) continue;
                        // Replace underscores with spaces and remove option- prefix
                        $options .= str_replace(['_', 'option-'], [' ', ''], $k) . '-' . $vv . ',';
                        // Get the option from the database
                        $stmt = $pdo->prepare('SELECT * FROM product_options WHERE option_name = ? AND option_value = ? AND product_id = ?');
                        $stmt->execute([ str_replace(['_', 'option-'], [' ', ''], $k), $vv, $product['id'] ]);
                        $option = $stmt->fetch(PDO::FETCH_ASSOC);
                        // Get cart option quantity
                        $cart_option_quantity = get_cart_option_quantity($product['id'], str_replace(['_', 'option-'], [' ', ''], $k) . '-' . $vv);
                        // Check if the option exists and the quantity is available
                        if ($option && ($option['quantity'] == -1 || $option['quantity']-$quantity-$cart_option_quantity >= 0)) {
                            $options_price = $option['price_modifier'] == 'add' ? $options_price + $option['price'] : $options_price - $option['price'];
                            $options_weight = $option['weight_modifier'] == 'add' ? $options_weight + $option['weight'] : $options_weight - $option['weight'];
                        } else {
                            $validation_error = 'The ' . htmlspecialchars(str_replace(['_', 'option-'], [' ', ''], $k) . '-' . $vv, ENT_QUOTES) . ' option is no longer available!';
                        }
                    }
                } else {
                    if (empty($v)) continue;
                    // Replace underscores with spaces and remove option- prefix
                    $options .= str_replace(['_', 'option-'], [' ', ''], $k) . '-' . $v . ',';
                    // Get the option from the database
                    $stmt = $pdo->prepare('SELECT * FROM product_options WHERE option_name = ? AND option_value = ? AND product_id = ?');
                    $stmt->execute([ str_replace(['_', 'option-'], [' ', ''], $k), $v, $product['id'] ]);
                    $option = $stmt->fetch(PDO::FETCH_ASSOC);
                    // Get cart option quantity
                    $cart_option_quantity = get_cart_option_quantity($product['id'], str_replace(['_', 'option-'], [' ', ''], $k) . '-' . $v);
                    // Check if the option exists and the quantity is available
                    if (!$option) {
                        // Option is text or datetime element
                        $stmt = $pdo->prepare('SELECT * FROM product_options WHERE option_name = ? AND product_id = ?');
                        $stmt->execute([ str_replace(['_', 'option-'], [' ', ''], $k), $product['id'] ]);
                        $option = $stmt->fetch(PDO::FETCH_ASSOC);                              
                    }
                    if ($option && ($option['quantity'] == -1 || $option['quantity']-$quantity-$cart_option_quantity >= 0)) {
                        $options_price = $option['price_modifier'] == 'add' ? $options_price + $option['price'] : $options_price - $option['price'];
                        $options_weight = $option['weight_modifier'] == 'add' ? $options_weight + $option['weight'] : $options_weight - $option['weight'];
                    } else {
                        $validation_error = 'The ' . htmlspecialchars(str_replace(['_', 'option-'], [' ', ''], $k) . '-' . $v, ENT_QUOTES) . ' option is no longer available!';
                    }
                }
            }
        }
        // Check product quantity
        $cart_product_quantity = get_cart_product_quantity($product['id']);
        if ($product['quantity'] != -1 && $product['quantity']-$quantity-$cart_product_quantity < 0) {
            $validation_error = 'The product is out of stock or you have reached the maximum quantity!';
        }
        // If there are no errors
        if (!$validation_error) {
            // Set the options price to 0 if it's less than 0
            $options_price = $options_price < 0 ? 0 : $options_price;
            $options = rtrim($options, ',');
            // Check if the product exists (array is not empty)
            if ($quantity > 0) {
                // Check if the product is a subscription
                if (isset($_POST['paypal_subscribe']) || isset($_POST['stripe_subscribe'])) {
                    $_SESSION['sub'] = [
                        'id' => $product['id'],
                        'quantity' => $quantity,
                        'options' => $options,
                        'options_price' => $options_price,
                        'options_weight' => $options_weight
                    ];
                }
                // If the user clicked the paypal subscribe button
                if (isset($_POST['paypal_subscribe'])) {
                    header('Location: ' . url('index.php?page=subscribe&method=paypal'));
                    exit;
                }
                // If the user clicked the stripe subscribe button
                if (isset($_POST['stripe_subscribe'])) {
                    header('Location: ' . url('index.php?page=subscribe&method=stripe'));
                    exit;
                }
                // Product exists in database, now we can create/update the session variable for the cart
                if (!isset($_SESSION['cart'])) {
                    // Shopping cart session variable doesnt exist, create it
                    $_SESSION['cart'] = [];
                }
                $cart_product = &get_cart_product($product['id'], $options);
                if ($cart_product) {
                    // Product exists in cart, update the quanity
                    $cart_product['quantity'] += $quantity;
                } else {
                    // Product is not in cart, add it
                    $_SESSION['cart'][] = [
                        'id' => $product['id'],
                        'quantity' => $quantity,
                        'options' => $options,
                        'options_price' => $options_price,
                        'options_weight' => $options_weight,
                        'shipping_price' => 0.00
                    ];
                }
            }
            // Prevent form resubmission...
            header('Location: ' . url('index.php?page=cart'));
            exit;
        }
    }
} else {
    // Output simple error if the id wasn't specified
    http_response_code(404);
    exit('Product does not exist!');
}
?>
<?=template_header($product['title'], $meta)?>

<?php if ($error): ?>

<p class="content-wrapper error"><?=$error?></p>

<?php else: ?>

<div class="product content-wrapper">

    <?php if ($product_media): ?>
    <div class="product-imgs">

        <?php if (file_exists($product_media[0]['full_path'])): ?>
        <div class="product-img-large">
            <img src="<?=base_url . $product_media[0]['full_path']?>" alt="<?=$product_media[0]['caption']?>">
        </div>
        <?php endif; ?>

        <div class="product-small-imgs">
            <?php foreach ($product_media as $media): ?>
            <div class="product-img-small<?=$media['position']==1?' selected':''?>">
                <img src="<?=base_url . $media['full_path'] ?>" width="150" height="150" alt="<?=$media['caption']?>">
            </div>
            <?php endforeach; ?>
        </div>

    </div>
    <?php endif; ?>

    <div class="product-wrapper">

        <h1 class="name"><?=$product['title']?></h1>

        <div class="prices">
            <span class="price" data-price="<?=$product['price']?>"><?=currency_code?><?=num_format($product['price'],2)?></span>
            <?php if ($product['rrp'] > 0): ?>
            <span class="rrp"><?=currency_code?><?=num_format($product['rrp'],2)?></span>
            <?php endif; ?>
            <?php if ($product['subscription']): ?>
            <span class="sub-period-type mar-left-2 mar-top-1">/ <?=ucwords($product['subscription_period_type'])?></span>
            <?php endif; ?>
        </div>

        <form class="product-form form" action="<?=url('index.php?page=product&id=' . ($product['url_slug'] ? $product['url_slug']  : $product['id']))?>" method="post">
            <?php foreach ($product_options as $id => $option): ?>
            <?php $id = explode('::', $id); ?>
            <?php if ($id[1] == 'select'): ?>
            <label for="<?=$id[0]?>" class="form-label"><?=$id[0]?></label>
            <select id="<?=$id[0]?>" class="form-input option select" name="option-<?=$id[0]?>"<?=$id[2] ? ' required' : ''?>>
                <option value="" selected disabled style="display:none"><?=$id[0]?></option>
                <?php foreach ($option as $option_value): ?>
                <option value="<?=$option_value['option_value']?>" data-price="<?=$option_value['price']?>" data-modifier="<?=$option_value['price_modifier']?>" data-quantity="<?=$option_value['quantity']?>"<?=$option_value['quantity']==0?' disabled':''?>><?=$option_value['option_value']?></option>
                <?php endforeach; ?>
            </select>
            <?php elseif ($id[1] == 'radio'): ?>
            <label for="<?=$id[0]?>" class="form-label-2"><?=$id[0]?></label>
            <div class="form-radio-checkbox">
                <?php foreach ($option as $n => $option_value): ?>
                <label>
                    <input <?=$n == 0 ? 'id="' . $id[0] . '" ' : ''?>class="option radio" value="<?=$option_value['option_value']?>" name="option-<?=$id[0]?>" type="radio" data-price="<?=$option_value['price']?>" data-modifier="<?=$option_value['price_modifier']?>" data-quantity="<?=$option_value['quantity']?>"<?=$id[2] && $n == 0 ? ' required' : ''?><?=$option_value['quantity']==0?' disabled':''?>><?=$option_value['option_value']?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php elseif ($id[1] == 'checkbox'): ?>
            <label for="<?=$id[0]?>" class="form-label-2"><?=$id[0]?></label>
            <div class="form-radio-checkbox">
                <?php foreach ($option as $n => $option_value): ?>
                <label>
                    <input <?=$n == 0 ? 'id="' . $id[0] . '" ' : ''?>class="option checkbox" value="<?=$option_value['option_value']?>" name="option-<?=$id[0]?>[]" type="checkbox" data-price="<?=$option_value['price']?>" data-modifier="<?=$option_value['price_modifier']?>" data-quantity="<?=$option_value['quantity']?>"<?=$id[2] && $n == 0 ? ' required' : ''?><?=$option_value['quantity']==0?' disabled':''?>><?=$option_value['option_value']?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php elseif ($id[1] == 'text'): ?>
            <?php foreach ($option as $option_value): ?>
            <label for="<?=$id[0]?>" class="form-label"><?=$id[0]?></label>
            <input id="<?=$id[0]?>" class="form-input option text" name="option-<?=$id[0]?>" type="text" placeholder="<?=$id[0]?>"<?=!empty($option_value['option_value'])?' value="' . $option_value['option_value'] . '"':''?> data-price="<?=$option_value['price']?>" data-modifier="<?=$option_value['price_modifier']?>" data-quantity="<?=$option_value['quantity']?>"<?=$id[2] ? ' required' : ''?><?=$option_value['quantity']==0?' disabled':''?>>
            <?php endforeach; ?>
            <?php elseif ($id[1] == 'datetime'): ?>
            <?php foreach ($option as $option_value): ?>
            <label for="<?=$id[0]?>" class="form-label"><?=$id[0]?></label>
            <input id="<?=$id[0]?>" class="form-input option datetime" name="option-<?=$id[0]?>" type="datetime-local"<?=$option_value['option_value'] ? 'value="' . date('Y-m-d\TH:i', strtotime($option_value['option_value'])) . '" ' : ''?> data-price="<?=$option_value['price']?>" data-modifier="<?=$option_value['price_modifier']?>" data-quantity="<?=$option_value['quantity']?>"<?=$id[2] ? ' required' : ''?><?=$option_value['quantity']==0?' disabled':''?>>
            <?php endforeach; ?>          
            <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!$product['subscription']): ?>
            <label for="quantity" class="form-label">Quantity</label>
            <input id="quantity" class="form-input" type="number" name="quantity" value="1" min="1" data-quantity="<?=$product['quantity']?>"<?php if ($product['quantity'] != -1): ?> max="<?=$product['quantity']?>"<?php endif; ?> placeholder="Quantity" required>
            <?php endif; ?>

            <?php if (!$on_wishlist): ?>
            <button type="submit" name="add_to_wishlist" class="add-to-wishlist" formnovalidate>
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12.1,18.55L12,18.65L11.89,18.55C7.14,14.24 4,11.39 4,8.5C4,6.5 5.5,5 7.5,5C9.04,5 10.54,6 11.07,7.36H12.93C13.46,6 14.96,5 16.5,5C18.5,5 20,6.5 20,8.5C20,11.39 16.86,14.24 12.1,18.55M16.5,3C14.76,3 13.09,3.81 12,5.08C10.91,3.81 9.24,3 7.5,3C4.42,3 2,5.41 2,8.5C2,12.27 5.4,15.36 10.55,20.03L12,21.35L13.45,20.03C18.6,15.36 22,12.27 22,8.5C22,5.41 19.58,3 16.5,3Z" /></svg>
                Add to Wishlist
            </button>
            <?php else: ?>
            <button type="submit" name="remove_from_wishlist" class="added-to-wishlist" formnovalidate>
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z" /></svg>
                Added to Wishlist
            </button>
            <?php endif; ?>

            <?php if ($product['quantity'] == 0): ?>
            <button type="submit" class="btn" disabled>Out of Stock</button>
            <?php else: ?>
            <?php if ($product['subscription']): ?>
            <?php if (paypal_enabled): ?>
            <button type="submit" class="btn paypal mar-bot-1" name="paypal_subscribe">Subscribe with PayPal</button>
            <?php endif; ?>
            <?php if (stripe_enabled): ?>
            <button type="submit" class="btn stripe" name="stripe_subscribe">Subscribe with Stripe</button>
            <?php endif; ?>
            <?php else: ?>
            <button type="submit" class="btn" name="add_to_cart">Add to Cart</button>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($validation_error): ?>
            <p class="error"><?=$validation_error?></p>
            <?php endif; ?>

        </form>

    </div>

</div>

<?php if (!empty($product['description'])): ?>
<div class="product-details content-wrapper">

    <div class="description-title">
        <h2>Product Description</h2>
    </div>

    <div class="description-content">
        <?=$product['description']?>
    </div>

</div>
<?php endif; ?>

<?php endif; ?>

<?=template_footer()?>