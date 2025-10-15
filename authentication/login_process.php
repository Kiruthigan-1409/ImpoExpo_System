<?php

session_start();

// Database connection
$host = "142.91.102.107";           
$user = "sysadmin_sliitppa25";      
$pass = ":%ngWE6;?*wm\$Qy|";                  
$dbname = "sysadmin_sliitppa25";    

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$username = trim($_POST['username']);
$password = trim($_POST['password']);

$sql = "SELECT * FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['admin'] = $username;
    header("ppa_final/delivery_management/delivery.php");
    exit();
} else {
    echo "<script>alert('Invalid username or password'); window.location.href='login.php';</script>";
}

$stmt->close();
$conn->close();
?>
