
<?php
include "connect.php";

function getSuppliers($fromDate = null, $toDate = null, $entries = 10) {
    global $conn;

    $sql = "
        SELECT 
            s.supplier_id, 
            s.suppliername, 
            s.s_country, 
            p.product_name, 
            s.s_email, 
            s.s_country_code, 
            s.s_contact, 
            COUNT(i.import_id) AS total_imports, 
            COALESCE(SUM(o.total_price), 0) AS totalsupplyorders
        FROM supplier s
        LEFT JOIN imports i ON s.supplier_id = i.supplier_id
        LEFT JOIN products p ON s.s_productid = p.product_id
        LEFT JOIN order_table o ON p.product_id = o.product_id
    ";

    if (!empty($fromDate) && !empty($toDate)) {
        $sql .= " 
            WHERE i.import_date BETWEEN ? AND ? 
              AND (o.order_placed_date BETWEEN ? AND ? OR o.order_placed_date IS NULL)";
    }

    $sql .= " 
        GROUP BY s.supplier_id
        ORDER BY total_imports DESC
        LIMIT ?";

    $stmt = $conn->prepare($sql);

    if (!empty($fromDate) && !empty($toDate)) {
      
        $stmt->bind_param('ssssi', $fromDate, $toDate, $fromDate, $toDate, $entries);
    } else {
       
        $stmt->bind_param('i', $entries);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $row['totalsupplyorders'] = $row['totalsupplyorders'] ?? 0;
        $suppliers[] = $row;
    }

    return $suppliers;
}

