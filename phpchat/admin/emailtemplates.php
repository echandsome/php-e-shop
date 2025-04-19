<?php
include 'main.php';
// Ensure account is admin
if ($_SESSION['chat_account_role'] != 'Admin') {
    exit('Invalid request!');
} 
// Capture post data
if (isset($_POST['emailtemplate'])) {
    // Save templates
    file_put_contents('../email-template.html', $_POST['emailtemplate']);
    header('Location: emailtemplates.php?success_msg=1');
    exit;
}
// Read the notification email template
$contents = file_get_contents('../email-template.html');
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Email templates updated successfully!';
    }
}
?>
<?=template_admin_header('Email Templates', 'emailtemplates')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100">Email Templates</h2>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <?php if (isset($success_msg)): ?>
    <div class="msg success">
        <i class="fas fa-check-circle"></i>
        <p><?=$success_msg?></p>
        <i class="fas fa-times"></i>
    </div>
    <?php endif; ?>

    <div class="content-block">
        <div class="form responsive-width-100 size-md">
            <label for="emailtemplate">Notification Email Template:</label>
            <textarea name="emailtemplate" id="emailtemplate" class="code-editor"><?=$contents?></textarea>
        </div>
    </div>

</form>

<?=template_admin_footer()?>