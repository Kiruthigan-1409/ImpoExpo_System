<?php
// db.php

$host = "142.91.102.107";           // Server
$user = "sysadmin_sliitppa25";                // Database username (change if not root)
$pass = ":%ngWE6;?*wm\$Qy|";
$dbname = "sysadmin_sliitppa25"; // Your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8mb4");

// Now $conn can be used in queries
?>
