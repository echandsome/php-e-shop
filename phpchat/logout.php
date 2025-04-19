<?php
session_start();
// Destroy session data
session_destroy();
// Remove the chat secret cookie
if (isset($_COOKIE['chat_secret'])) {
    unset($_COOKIE['chat_secret']);
    setcookie('chat_secret', '', time() - 3600);
}
?>