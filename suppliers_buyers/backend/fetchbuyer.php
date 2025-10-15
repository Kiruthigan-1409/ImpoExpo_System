

<?php
include "connect.php";

function getBuyers($fromDate = null, $toDate = null, $entries = 10) {
    global $conn;

    $sql = "
        SELECT 
            b.buyer_id, 
            b.buyername, 
            b.b_city, 
            p.product_name, 
            b.b_email, 
            b.b_contact, 
            COUNT(i.delivery_id) AS total_deliveries, 
            COALESCE(SUM(f.amount), 0) AS totalRevenue
        FROM buyer b
        LEFT JOIN deliveries i ON b.buyername = i.buyer_name
        LEFT JOIN products p ON b.b_productid = p.product_id
        LEFT JOIN payments f ON b.buyer_id = f.buyer_id
        WHERE 1=1
    ";

    if (!empty($fromDate) && !empty($toDate)) {
        $sql .= "
            AND (i.actual_date BETWEEN ? AND ?)
            AND (f.payment_date BETWEEN ? AND ?)
        ";
    }

    $sql .= " 
        AND (f.status = 'completed')
        GROUP BY b.buyer_id
        ORDER BY total_deliveries DESC
        LIMIT ?";

    $stmt = $conn->prepare($sql);

    if (!empty($fromDate) && !empty($toDate)) {
      
        $stmt->bind_param('ssssi', $fromDate, $toDate, $fromDate, $toDate, $entries);
    } else {
       
        $stmt->bind_param('i', $entries);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $buyers = [];
    while ($row = $result->fetch_assoc()) {
        $buyers[] = $row;
    }

    return $buyers;
}