<?php
/**
 * SIAMKA - Main Entry Point
 */
session_start();
require_once "config/database.php";

if(isset($_SESSION["user_id"])) {
    $role = $_SESSION["role"];
    header("Location: modules/dashboard/" . $role . ".php");
} else {
    header("Location: modules/auth/login.php");
}
exit;
?>