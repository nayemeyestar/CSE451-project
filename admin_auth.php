<?php
// FILE: admin_auth.php
require_once 'config.php';
session_start();

if (
    !isset($_SESSION['admin_id']) ||      // no admin id
    !isset($_SESSION['is_admin']) ||      // no role flag
    !$_SESSION['is_admin']                // role is not admin
) {
    header("Location: login.php");
    exit;
}
