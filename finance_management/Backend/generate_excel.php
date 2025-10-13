<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

include "db.php";

$period = $_GET['period'] ?? "all";

// Filter query
$where = "";
if ($period === "month") {
    $where = "WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
} elseif ($period === "year") {
    $where = "WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())";
}

$sql = "
    SELECT 
        p.*, 
        b.buyername AS buyer_name
    FROM payments p
    LEFT JOIN buyer b ON p.buyer_id = b.buyer_id
    $where
    ORDER BY payment_date DESC
";
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set sheet title
$sheet->setTitle('Payment Report');

// Header row
$headers = ["Reference", "Date", "Buyer (ID - Name)", "Method", "Status", "Amount (LKR)"];
$sheet->fromArray($headers, NULL, "A1");

// Style header row
$headerStyle = $sheet->getStyle("A1:F1");
$headerStyle->getFont()->setBold(true)->setSize(12);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

$rowNum = 2;
$totalAmount = 0;

// Add payment data
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $buyer = $row['buyer_id'] . " - " . ($row['buyer_name'] ?? "N/A");
        $amount = (float)$row['amount'];
        $totalAmount += $amount;

        $sheet->fromArray([
            $row['payment_reference'],
            $row['payment_date'],
            $buyer,
            $row['payment_method'],
            $row['status'],
            number_format($amount, 2)
        ], NULL, "A$rowNum");

        $rowNum++;
    }

    // Add total amount row
    $sheet->setCellValue("A$rowNum", "Total");
    $sheet->mergeCells("A$rowNum:E$rowNum");
    $sheet->setCellValue("F$rowNum", number_format($totalAmount, 2) . " LKR");

    // Style total row
    $totalStyle = $sheet->getStyle("A$rowNum:F$rowNum");
    $totalStyle->getFont()->setBold(true);
    $totalStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA');
    $totalStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

} else {
    $sheet->setCellValue("A2", "No payments found");
    $sheet->mergeCells("A2:F2");
    $sheet->getStyle("A2:F2")->getAlignment()->setHorizontal('center');
}

// Auto-adjust column widths
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Borders for data
$dataStyle = $sheet->getStyle("A1:F" . ($rowNum - 1));
$dataStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Output
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment;filename=Finance-Report.xlsx");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
