
<?php

define('DB_SERVER', '142.91.102.107');
define('DB_USER', 'sysadmin_sliitppa25');
define('DB_PASS', ':%ngWE6;?*wm$Qy|');
define('DB_NAME', 'sysadmin_sliitppa25');
$conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASS,DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
