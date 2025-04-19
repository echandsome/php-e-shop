<?php
include 'main.php';
// Ensure account is admin
if ($_SESSION['chat_account_role'] != 'Admin') {
    exit('Invalid request!');
} 
// Default filter product values
$filter = [
    'word' => '',
    'replacement' => ''
];
if (isset($_GET['id'])) {
    // Retrieve the filter from the database
    $stmt = $pdo->prepare('SELECT * FROM word_filters WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $filter = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing filter
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the filter
        $stmt = $pdo->prepare('UPDATE word_filters SET word = ?, replacement = ? WHERE id = ?');
        $stmt->execute([ $_POST['word'], $_POST['replacement'], $_GET['id'] ]);
        header('Location: word_filters.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the filter
        $stmt = $pdo->prepare('DELETE FROM word_filters WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        header('Location: word_filters.php?success_msg=3');
        exit;
    }
} else {
    // Create a new filter
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO word_filters (word,replacement) VALUES (?,?)');
        $stmt->execute([ $_POST['word'], $_POST['replacement'] ]);
        header('Location: word_filters.php?success_msg=1');
        exit;
    }
}
// Word filter template below
?>
<?=template_admin_header($page . ' Word Filter', 'settings', 'word_filters')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Word Filter</h2>
        <a href="filters.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this word filter?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="word"><i class="required">*</i> Word</label>
            <input id="word" type="text" name="word" placeholder="Word" value="<?=htmlspecialchars($filter['word'], ENT_QUOTES)?>" required>

            <label for="replacement"><i class="required">*</i> Replacement</label>
            <input id="replacement" type="text" name="replacement" placeholder="Replacement" value="<?=htmlspecialchars($filter['replacement'], ENT_QUOTES)?>" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>