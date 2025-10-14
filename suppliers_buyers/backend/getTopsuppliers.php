
<?php
include "fetchSuppliers.php";

$fromDate = $_POST['fromDate'] ?? null;
$toDate   = $_POST['toDate'] ?? null;
$entries  = !empty($_POST['entries']) ? intval($_POST['entries']) : 10;

$suppliers = getSuppliers($fromDate, $toDate, $entries);

$publishedDate = date("Y-m-d");

echo json_encode($suppliers); 
?>
