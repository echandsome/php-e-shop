<?php
include 'main.php';
// Ensure account is admin
if ($_SESSION['chat_account_role'] != 'Admin') {
    exit('Invalid request!');
} 
// Configuration file
$file = '../config.php';
// Open the configuration file for reading
$contents = file_get_contents($file);
// Format key function
function format_key($key) {
    $key = str_replace(
        ['_', 'url', 'db ', ' pass', ' user', ' id', ' uri', 'smtp'], 
        [' ', 'URL', 'Database ', ' Password', ' Username', ' ID', ' URI', 'SMTP'], 
        strtolower($key)
    );
    return ucwords($key);
}
// Format HTML output function
function format_var_html($key, $value, $comment, $list = []) {
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
    file_put_contents('../config.php', $contents);
    header('Location: settings.php?success_msg=1');
    exit;
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Settings updated successfully!';
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
    <div class="msg success">
        <i class="fas fa-check-circle"></i>
        <p><?=$success_msg?></p>
        <i class="fas fa-times"></i>
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