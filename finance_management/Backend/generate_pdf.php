<?php
// generate_pdf.php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// adjust memory & time if needed for big images
ini_set('memory_limit', '512M');
set_time_limit(60);

// require FPDF (use your actual folder name)
require __DIR__ . '/fpdf186/fpdf.php';
include "db.php";

// read JSON POST body
$raw = file_get_contents("php://input");
$data = @json_decode($raw, true);
if (!is_array($data)) $data = [];

$period = $data['period'] ?? "all";
$charts = $data['charts'] ?? [];

// Build WHERE
$where = "";
$periodTitle = "Full Report (All Time)";
if ($period === "month") {
    $where = "WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
    $periodTitle = "This Month";
} elseif ($period === "year") {
    $where = "WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())";
    $periodTitle = "This Year";
}

$sql = "SELECT p.*, b.buyername as buyer_name FROM payments p LEFT JOIN buyer b ON p.buyer_id = b.buyer_id $where ORDER BY payment_date DESC";
$result = $conn->query($sql);

// create PDF
$pdf = new FPDF();
$pdf->AddPage();

// Header / Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,"Makgrow Impex - Payment Report",0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,"Period: $periodTitle",0,1,'C');
$pdf->Ln(6);

// Table header
$pdf->SetFont('Arial','B',9);
$w = [35, 30, 35, 25, 25, 30]; // widths for Reference, Date, Buyer, Amount, Method, Status
$pdf->Cell($w[0],8,"Reference",1);
$pdf->Cell($w[1],8,"Date",1);
$pdf->Cell($w[2],8,"Buyer",1);
$pdf->Cell($w[3],8,"Amount",1);
$pdf->Cell($w[4],8,"Method",1);
$pdf->Cell($w[5],8,"Status",1);
$pdf->Ln();

$pdf->SetFont('Arial','',9);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // ensure cell content doesn't overflow too much; you can adjust widths or use MultiCell for long text
        $pdf->Cell($w[0],7,$row['payment_reference'],1);
        $pdf->Cell($w[1],7,$row['payment_date'],1);
        $pdf->Cell($w[2],7,substr($row['buyer_name'] ?? $row['buyer_id'],0,20),1);
        $pdf->Cell($w[3],7,number_format((float)$row['amount'],2),1,0,'R');
        $pdf->Cell($w[4],7,$row['payment_method'],1);
        $pdf->Cell($w[5],7,$row['status'],1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(array_sum($w),8,"No payments found",1,1,'C');
}

// Charts (if provided)
if (!empty($charts) && is_array($charts)) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,"Charts",0,1,'C');
    $pdf->Ln(6);

    foreach ($charts as $type => $img) {
        if (!is_string($img) || trim($img) === "") continue;

        // detect mime and strip prefix
        $matches = [];
        if (preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $img, $matches)) {
            $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        } else {
            // fallback assume png
            $ext = 'png';
        }

        // decode base64 (strip header if exists)
        $imgData = preg_replace('#^data:image/\w+;base64,#i', '', $img);
        $imgData = base64_decode($imgData);
        if ($imgData === false) continue;

        // create temp file with extension so FPDF detects type
        $tmp = tempnam(sys_get_temp_dir(), 'chart_');
        $file = $tmp . '.' . $ext;
        file_put_contents($file, $imgData);

        if (!file_exists($file) || filesize($file) === 0) {
            if (file_exists($file)) unlink($file);
            continue;
        }

        // Insert image (respect page margins)
        $y = $pdf->GetY();
        // If not enough space, add new page
        if ($y + 110 > $pdf->GetPageHeight() - 20) {
            $pdf->AddPage();
            $y = $pdf->GetY();
        }

        $typeParam = (strtoupper($ext) === 'JPG' || strtoupper($ext) === 'JPEG') ? 'JPEG' : 'PNG';
        // center image: set x so image width ~150 fits center
        $x = ($pdf->GetPageWidth() - 150) / 2;
        $pdf->Image($file, $x, $y, 150, 90, $typeParam);
        $pdf->Ln(95);

        // cleanup
        unlink($file);
    }
}

// clear buffers to avoid corrupting the PDF stream
while (ob_get_level()) {
    ob_end_clean();
}

// Output PDF as download (D). FPDF will send headers.
$pdf->Output("D", "Report.pdf");
exit;
