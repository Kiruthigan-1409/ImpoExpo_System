

<?php
header("Content-Type: application/json");
include "connect.php";


$sql = "SELECT s.supplier_id, s.suppliername, s.s_company, s.s_country, 
               s.s_city, s.s_email, 
               CONCAT(s.s_country_code, ' ', s.s_contact) AS contact,
               p.product_id, 
               p.product_name, 
               s.s_status
        FROM supplier s
        JOIN products p ON s.s_productid = p.product_id";

        $result = $conn->query($sql);

        $suppliers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row;
            }
        }

        echo json_encode($suppliers);
        $conn->close();
?>


