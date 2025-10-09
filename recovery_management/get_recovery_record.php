<?php
include 'db.php';  // Use your db connection file

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid record id']);
    exit;
}

// Fetch the record from database
$stmt = $conn->prepare("
  SELECT r.*, d.quantity AS delivery_quantity
  FROM recovery_records r
  LEFT JOIN deliveries d ON r.original_delivery COLLATE utf8mb4_general_ci = d.delivery_code COLLATE utf8mb4_general_ci
  WHERE r.id = ? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($record = $res->fetch_assoc()) {
  echo json_encode(['success' => true, 'record' => $record]);
} else {
  echo json_encode(['success' => false, 'message' => 'Record not found']);
}
$stmt->close();
$conn->close();

?>
