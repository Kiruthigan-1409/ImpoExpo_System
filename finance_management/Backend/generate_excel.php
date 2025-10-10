<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include "db.php";

$period = $_GET['period'] ?? "all";

// Filter query
$where = "";
if ($period === "month") {
    $where = "WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
} elseif ($period === "year") {
    $where = "WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())";
}

$sql = "SELECT * FROM payments $where ORDER BY payment_date DESC";
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$sheet->fromArray(["Reference", "Date", "Buyer", "Amount", "Method", "Status"], NULL, "A1");

// Rows
$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $row['payment_reference'],
        $row['payment_date'],
        $row['buyer_id'],
        $row['amount'],
        $row['payment_method'],
        $row['status']
    ], NULL, "A$rowNum");
    $rowNum++;
}

// Output
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment;filename=Report.xlsx");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
