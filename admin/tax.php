<?php
defined('shoppingcart_admin') or exit;
// Default input tax values
$tax = [
    'country' => '',
    'rate' => '',
    'rules' => [],
    'rate_type' => 'percentage',
];
// function generate rules function
function generate_rules() {
    $rules = [];
    if (isset($_POST['rule_field'])) {
        foreach ($_POST['rule_field'] as $key => $field) {
            $rules[] = [
                'field' => $field,
                'operator' => $_POST['rule_operator'][$key],
                'value' => $_POST['rule_value'][$key]
            ];
        }
    }
    // convert rules to json
    return $rules ? json_encode($rules) : '';
}
if (isset($_GET['id'])) {
    // ID param exists, edit an existing tax
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the tax
        $categories_list = isset($_POST['categories']) ? implode(',', $_POST['categories']) : '';
        $products_list = isset($_POST['products']) ? implode(',', $_POST['products']) : '';
        $rules = generate_rules();
        $stmt = $pdo->prepare('UPDATE taxes SET country = ?, rate = ?, rate_type = ?, rules = ? WHERE id = ?');
        $stmt->execute([ $_POST['country'], $_POST['rate'], $_POST['rate_type'], $rules, $_GET['id'] ]);
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
    // Get the rules from the database
    $tax['rules'] = $tax['rules'] ? json_decode($tax['rules'], true) : [];
} else {
    // Create a new tax
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $rules = generate_rules();
        $stmt = $pdo->prepare('INSERT INTO taxes (country,rate,rate_type,rules) VALUES (?,?,?,?)');
        $stmt->execute([ $_POST['country'], $_POST['rate'], $_POST['rate_type'], $rules ]);
        header('Location: index.php?page=taxes&success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Tax', 'taxes', 'manage')?>

<form method="post">

    <div class="content-title">
        <h2><?=$page?> Tax</h2>
        <div class="btns">
            <a href="index.php?page=taxes" class="btn alt mar-right-1">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn red mar-right-1" onclick="return confirm('Are you sure you want to delete this tax?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn">
        </div>
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
            <input id="rate" type="number" name="rate" step=".01" placeholder="Rate" value="<?=$tax['rate']?>" required>

            <label for="rate_type"><span class="required">*</span> Rate Type</label>
            <select name="rate_type">
                <option value="percentage"<?=$tax['rate_type']=='percentage'?' selected':''?>>Percentage</option>
                <option value="fixed"<?=$tax['rate_type']=='fixed'?' selected':''?>>Fixed</option>
            </select>

            <label for="rules">Rules</label>
            <div class="rules">
                <?php foreach ($tax['rules'] as $rule): ?>
                <div class="rule">
                    <select name="rule_field[]">
                        <option value="address_city"<?=$rule['field']=='address_city'?' selected':''?>>City</option>
                        <option value="address_state"<?=$rule['field']=='address_state'?' selected':''?>>State</option>
                        <option value="address_zip"<?=$rule['field']=='address_zip'?' selected':''?>>ZIP</option>
                    </select>
                    <select name="rule_operator[]">
                        <option value="equals"<?=$rule['operator']=='equals'?' selected':''?>>equals to</option>
                        <option value="not_equals"<?=$rule['operator']=='not_equals'?' selected':''?>>not equals to</option>
                        <option value="includes"<?=$rule['operator']=='includes'?' selected':''?>>includes</option>
                        <option value="excludes"<?=$rule['operator']=='excludes'?' selected':''?>>excludes</option>
                        <option value="starts_with"<?=$rule['operator']=='starts_with'?' selected':''?>>starts with</option>
                        <option value="ends_with"<?=$rule['operator']=='ends_with'?' selected':''?>>ends with</option>
                    </select>
                    <input type="text" name="rule_value[]" placeholder="Value" value="<?=$rule['value']?>">
                    <a href="#" class="delete-rule">
                        <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>Delete</title><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="#" class="add-link add-rule">+ Add Rule</a>

        </div>

    </div>

</form>

<script>
const deleteRulesEventHandler = () => {
    document.querySelectorAll('.delete-rule').forEach(deleteRule => {
        deleteRule.onclick = event => {
            event.preventDefault();
            event.target.closest('.rule').remove();
        };
    });
};
document.querySelector('.add-rule').onclick = event => {
    event.preventDefault();
    document.querySelector('.rules').insertAdjacentHTML('beforeend', `
        <div class="rule">
            <select name="rule_field[]">
                <option value="address_city">City</option>
                <option value="address_state">State</option>
                <option value="address_zip">ZIP</option>
            </select>
            <select name="rule_operator[]">
                <option value="equals">equals to</option>
                <option value="not_equals">not equals to</option>
                <option value="includes">includes</option>
                <option value="excludes">excludes</option>
            </select>
            <input type="text" name="rule_value[]" placeholder="Value">
            <a href="#" class="delete-rule">
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>Delete</title><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
            </a>
        </div>
    `);
    deleteRulesEventHandler();
};
deleteRulesEventHandler();
</script>

<?=template_admin_footer()?>