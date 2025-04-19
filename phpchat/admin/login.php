<?php
// Include the config file
include_once '../config.php';
include_once '../main.php';
// Output message
$msg = '';
// Capture form data and authenticate the user
if (isset($_POST['admin_email'], $_POST['admin_password'])) {
    // Check if account exists with the captured email
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE (role = "Admin" OR role = "Operator") AND email = ?');
    $stmt->execute([ $_POST['admin_email'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // Verify password if account exists
    if ($account && password_verify($_POST['admin_password'], $account['password'])) {
        // Authenticate the user
        $_SESSION['chat_account_loggedin'] = TRUE;
        $_SESSION['chat_account_id'] = $account['id'];
        $_SESSION['chat_account_role'] = $account['role'];
        // Update account info
        $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $stmt = $pdo->prepare('UPDATE accounts SET last_seen = ?, ip = ?, user_agent = ? WHERE id = ?');
        $stmt->execute([ date('Y-m-d H:i:s'), $ip, $user_agent, $account['id'] ]);
        // Update all accounts status to "Idle" if they are offline
        $stmt = $pdo->prepare('UPDATE accounts SET status = "Idle" WHERE last_seen < ?');
        $stmt->execute([ date('Y-m-d H:i:s', strtotime('-2 hours')) ]);
        // Redirect to dashboard page
        header('Location: index.php');
        exit;
    } else {
        $msg = 'Incorrect email and/or password!';
    }
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Admin Login</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
        <link href="admin.css" rel="stylesheet" type="text/css">
	</head>
	<body class="login">
        <form action="" method="post" class="">
            <h2>Admin Login</h2>
            <input type="email" name="admin_email" placeholder="Email" required>
            <input type="password" name="admin_password" placeholder="Password" required>
            <input type="submit" value="Login">
            <p><?=$msg?></p>
        </form>
    </body>
</html>