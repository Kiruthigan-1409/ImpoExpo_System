<?php
header("Content-Type: application/json");
include "db.php";

if (!isset($_GET["id"])) {
    echo json_encode(["success" => false, "error" => "Missing payment reference"]);
    exit;
}

$id = $conn->real_escape_string($_GET["id"]);

$sql = "
    SELECT 
        p.*,
        b.buyername AS buyer_name
    FROM payments p
    LEFT JOIN buyer b ON p.buyer_id = b.buyer_id
    WHERE p.payment_reference = '$id'
    LIMIT 1
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $row["buyer_display"] = $row["buyer_name"];
    $row["success"] = true; // ✅ add this
    echo json_encode($row);
} else {
    echo json_encode(["success" => false, "error" => "Payment not found"]);
}
$conn->close();
?>
