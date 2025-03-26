<?php
defined('shoppingcart_admin') or exit;
// Save the email templates
if (isset($_POST['order_details_email_template'])) {
    if (file_put_contents('../order-details-template.html', $_POST['order_details_email_template']) === false) {
        header('Location: index.php?page=email_templates&error_msg=1');
        exit;
    }
}
if (isset($_POST['order_notification_email_template'])) {
    if (file_put_contents('../order-notification-template.html', $_POST['order_notification_email_template']) === false) {
        header('Location: index.php?page=email_templates&error_msg=1');
        exit;
    }
}
if (isset($_POST['resetpass_email_template'])) {
    if (file_put_contents('../resetpass-email-template.html', $_POST['resetpass_email_template']) === false) {
        header('Location: index.php?page=email_templates&error_msg=1');
        exit;
    }
}
if (isset($_POST['submit'])) {
    header('Location: index.php?page=email_templates&success_msg=1');
    exit;
}
// Read the order details email template HTML file
if (file_exists('../order-details-template.html')) {
    $order_details_email_template = file_get_contents('../order-details-template.html');
}
// Read the notification email template HTML file
if (file_exists('../order-notification-template.html')) {
    $order_notification_email_template = file_get_contents('../order-notification-template.html');
}
// Read the password reset email template HTML file
if (file_exists('../resetpass-email-template.html')) {
    $resetpass_email_template = file_get_contents('../resetpass-email-template.html');
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Email template(s) updated successfully!';
    }
}
// Handle error messages
if (isset($_GET['error_msg'])) {
    if ($_GET['error_msg'] == 1) {
        $error_msg = 'There was an error updating the email template(s)! Please set the correct permissions!';
    }
}
?>
<?=template_admin_header('Email Templates', 'email_templates')?>

<form method="post" enctype="multipart/form-data">

    <div class="content-title">
        <h2>Email Templates</h2>
        <div class="btns">
            <input type="submit" name="submit" value="Save" class="btn">
        </div>
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

    <div class="tabs">
        <?php if (isset($order_details_email_template)): ?>
        <a href="#" class="active">Order Details</a>
        <?php endif; ?>
        <?php if (isset($order_notification_email_template)): ?>
        <a href="#">Order Notification</a>
        <?php endif; ?>
        <?php if (isset($resetpass_email_template)): ?>
        <a href="#">Reset Password</a>
        <?php endif; ?>
    </div>

    <div class="content-block">
        <div class="form responsive-width-100 size-full">
            <?php if (isset($order_details_email_template)): ?>
            <div class="tab-content active">
                <?php if (template_editor == 'tinymce'): ?>
                <div style="width:100%">
                    <textarea id="order_details_email_template" name="order_details_email_template" style="width:100%;height:600px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?=$order_details_email_template?></textarea>
                </div>
                <?php else: ?>
                <textarea name="order_details_email_template" id="order_details_email_template" class="code-editor"><?=$order_details_email_template?></textarea>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (isset($order_notification_email_template)): ?>
            <div class="tab-content">
                <?php if (template_editor == 'tinymce'): ?>
                <div style="width:100%">
                    <textarea id="order_notification_email_template" name="order_notification_email_template" style="width:100%;height:600px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?=$order_notification_email_template?></textarea>
                </div>
                <?php else: ?>
                <textarea name="order_notification_email_template" id="order_notification_email_template" class="code-editor"><?=$order_notification_email_template?></textarea>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (isset($resetpass_email_template)): ?>
            <div class="tab-content">
                <?php if (template_editor == 'tinymce'): ?>
                <div style="width:100%">
                    <textarea id="resetpass_email_template" name="resetpass_email_template" style="width:100%;height:600px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?=$resetpass_email_template?></textarea>
                </div>
                <?php else: ?>
                <textarea name="resetpass_email_template" id="resetpass_email_template" class="code-editor"><?=$resetpass_email_template?></textarea>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</form>

<?php if (template_editor == 'tinymce'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.3.0/tinymce.min.js" integrity="sha512-RUZ2d69UiTI+LdjfDCxqJh5HfjmOcouct56utQNVRjr90Ea8uHQa+gCxvxDTC9fFvIGP+t4TDDJWNTRV48tBpQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
tinymce.init({
    selector: '#order_details_email_template',
    plugins: 'image table lists media link code',
    toolbar: 'undo redo | insert_meta | blocks | bold italic forecolor | align | outdent indent | numlist bullist | table image link | code',
    menubar: 'edit view insert format tools table',
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    content_css: false,
    height: 600,
    branding: false,
    promotion: false,
    automatic_uploads: false,
    image_title: true,
    image_description: true,
    license_key: 'gpl',
    setup: function (editor) {
        editor.ui.registry.addMenuButton('insert_meta', {
            icon: 'addtag',
            tooltip: 'Insert Meta Tag',
            fetch: function (callback) {
                const items = [
                    {
                        type: 'menuitem',
                        text: 'Insert Order ID',
                        onAction: function () {
                            editor.insertContent('%order_id%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Subtotal',
                        onAction: function () {
                            editor.insertContent('%subtotal%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert First Name',
                        onAction: function () {
                            editor.insertContent('%first_name%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Last Name',
                        onAction: function () {
                            editor.insertContent('%last_name%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Address Street',
                        onAction: function () {
                            editor.insertContent('%address_street%');
                        }
                    },    
                    {
                        type: 'menuitem',
                        text: 'Insert Address City',
                        onAction: function () {
                            editor.insertContent('%address_city%');
                        }
                    },              
                    {
                        type: 'menuitem',
                        text: 'Insert Address State',
                        onAction: function () {
                            editor.insertContent('%address_state%');
                        }
                    },   
                    {
                        type: 'menuitem',
                        text: 'Insert Address ZIP',
                        onAction: function () {
                            editor.insertContent('%address_zip%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Address Country',
                        onAction: function () {
                            editor.insertContent('%address_country%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Products Template',
                        onAction: function () {
                            editor.insertContent('%products_template%');
                        }
                    }
                ];
                callback(items);
            }
        });
    }
});
tinymce.init({
    selector: '#order_notification_email_template',
    plugins: 'image table lists media link code',
    toolbar: 'undo redo | insert_meta | blocks | bold italic forecolor | align | outdent indent | numlist bullist | table image link | code',
    menubar: 'edit view insert format tools table',
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    content_css: false,
    height: 600,
    branding: false,
    promotion: false,
    automatic_uploads: false,
    image_title: true,
    image_description: true,
    license_key: 'gpl',
    setup: function (editor) {
        editor.ui.registry.addMenuButton('insert_meta', {
            icon: 'addtag',
            tooltip: 'Insert Meta Tag',
            fetch: function (callback) {
                const items = [
                    {
                        type: 'menuitem',
                        text: 'Insert Order ID',
                        onAction: function () {
                            editor.insertContent('%order_id%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Subtotal',
                        onAction: function () {
                            editor.insertContent('%subtotal%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert First Name',
                        onAction: function () {
                            editor.insertContent('%first_name%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Last Name',
                        onAction: function () {
                            editor.insertContent('%last_name%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Address Street',
                        onAction: function () {
                            editor.insertContent('%address_street%');
                        }
                    },    
                    {
                        type: 'menuitem',
                        text: 'Insert Address City',
                        onAction: function () {
                            editor.insertContent('%address_city%');
                        }
                    },              
                    {
                        type: 'menuitem',
                        text: 'Insert Address State',
                        onAction: function () {
                            editor.insertContent('%address_state%');
                        }
                    },   
                    {
                        type: 'menuitem',
                        text: 'Insert Address ZIP',
                        onAction: function () {
                            editor.insertContent('%address_zip%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Address Country',
                        onAction: function () {
                            editor.insertContent('%address_country%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Products Template',
                        onAction: function () {
                            editor.insertContent('%products_template%');
                        }
                    }
                ];
                callback(items);
            }
        });
    }
});
tinymce.init({
    selector: '#resetpass_email_template',
    plugins: 'image table lists media link code',
    toolbar: 'undo redo | insert_meta | blocks | bold italic forecolor | align | outdent indent | numlist bullist | table image link | code',
    menubar: 'edit view insert format tools table',
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    content_css: false,
    height: 600,
    branding: false,
    promotion: false,
    automatic_uploads: false,
    image_title: true,
    image_description: true,
    license_key: 'gpl',
    setup: function (editor) {
        editor.ui.registry.addMenuButton('insert_meta', {
            icon: 'addtag',
            tooltip: 'Insert Meta Tag',
            fetch: function (callback) {
                const items = [
                    {
                        type: 'menuitem',
                        text: 'Insert Reset Password Link',
                        onAction: function () {
                            editor.insertContent('%link%');
                        }
                    }
                ];
                callback(items);
            }
        });
    }
});
</script>
<?php endif; ?>

<?=template_admin_footer()?>