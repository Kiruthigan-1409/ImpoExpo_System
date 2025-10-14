<?php
include "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['order_id'];
    $buyer = $_POST['buyer'];
    $product = $_POST['product_id'];
    $size = $_POST['size'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['total_price'];
    $deadline = $_POST['deadline_date'];
    $status = $_POST['status'];
    $payment = isset($_POST['payment']) ? 1 : 0;
    $delivery = isset($_POST['delivery']) ? 1 : 0;
    $address = $_POST['address'];
    $description = $_POST['description'];

    $sql = "UPDATE order_table SET 
                buyer_id='$buyer',
                product_id='$product',
                size='$size',
                quantity='$quantity',
                total_price='$total_price',
                deadline_date='$deadline',
                status='$status',
                payment_confirmation='$payment',
                delivery_confirmation='$delivery',
                order_address='$address',
                description='$description'
            WHERE order_id='$id'";

    if ($conn->query($sql)) {
        header("Location: index.php?msg=Order+updated+successfully");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
