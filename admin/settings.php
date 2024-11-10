<?php
defined('admin') or exit;
// Configuration file
$file = '../config.php';
// Open the configuration file for reading
$contents = file_get_contents($file);
// Format key function
function format_key($key) {
    $key = str_replace(
        ['_', 'url', 'db ', ' pass', ' user', ' id', ' uri', 'smtp', 'paypal', 'ipn'], 
        [' ', 'URL', 'Database ', ' Password', ' Username', ' ID', ' URI', 'SMTP', 'PayPal', 'IPN'], 
        strtolower($key)
    );
    return ucwords($key);
}
// Format HTML output function
function format_var_html($key, $value, $comment, $list = []) {
    // Add keys to exclude from the form
    $exclude = ['db_user', 'db_pass', 'db_name', 'db_host'];
    if (in_array($key, $exclude)) {
        return '';
    }
    $html = '';
    $type = 'text';
    $type = strpos($value, '\n') !== false ? 'textarea' : $type;
    $value = $type != 'textarea' ? htmlspecialchars(trim($value, '\''), ENT_QUOTES) : trim($value, '\'');
    $type = strpos($key, 'pass') !== false ? 'password' : $type;
    $type = in_array(strtolower($value), ['true', 'false']) ? 'checkbox' : $type;
    $checked = strtolower($value) == 'true' ? ' checked' : '';
    $html .= '<label for="' . $key . '">' . format_key($key) . '</label>';
    if (substr($comment, 0, 2) === '//') {
        $html .= '<p class="comment">' . ltrim($comment, '//') . '</p>';
    }
    if ($type == 'checkbox') {
        $html .= '<input type="hidden" name="' . $key . '" value="false">';
    }
    if ($list) {
        $html .= '<select name="' . $key . '" id="' . $key . '">';
        foreach ($list as $item) {
            $item = explode('=', trim($item));
            $selected = strtolower($item[0]) == strtolower($value) ? ' selected' : '';
            $html .= '<option value="' . $item[0] . '"' . $selected . '>' . $item[1] . '</option>';
        }
        $html .= '</select>';
    } else if ($type == 'textarea') {
        $html .= '<textarea name="' . $key . '" id="' . $key . '" placeholder="' . format_key($key) . '">' . str_replace('\n', PHP_EOL, $value) . '</textarea>';
    } else {
        $html .= '<input type="' . $type . '" name="' . $key . '" id="' . $key . '" value="' . $value . '" placeholder="' . format_key($key) . '"' . $checked . '>';
    }
    return $html;
}
// Format tabs
function format_tabs($contents) {
    $rows = explode("\n", $contents);
    echo '<div class="tabs">';
    echo '<a href="#" class="active">General</a>';
    for ($i = 0; $i < count($rows); $i++) {
        preg_match('/\/\*(.*?)\*\//', $rows[$i], $match);
        if ($match) {
            echo '<a href="#">' . $match[1] . '</a>';
        }
    }
    echo '</div>';
}
// Format form
function format_form($contents) {
    $rows = explode("\n", $contents);
    echo '<div class="tab-content active">';
    for ($i = 0; $i < count($rows); $i++) {
        preg_match('/\/\*(.*?)\*\//', $rows[$i], $match);
        if ($match) {
            echo '</div><div class="tab-content">';
        }
        preg_match('/define\(\'(.*?)\', ?(.*?)\)/', $rows[$i], $match);
        if ($match) {
            $list = substr($rows[$i-1], 0, 8) === '// List:' ? explode(',', ltrim($rows[$i-1], '// List:')) : [];
            echo format_var_html($match[1], $match[2], $list ? $rows[$i-2] : $rows[$i-1], $list);
        }
    }  
    echo '</div>';
}
if (!empty($_POST)) {
    // Update the configuration file with the new keys and values
    foreach ($_POST as $k => $v) {
        $val = in_array(strtolower($v), ['true', 'false']) ? strtolower($v) : '\'' . $v . '\'';
        $val = is_numeric($v) ? $v : $val;
        $val = str_replace(PHP_EOL, '\n', $val);
        $contents = preg_replace('/define\(\'' . $k . '\'\, ?(.*?)\)/s', 'define(\'' . $k . '\',' . $val . ')', $contents);
    }
    // Save the updated configuration file
    if (file_put_contents($file, $contents) === false) {
        header('Location: index.php?page=settings&error_msg=1');
        exit;
    } else {
        header('Location: index.php?page=settings&success_msg=1');
        exit;
    }
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Settings updated successfully!';
    }
}
// Handle error messages
if (isset($_GET['error_msg'])) {
    if ($_GET['error_msg'] == 1) {
        $error_msg = 'There was an error updating the settings! Please make sure the config.php file is writable!';
    }
}
?>
<?=template_admin_header('Settings', 'settings')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100">Settings</h2>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <?php if (isset($success_msg)): ?>
    <div class="mar-top-4">
        <div class="msg success">
            <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
            <p><?=$success_msg?></p>
            <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
    <div class="mar-top-4">
        <div class="msg error">
            <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
            <p><?=$error_msg?></p>
            <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
        </div>
    </div>
    <?php endif; ?>

    <?=format_tabs($contents)?>
    <div class="content-block">
        <div class="form responsive-width-100">
            <?=format_form($contents)?>
        </div>
    </div>

</form>

<script>
document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    checkbox.onclick = () => checkbox.value = checkbox.checked ? 'true' : 'false';
});
</script>

<?=template_admin_footer()?>