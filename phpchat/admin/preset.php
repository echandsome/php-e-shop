<?php
include 'main.php';
// Default preset product values
$preset = [
    'msg' => '',
    'acc_id' => NULL,
];
// Retrieve all accounts from the "accounts" table
$stmt = $pdo->prepare('SELECT * FROM accounts');
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Check if the ID param in the URL exists
if (isset($_GET['id'])) {
    // Retrieve the preset from the database
    if ($_SESSION['chat_account_role'] == 'Admin') {
        $stmt = $pdo->prepare('SELECT * FROM presets WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM presets WHERE id = ? AND acc_id = ?');
        $stmt->execute([ $_GET['id'], $_SESSION['chat_account_id'] ]);
    }
    $preset = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if the preset exists
    if (!$preset) {
        header('Location: presets.php');
        exit;
    }
    // ID param exists, edit an existing preset
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the preset
        if ($_SESSION['chat_account_role'] == 'Admin') {
            $acc_id = $_POST['acc_id'] == 0 ? NULL : $_POST['acc_id'];
            $stmt = $pdo->prepare('UPDATE presets SET msg = ?, acc_id = ? WHERE id = ?');
            $stmt->execute([ $_POST['msg'], $acc_id, $_GET['id'] ]);
        } else {
            $stmt = $pdo->prepare('UPDATE presets SET msg = ? WHERE id = ? AND acc_id = ?');
            $stmt->execute([ $_POST['msg'], $_GET['id'], $_SESSION['chat_account_id'] ]);
        }
        header('Location: presets.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the preset
        if ($_SESSION['chat_account_role'] == 'Admin') {
            $stmt = $pdo->prepare('DELETE FROM presets WHERE id = ?');
            $stmt->execute([ $_GET['id'] ]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM presets WHERE id = ? AND acc_id = ?');
            $stmt->execute([ $_GET['id'], $_SESSION['chat_account_id'] ]);
        }
        header('Location: presets.php?success_msg=3');
        exit;
    }
} else {
    // Create a new preset
    $page = 'Create';
    if (isset($_POST['submit'])) {
        if ($_SESSION['chat_account_role'] == 'Admin') {
            $acc_id = $_POST['acc_id'] == 0 ? NULL : $_POST['acc_id'];
            $stmt = $pdo->prepare('INSERT INTO presets (msg,acc_id) VALUES (?,?)');
            $stmt->execute([ $_POST['msg'], $acc_id ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO presets (msg,acc_id) VALUES (?,?)');
            $stmt->execute([ $_POST['msg'], $_SESSION['chat_account_id'] ]);
        }
        header('Location: presets.php?success_msg=1');
        exit;
    }
}
// Preset template below
?>
<?=template_admin_header($page . ' Preset', 'settings', 'presets')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Preset</h2>
        <a href="presets.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this preset?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="msg"><i class="required">*</i> Message</label>
            <textarea id="msg" type="text" name="msg" placeholder="Message..." required><?=htmlspecialchars($preset['msg'], ENT_QUOTES)?></textarea>

            <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
            <label for="acc_id"><i class="required">*</i> Account</label>
            <select id="acc_id" name="acc_id">
                <option value="0">All Accounts</option>
                <?php foreach ($accounts as $account): ?>
                <option value="<?=$account['id']?>"<?=$preset['acc_id']==$account['id']?' selected':''?>><?=$account['email']?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

        </div>

    </div>

</form>

<?=template_admin_footer()?>