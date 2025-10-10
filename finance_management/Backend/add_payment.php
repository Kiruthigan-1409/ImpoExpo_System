<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 0); // prevent HTML output
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
$bank_ref   = isset($data['bank_reference']) && $data['bank_reference'] !== ""
              ? "'" . $conn->real_escape_string($data['bank_reference']) . "'"
              : "NULL";

$cheque_ref = isset($data['cheque_reference']) && $data['cheque_reference'] !== ""
              ? "'" . $conn->real_escape_string($data['cheque_reference']) . "'"
              : "NULL";

$notes      = isset($data['notes']) && $data['notes'] !== ""
              ? "'" . $conn->real_escape_string($data['notes']) . "'"
              : "NULL";

$sql = "INSERT INTO payments 
    (payment_reference, payment_date, buyer_id, delivery_id, amount, payment_method, status, bank_reference, cheque_reference, notes) 
    VALUES 
    ('$reference', '$date', $buyer_id, $delivery_id, $amount, '$method', '$status', $bank_ref, $cheque_ref, $notes)";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "payment_reference" => $reference]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>
