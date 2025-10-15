        <?php
       include 'db.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_id = $_POST['order_id'];
            $product_id = $_POST['product_id'];
            $buyer_id = $_POST['buyer']; // dropdown gives buyer_id
            $size = $_POST['size'];
            $quantity = $_POST['quantity'];
            $total_price = $_POST['total_price'];
            $deadline_date = $_POST['deadline_date'];
            $description = $_POST['description'];
            $status = $_POST['status'];
            $address = $_POST['address'];
            $payment_confirmation = isset($_POST['payment']) ? 1 : 0;
            $delivery_confirmation = isset($_POST['delivery']) ? 1 : 0;

            $sql = "INSERT INTO order_table
                (order_id, product_id, buyer_id, order_address, size, quantity, total_price, order_placed_date, deadline_date, description, status, payment_confirmation, delivery_confirmation)
                VALUES
                ('$order_id', '$product_id', '$buyer_id', '$address', '$size', '$quantity', '$total_price', NOW(), '$deadline_date', '$description', '$status', '$payment_confirmation', '$delivery_confirmation')";

            if ($conn->query($sql) === TRUE) {
                // Redirect back to index with success flag
                header('Location: index.php?added=1');
                exit();
            } else {
                echo "Error: " . $conn->error;
            }
        }

        $conn->close();
        ?>
