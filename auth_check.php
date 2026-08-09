<?php
// FILE: auth_check.php
require_once 'config.php';
session_start();

if (
    !isset($_SESSION['user_id']) ||       // no user id
    !isset($_SESSION['is_admin']) ||     
    $_SESSION['is_admin']                 
) {
    header("Location: login.php");
    exit;
}
