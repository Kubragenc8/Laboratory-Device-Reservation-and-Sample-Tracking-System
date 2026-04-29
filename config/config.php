<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Europe/Istanbul');

define('APP_NAME', 'Laboratory Reservation System');

define('PROJECT_URL', 'http://localhost/Laboratory-Device-Reservation-and-Sample-Tracking-System/');
define('BASE_URL', PROJECT_URL . 'public/');
define('ASSETS_URL', PROJECT_URL . 'assets/');

define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}