<?php
session_start();
// Destroy session data
session_destroy();
// Redirect to login page
header('Location: login.php');
?>