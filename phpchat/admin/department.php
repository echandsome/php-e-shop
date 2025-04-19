<?php
include 'main.php';
// Prevent non-admins accessing the page
if ($_SESSION['chat_account_role'] != 'Admin') {
    exit('Invalid request!');
}
// Default department values
$dep = [
    'title' => '',
    'created' => date('Y-m-d\TH:i')
];
if (isset($_GET['id'])) {
    // Retrieve the department from the database
    $stmt = $pdo->prepare('SELECT * FROM departments WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $dep = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing department
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the department
        $stmt = $pdo->prepare('UPDATE departments SET title = ?, created = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['created'], $_GET['id'] ]);
        header('Location: departments.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the department
        $stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        header('Location: departments.php?success_msg=3');
        exit;
    }
} else {
    // Create a new department
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO departments (title,created) VALUES (?,?)');
        $stmt->execute([ $_POST['title'], $_POST['created'] ]);
        header('Location: departments.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Department', 'departments', 'manage')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Department</h2>
        <a href="departments.php" class="btn alt mar-right-2">Cancel</a>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this department?')">
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="title"><i class="required">*</i> Title</label>
            <input id="title" type="text" name="title" placeholder="Title" value="<?=htmlspecialchars($dep['title'], ENT_QUOTES)?>" required>

            <label for="created"><i class="required">*</i> Created Date</label>
            <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($dep['created']))?>" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>