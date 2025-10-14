<?php

include "connect.php"; 

// Fetch countries
$countries = [];
$result = $conn->query("SELECT DISTINCT s_country FROM supplier");
while ($row = $result->fetch_assoc()) {
    $countries[] = $row['s_country'];
}


$products = [];
$result = $conn->query("
    SELECT DISTINCT p.product_name 
    FROM supplier s
    JOIN products p ON s.s_productid = p.product_id
");
while ($row = $result->fetch_assoc()) {
    $products[] = $row['product_name'];
}

$cities = [];
$result = $conn->query('SELECT DISTINCT b_city FROM buyer');

while ($row = $result->fetch_assoc()) {
    $cities[] = $row['b_city'];
}

//buyer products
$buyerproducts = [];
$result = $conn->query("
    SELECT DISTINCT p.product_name 
    FROM buyer b
    JOIN products p ON b.b_productid = p.product_id
");
while ($row = $result->fetch_assoc()) {
    $buyerproducts[] = $row['product_name'];
}



header('Content-Type: application/json');

echo json_encode([
    "countries" => $countries,
    "products" => $products,
    "cities"=> $cities,
    "buyerproducts"=> $buyerproducts
]);

?>


