<?php
$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
$baseURL .= '://' . $_SERVER['HTTP_HOST'] . '/ImpoExpo_System/';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: $baseURL/authentication/index.php");
    exit();
}
