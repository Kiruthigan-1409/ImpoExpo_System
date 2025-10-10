<?php
header("Content-Type: application/json");
include "db.php";

// Find max reference number
$result = $conn->query("SELECT payment_reference FROM payments ORDER BY id DESC LIMIT 1");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    preg_match('/PAY-(\d+)/', $row['payment_reference'], $matches);
    $nextNum = isset($matches[1]) ? intval($matches[1]) + 1 : 1000;
} else {
    $nextNum = 1000;
}

echo json_encode(["nextRef" => "PAY-" . $nextNum]);
?>
