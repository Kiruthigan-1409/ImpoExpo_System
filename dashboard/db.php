<?php


$host = "142.91.102.107";           
$user = "sysadmin_sliitppa25";             
$pass = ":%ngWE6;?*wm\$Qy|";
$dbname = "sysadmin_sliitppa25"; 

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
