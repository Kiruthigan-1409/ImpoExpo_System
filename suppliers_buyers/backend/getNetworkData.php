<?php
header("Content-Type: application/json");
include "connect.php";

// fetch coordinates dynamically from OpenStreetMap
function getCoordinates($place) {
    $encoded = urlencode($place);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=$encoded";

    $opts = [
        "http" => [
            "header" => "User-Agent: SupplierBuyerMap/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
        return [(float)$data[0]['lat'], (float)$data[0]['lon']];
    }
    return [0, 0]; 
}

$response = [
    "suppliers" => [],
    "buyers" => [],
    "connections" => []
];

// suppliers
$sql_suppliers = "SELECT supplier_id, suppliername, s_country FROM supplier";
$result_suppliers = $conn->query($sql_suppliers);

$countriesCache = []; 

while ($row = $result_suppliers->fetch_assoc()) {
    $country = trim($row['s_country']);

    if (!isset($countriesCache[$country])) {
        $countriesCache[$country] = getCoordinates($country);
    }

    [$lat, $lng] = $countriesCache[$country];

    $response["suppliers"][] = [
        "supplier_id" => $row["supplier_id"],
        "suppliername" => $row["suppliername"],
        "s_country" => $row["s_country"],
        "lat" => $lat,
        "lng" => $lng
    ];
}

//buyers
$sql_buyers = "SELECT buyer_id, buyername, b_city FROM buyer";
$result_buyers = $conn->query($sql_buyers);

$citiesCache = [];

while ($row = $result_buyers->fetch_assoc()) {
    $city = trim($row['b_city']);

    if (!isset($citiesCache[$city])) {
        $citiesCache[$city] = getCoordinates($city . ", Sri Lanka");
    }

    [$lat, $lng] = $citiesCache[$city];

    $response["buyers"][] = [
        "buyer_id" => $row["buyer_id"],
        "buyername" => $row["buyername"],
        "b_city" => $row["b_city"],
        "lat" => $lat,
        "lng" => $lng
    ];
}

//  build supplier–buyer connections
foreach ($response["suppliers"] as $s) {
    foreach ($response["buyers"] as $b) {
        $response["connections"][] = [
            "supplierCoords" => [$s["lat"], $s["lng"]],
            "buyerCoords" => [$b["lat"], $b["lng"]],
        ];
    }
}

echo json_encode($response);
$conn->close();
?>
