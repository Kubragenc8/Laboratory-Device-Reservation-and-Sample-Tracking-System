<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if (!isAdmin()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}