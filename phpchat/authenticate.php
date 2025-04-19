<?php
// include the main file
include 'main.php';
// Variables
$msg = '';
$status = 'error';
// Validate the form data
if (!isset($_POST['name'], $_POST['email'])) {
    $msg = 'Please enter a valid name and email address!';
} else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
	$msg = 'Please enter a valid email address!';
} else if (!preg_match('/^[a-zA-Z\s]+$/', $_POST['name'])) {
    $msg = 'Name must contain only letters!';
} else if (authentication_required && !isset($_POST['password'])) {
    $status = 'password_field_required';
} else if (isset($_POST['password']) && empty($_POST['password'])) {
    $status = 'password_field_required';
} else if (isset($_POST['password']) && (strlen($_POST['password']) < 3 || strlen($_POST['password']) > 20)) {
    $msg = 'Password must be between 3 and 20 characters long!';
} else {
    // Form data is valid...
    // Select account from the database based on the email address
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
    $stmt->execute([ $_POST['email'] ]);
    // Fetch the results and return them as an associative array
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // Does the account exist?
    if ($account) {
        // Yes, it does... Check whether the user is an operator or guest
        if ($account['role'] == 'Operator' || $account['role'] == 'Admin') {
            // User is operator, so show the password input field on the front-end
            $msg = 'Please use the <a href="admin/">admin panel</a> to login!';
        } else if ($account['role'] == 'Guest') {
            // User is a guest
            // Authenticate the guest
            if (!empty($account['password'])) {
                // User is an operator and provided a password
                if (isset($_POST['password']) && password_verify($_POST['password'], $account['password'])) {
                    // Password is correct! Authenticate the operator
                    $_SESSION['chat_widget_account_loggedin'] = TRUE;
                    $_SESSION['chat_widget_account_id'] = $account['id'];
                    $_SESSION['chat_widget_account_role'] = $account['role']; 
                    // Update the secret code
                    update_info($pdo, $account['id'], $account['email'], $account['secret']);
                    // Ouput: success
                    $status = 'success';
                } else if (!isset($_POST['password'])) {
                    // Password field is required
                    $status = 'password_field_required';
                } else {
                    // Invalid password
                    $msg = 'Invalid credentials!';
                }
            } else {
                // Guests don't need a password
                $_SESSION['chat_widget_account_loggedin'] = TRUE;
                $_SESSION['chat_widget_account_id'] = $account['id'];
                $_SESSION['chat_widget_account_role'] = $account['role']; 
                // Update secret code
                update_info($pdo, $account['id'], $account['email'], $account['secret'], isset($_POST['password']) ? $_POST['password'] : '');
                // Output: success
                $status = 'success';
            }
        }
    } else {
        // Hash password, if there is one
        $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
        // Current date
        $date = date('Y-m-d H:i:s');
        // Name
        $name = isset($_POST['name']) ? $_POST['name'] : 'Guest';
        // Accounts doesn't exist, so create one
        $stmt = $pdo->prepare('INSERT INTO accounts (email, password, full_name, role, last_seen, registered) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([ $_POST['email'], $password, $name, 'Guest', $date, $date ]);
        // Retrieve the account ID
        $id = $pdo->lastInsertId();
        // Authenticate the new user
        $_SESSION['chat_widget_account_loggedin'] = TRUE;
        $_SESSION['chat_widget_account_id'] = $id;   
        $_SESSION['chat_widget_account_role'] = 'Guest'; 
        // Update secret code
        update_info($pdo, $id, $_POST['email']);
        // Output: success
        $status = 'create_success';
    }
}
// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
// Output JSON
echo json_encode([
    'status' => $status,
    'msg' => $msg
]);
?>