<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
include "db.php";


 // fall back to a safer query without the deliveries join so the UI still works.

$sql_with_deliveries = "
    SELECT 
        p.payment_reference,
        p.payment_date,
        p.amount,
        p.payment_method,
        p.status,
        p.bank_reference,
        p.cheque_reference,
        p.notes,
        p.buyer_id,
        p.delivery_id,
        CONCAT(b.buyername) AS buyer_name
    FROM payments p
    LEFT JOIN buyer b ON p.buyer_id = b.buyer_id
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql_with_deliveries);

if ($result === false) {
    // Fallback without deliveries table/column dependency
    $sql_fallback = "
        SELECT 
            p.payment_reference,
            p.payment_date,
            p.amount,
            p.payment_method,
            p.status,
            p.bank_reference,
            p.cheque_reference,
            p.notes,
            p.buyer_id,
            p.delivery_id,
            CONCAT(b.buyername) AS buyer_name
        FROM payments p
        LEFT JOIN buyer b ON p.buyer_id = b.buyer_id
        ORDER BY p.created_at DESC
    ";
    $result = $conn->query($sql_fallback);
    $add_delivery_placeholder = true;
} else {
    $add_delivery_placeholder = false;
}

$payments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($add_delivery_placeholder && !isset($row["delivery_description"])) {
            // Keep the frontend happy even when deliveries are not joined
            $row["delivery_description"] = null;
        }
        // normalize numeric types for frontend calculations
        $row["amount"] = (float)$row["amount"];
        $row["buyer_id"] = (string)$row["buyer_id"];
        $payments[] = $row;
    }
}

echo json_encode($payments);
$conn->close();
