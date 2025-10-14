
    <?php
        header("Content-Type: application/json");
        include "connect.php";



        // Fetch products
        $sql = "SELECT product_id, product_name FROM products";
        $result = $conn->query($sql);

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        echo json_encode($products);
        $conn->close();
    ?>
