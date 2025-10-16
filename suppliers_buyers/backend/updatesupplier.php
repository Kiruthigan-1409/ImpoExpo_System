<?php
header("Content-Type: application/json");
include "connect.php";

// Validate supplier ID
if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "No supplier ID provided."]);
    exit;
}

$id = intval($_GET['id']);


$suppliername = $_POST['suppliername'] ?? '';
$s_company = $_POST['s_company'] ?? '';
$s_country = $_POST['s_country'] ?? '';
$s_city = $_POST['s_city'] ?? '';
$s_email = $_POST['s_email'] ?? '';
$s_country_code = $_POST['s_country_code'] ?? '';
$s_contact = $_POST['s_contact'] ?? '';
$s_productid = $_POST['s_productid'] ?? '';
$s_status = $_POST['s_status'] ?? '';


$sql = "UPDATE supplier 
        SET suppliername = ?, 
            s_company = ?, 
            s_country = ?, 
            s_city = ?, 
            s_email = ?, 
            s_country_code = ?, 
            s_contact = ?, 
            s_productid = ?, 
            s_status = ? 
        WHERE supplier_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssssi",
    $suppliername,
    $s_company,
            $s_country,
            $s_city,
            $s_email,
            $s_country_code,
            $s_contact,
            $s_productid,
            $s_status,
            $id
        );

if ($stmt->execute()) {

 
    $action = "Updated supplier info";
    $changed_by = "Admin"; 

    $logSql = "INSERT INTO supplierhistory (supplier_id, action, changed_by) 
               VALUES (?, ?, ?)";
    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param("iss", $id, $action, $changed_by);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode(["success" => true, "message" => "Supplier updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error updating supplier: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>


