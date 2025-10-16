
<?php
header("Content-Type: application/json");
include "connect.php";

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "No supplier ID provided"]);
    exit;
}

$id = intval($_GET['id']);


$logSql = "INSERT INTO supplierhistory (supplier_id, action, changed_by) 
           VALUES (?, 'Deleted supplier', 'Admin')";
$logStmt = $conn->prepare($logSql);
$logStmt->bind_param("i", $id);
$logStmt->execute();
$logStmt->close();


$sql = "DELETE FROM supplier WHERE supplier_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Supplier deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error deleting supplier: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

