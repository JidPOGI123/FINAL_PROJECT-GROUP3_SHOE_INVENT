<?php
require_once 'config/auth_check.php';

session_unset();
session_destroy();

header("Location: login.php");
exit();
?>