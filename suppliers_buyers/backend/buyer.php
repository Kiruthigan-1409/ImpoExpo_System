
<?php

header("Content-Type: application/json");
include "connect.php";


$sql = "SELECT b.buyer_id, b.buyername,b.b_address,b.b_city, 
            b.b_email, 
                b.b_contact,
               p.product_id, 
               p.product_name, 
               b.b_status
        FROM buyer b
        JOIN products p ON b.b_productid = p.product_id";

$result = $conn->query($sql);

$buyers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $buyers[] = $row;
    }
}

echo json_encode($buyers);
$conn->close();

?>
