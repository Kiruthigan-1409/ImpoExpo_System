<?php
// Database connection
define('DB_SERVER', '142.91.102.107');
define('DB_USER', 'sysadmin_sliitppa25');
define('DB_PASS', ':%ngWE6;?*wm$Qy|');
define('DB_NAME', 'sysadmin_sliitppa25');
$conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $product_id = $_POST['product_id'];
    $buyer_id = $_POST['buyer'];
    $size = $_POST['size'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['total_price'];
    $deadline_date = $_POST['deadline_date'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $address = $_POST['address'];
    $payment_confirmation = isset($_POST['payment']) ? 1 : 0;
    $delivery_confirmation = isset($_POST['delivery']) ? 1 : 0;

    $sql = "UPDATE order_table SET
            product_id = '$product_id',
            buyer_id = '$buyer_id',
            order_address = '$address',
            size = '$size',
            quantity = '$quantity',
            total_price = '$total_price',
            deadline_date = '$deadline_date',
            description = '$description',
            status = '$status',
            payment_confirmation = '$payment_confirmation',
            delivery_confirmation = '$delivery_confirmation'
            WHERE order_id = '$order_id'";

    if ($conn->query($sql) === TRUE) {
        // Redirect back to index with success flag
        header('Location: index.php?updated=1');
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>