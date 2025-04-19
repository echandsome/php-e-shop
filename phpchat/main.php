<?php
// Initialize sessions
session_start();
// Include the configuration file
include_once 'config.php';
try {
    // Attempt to connect to our MySQL database
	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    // Output all connection errors. We want to know what went wrong...
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // Failed to connect! Check the database variables and ensure your database exists with all tables.
	exit('Failed to connect to database: ' . $exception->getMessage());
}
// The following function will check whether the user is logged-in or not.
function is_loggedin($pdo) {
    // Session loggedin?
    if (isset($_SESSION['chat_widget_account_loggedin'])) {
        // Update the last seed date
        $stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
        $stmt->execute([ date('Y-m-d H:i:s'), $_SESSION['chat_widget_account_id'] ]);
        return TRUE;
    }
    // Check if the secret cookie is declared in the browser cookies
    if (isset($_COOKIE['chat_secret']) && !empty($_COOKIE['chat_secret'])) {
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE secret = ?');
        $stmt->execute([ $_COOKIE['chat_secret'] ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        // Does the account exist?
        if ($account) {
            // Yes it does... Authenticate the user
            $_SESSION['chat_widget_account_loggedin'] = TRUE;
            $_SESSION['chat_widget_account_id'] = $account['id'];
            $_SESSION['chat_widget_account_role'] = $account['role']; 
            return TRUE;
        }
    }
    // User isn't logged-in!
    return FALSE;
}
// The following function will update the user's secret code along with client info in the databse
function update_info($pdo, $id, $email, $current_secret = '', $password = '') {
    // Get client IP address
    $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    // Generate the code using the password hash function. Make sure you change 'yoursecretkey'.
    $cookiehash = !empty($current_secret) ? $current_secret : password_hash($id . $email . 'yoursecretkey', PASSWORD_DEFAULT);
    // The number of days the secret cookie will be remembered
    $days = 30;
    // Create the new cookie
    setcookie('chat_secret', $cookiehash, (int)(time()+60*60*24*$days));
    // Update the secret code in the databse
    if (empty($password)) {
        $stmt = $pdo->prepare('UPDATE accounts SET secret = ?, ip = ?, user_agent = ? WHERE id = ?');
        $stmt->execute([ $cookiehash, $ip, $user_agent, $id ]);
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE accounts SET secret = ?, ip = ?, user_agent = ?, password = ? WHERE id = ?');
        $stmt->execute([ $cookiehash, $ip, $user_agent, $password, $id ]);
    }
}
// The following function will be used to assign a unique icon color to our users
function color_from_string($string) {
    // The list of hex colors
    $colors = ['#34568B','#FF6F61','#6B5B95','#88B04B','#F7CAC9','#92A8D1','#955251','#B565A7','#009B77','#DD4124','#D65076','#45B8AC','#EFC050','#5B5EA6','#9B2335','#DFCFBE','#BC243C','#C3447A','#363945','#939597','#E0B589','#926AA6','#0072B5','#E9897E','#B55A30','#4B5335','#798EA4','#00758F','#FA7A35','#6B5876','#B89B72','#282D3C','#C48A69','#A2242F','#006B54','#6A2E2A','#6C244C','#755139','#615550','#5A3E36','#264E36','#577284','#6B5B95','#944743','#00A591','#6C4F3D','#BD3D3A','#7F4145','#485167','#5A7247','#D2691E','#F7786B','#91A8D0','#4C6A92','#838487','#AD5D5D','#006E51','#9E4624'];
    // Find color based on the string
    $colorIndex = hexdec(substr(sha1($string), 0, 10)) % count($colors);
    // Return the hex color
    return $colors[$colorIndex];
}
// The following function will decode UTF8 emojis 
function decode_emojis($string) {
    $emoji_array = explode(',', emoji_list);
    foreach ($emoji_array as $emoji) {
        $string = str_replace('&amp;#x' . $emoji . ';', '&#x' . $emoji . ';', $string);
    }
    return $string;
}
?>