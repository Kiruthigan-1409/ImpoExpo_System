<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

$reference = $conn->real_escape_string($data['payment_reference']);
$date = $conn->real_escape_string($data['payment_date']);
$buyer_id = intval($data['buyer_id']);
$delivery_id = !empty($data['delivery_id']) ? intval($data['delivery_id']) : "NULL";
$amount = floatval($data['amount']);
$method = $conn->real_escape_string($data['payment_method']);
$status = $conn->real_escape_string($data['status']);
$bank_ref = !empty($data['bank_reference']) ? "'" . $conn->real_escape_string($data['bank_reference']) . "'" : "NULL";
$cheque_ref = !empty($data['cheque_reference']) ? "'" . $conn->real_escape_string($data['cheque_reference']) . "'" : "NULL";
$notes = !empty($data['notes']) ? "'" . $conn->real_escape_string($data['notes']) . "'" : "NULL";

$sql = "UPDATE payments SET
    payment_date = '$date',
    buyer_id = $buyer_id,
    delivery_id = $delivery_id,
    amount = $amount,
    payment_method = '$method',
    status = '$status',
    bank_reference = $bank_ref,
    cheque_reference = $cheque_ref,
    notes = $notes
    WHERE payment_reference = '$reference'";

if ($conn->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>
