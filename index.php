<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . APP_BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . APP_BASE_URL . '/login.php');
}
exit;
