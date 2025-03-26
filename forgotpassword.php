<?php
// Prevent direct access to file
defined('shoppingcart') or exit;
// Page title
$page = 'Forgot';
// Error msg
$error_msg = '';
// Success msg
$success_msg = '';
// Check if the user is logged in
if (isset($_SESSION['account_loggedin'])) {
    header('Location: ' . url('index.php'));
    exit;
}
// Now we check if the data from the forgot password form was submitted, isset() will check if the data exists.
if (isset($_POST['email'])) {
    // Prepare our SQL, preparing the SQL statement will prevent SQL injection.
    $stmt = $pdo->prepare('SELECT email FROM accounts WHERE email = ?');
    $stmt->execute([ $_POST['email'] ]);
	$account = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if the acc exists...
    if ($account) {
        // Email exist, so generate a strong unique reset code
    	$unique_reset_code = hash('sha256', uniqid() . $account['email'] . secret_key);
		// Update the reset code in the database
        $stmt = $pdo->prepare('UPDATE accounts SET reset_code = ? WHERE email = ?');
        $stmt->execute([ $unique_reset_code, $account['email'] ]);
		// Send email with reset link
		send_password_reset_email($account['email'], $unique_reset_code);
		// Output success message
        $success_msg = 'Reset password link has been sent to your email!';
    } else {
		// Output error message
        $error_msg = 'We do not have an account with that email!';
    }
}
// Handle reset password form
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE reset_code = ?');
    $stmt->execute([ $_GET['code'] ]);
	$account = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if the account exists...
    if ($account) {
        // Set page
        $page = 'Reset';
		// Validate form data
        if (isset($_POST['npassword'], $_POST['cpassword'])) {
            if (strlen($_POST['npassword']) > 20 || strlen($_POST['npassword']) < 5) {
            	$error_msg = 'Password must be between 5 and 20 characters long!';
            } else if ($_POST['npassword'] != $_POST['cpassword']) {
                $error_msg = 'Passwords must match!';
            } else {
				// Hash the new password
				$password = password_hash($_POST['npassword'], PASSWORD_DEFAULT);
				// Update the password in the database
                $stmt = $pdo->prepare('UPDATE accounts SET password = ?, reset_code = "" WHERE reset_code = ?');
                $stmt->execute([ $password, $_GET['code'] ]);
				// Output success message
                $success_msg = 'Password has been reset! You can now <a href="' . url('index.php?page=myaccount') . '" class="form-link">login</a>!';
            }
        }
    } else {
		// Coundn't find the account with that reset code
        exit('Incorrect code provided!');
    }
}
?>
<?=template_header($page. ' Password')?>

<div class="content-wrapper forgot-password">

    <h1 class="page-title"><?=$page?> Password</h1>

    <?php if ($page == 'Forgot'): ?>
    <form action="<?=url('index.php?page=forgotpassword')?>" method="post" class="form">

        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" placeholder="john@example.com" class="form-input expand" required>

        <button type="submit" class="btn">Submit</button>

    </form>
    <?php else: ?>
    <form action="<?=url('index.php?page=forgotpassword', ['code' => htmlspecialchars($_GET['code'], ENT_QUOTES)])?>" method="post" class="form">

        <label for="npassword" class="form-label">New Password</label>
        <input type="password" name="npassword" id="npassword" placeholder="New Password" required class="form-input expand" autocomplete="new-password">

        <label for="cpassword" class="form-label">Confirm Password</label>
        <input type="password" name="cpassword" id="cpassword" placeholder="Confirm Password" required class="form-input expand" autocomplete="new-password">

        <button type="submit" class="btn">Submit</button>

    </form>   
    <?php endif; ?>

    <?php if ($error_msg): ?>
    <p class="error pad-top-2"><?=$error_msg?></p>
    <?php endif; ?>

    <?php if ($success_msg): ?>
    <p class="success pad-top-2"><?=$success_msg?></p>
    <?php endif; ?>

</div>

<?=template_footer()?>