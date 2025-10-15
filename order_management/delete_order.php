<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    
    // First, check if the order exists
    $check_sql = "SELECT * FROM order_table WHERE order_id = '$order_id'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Order exists, proceed with deletion
        $sql = "DELETE FROM order_table WHERE order_id = '$order_id'";
        
        if ($conn->query($sql) === TRUE) {
            // Redirect back to index with success flag
            header('Location: index.php?deleted=1');
            exit();
        } else {
            // Redirect back with error flag
            header('Location: index.php?delete_error=1&message=' . urlencode($conn->error));
            exit();
        }
    } else {
        // Order not found
        header('Location: index.php?delete_error=1&message=' . urlencode("Order not found"));
        exit();
    }
} else {
    // Invalid request method
    header('Location: index.php?delete_error=1&message=' . urlencode("Invalid request"));
    exit();
}

$conn->close();
?>