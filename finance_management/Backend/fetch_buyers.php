<?php
header("Content-Type: application/json");
include "db.php";

$sql = "SELECT buyer_id, buyername FROM buyer ORDER BY buyer_id ASC";
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
