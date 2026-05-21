<?php
session_start();
session_destroy();
header('Location: ' . (defined('APP_BASE_URL') ? APP_BASE_URL : '') . '/login.php');
exit;
