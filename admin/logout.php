<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Logout user
$auth->logout();

// Redirect ke halaman login
header('Location: login.php');
exit();
?>