<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';
$dir = ($lang == 'ar') ? 'rtl' : 'ltr';

$trans = require __DIR__ . "/lang/$lang.php";