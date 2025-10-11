<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"])) {
    echo json_encode(["success" => false, "error" => "Missing payment reference"]);
    exit;
}

$id = $conn->real_escape_string($data["id"]);
$sql = "DELETE FROM payments WHERE payment_reference = '$id'";

if ($conn->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>
