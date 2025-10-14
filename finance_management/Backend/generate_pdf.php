<?php
// generate_pdf.php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// increase resources for images
ini_set('memory_limit', '512M');
set_time_limit(90);

// require FPDF (adjust path if needed)
require __DIR__ . '/fpdf186/fpdf.php';
include "db.php";

// read JSON POST body
$raw = file_get_contents("php://input");
$data = @json_decode($raw, true);
if (!is_array($data)) $data = [];

$period = $data['period'] ?? "all";
$charts = $data['charts'] ?? [];

// build WHERE condition & period title
$where = "";
$periodTitle = "Full Report (All Time)";
if ($period === "month") {
    $where = "WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
    $periodTitle = "This Month";
} elseif ($period === "year") {
    $where = "WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())";
    $periodTitle = "This Year";
}

// fetch payments joined with buyer name
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

// prepare PDF
$pdf = new FPDF('P','mm','A4');
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

// Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,"Makgrow Impex - Payment Report",0,1,'C');
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Period: $periodTitle",0,1,'C');
$pdf->Ln(4);

// If no data, still show message and charts if any
$totalAmount = 0.0;
$categoryTotals = [
    'completed' => 0.0,
    'pending' => 0.0,
    'failed' => 0.0,
    'refunded' => 0.0
];

$rows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // normalize
        $amount = (float)$row['amount'];
        $status = strtolower(trim($row['status'] ?? 'unknown'));
        if (isset($categoryTotals[$status])) {
            $categoryTotals[$status] += $amount;
        }
        $totalAmount += $amount;

        $rows[] = $row;
    }
}

// ---- Summary Cards (category totals + percentages) ----
$cardW = ($pdf->GetPageWidth() - 32) / 4; // 4 cards with 8mm side paddings total
$startX = 16;
$yBefore = $pdf->GetY();
$cardH = 24;

$colors = [
    'completed' => [218, 255, 226], // pale green
    'pending'   => [255, 249, 216], // pale yellow
    'failed'    => [255, 230, 230], // pale red
    'refunded'  => [241, 236, 255]  // pale purple
];

$labelColors = [
    'completed' => [34,139,34],
    'pending'   => [204,102,0],
    'failed'    => [220,20,60],
    'refunded'  => [102,51,153]
];

$pdf->SetFont('Arial','B',10);

$i = 0;
foreach ($categoryTotals as $k => $amt) {
    $x = $startX + $i * ($cardW + 4);
    $y = $yBefore;

    // background rectangle
    $c = $colors[$k];
    $pdf->SetFillColor($c[0], $c[1], $c[2]);
    $pdf->SetDrawColor(220,220,220);
    $pdf->Rect($x, $y, $cardW, $cardH, 'F');

    // inner padding
    $pdf->SetXY($x + 4, $y + 4);
    $pdf->SetFont('Arial','B',12);
    $displayAmt = number_format($amt, 2);
    $pdf->SetTextColor($labelColors[$k][0], $labelColors[$k][1], $labelColors[$k][2]);
    $pdf->Cell($cardW - 8, 6, $displayAmt . " LKR", 0, 2);

    $pdf->SetFont('Arial','',9);
    $pdf->SetTextColor(60,60,60);
    $pct = ($totalAmount > 0) ? round(($amt / $totalAmount)*100, 2) : 0;
    $label = ucfirst($k);
    $pdf->Cell($cardW - 8, 6, "$label " . chr(149) . " $pct%" , 0, 2);

    $i++;
}

// move down after cards
$pdf->Ln($cardH + 4);

// ---- Data Table ----
// Table header
$pdf->SetFont('Arial','B',9);
$w = [32, 26, 48, 30, 28, 26]; // Reference, Date, Buyer (ID-Name), Method, Status, Amount
$leftMargin = 12;
$pdf->SetX($leftMargin);
$pdf->SetFillColor(235, 240, 250);
$pdf->SetDrawColor(200,200,200);

$headers = ["Reference","Date","Buyer (ID - Name)","Method","Status","Amount (LKR)"];
for ($ci=0; $ci < count($headers); $ci++) {
    $pdf->Cell($w[$ci], 8, $headers[$ci], 1, 0, 'L', true);
}
$pdf->Ln();

// Table rows
$pdf->SetFont('Arial','',9);
if (count($rows) === 0) {
    $pdf->Cell(array_sum($w), 10, "No payments found for the selected period.", 1, 1, 'C');
} else {
    foreach ($rows as $r) {
        // check page break
        if ($pdf->GetY() > $pdf->GetPageHeight() - 40) {
            $pdf->AddPage();
        }

        $buyerText = ($r['buyer_id'] ?? '') . " - " . ($r['buyer_name'] ?? "N/A");
        $pdf->SetX($leftMargin);
        $pdf->Cell($w[0],7, $r['payment_reference'],1);
        $pdf->Cell($w[1],7, $r['payment_date'],1);
        $pdf->Cell($w[2],7, substr($buyerText,0,40),1);
        $pdf->Cell($w[3],7, ucfirst($r['payment_method']),1);
        $pdf->Cell($w[4],7, ucfirst($r['status']),1);
        $pdf->Cell($w[5],7, number_format((float)$r['amount'],2),1,0,'R');
        $pdf->Ln();
    }

    // Add blank line
    $pdf->Ln(4);

    // Category totals table (similar to excel)
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,6,"Category Totals",0,1,'L');
    $pdf->Ln(2);

    $labelW = 70;
    $valW = 40;
    $pctW = 30;
    $x0 = $leftMargin;

    $pdf->SetFont('Arial','',10);
    foreach ($categoryTotals as $status => $amt) {
        $pct = ($totalAmount > 0) ? ($amt / $totalAmount) * 100 : 0;
        $pdf->SetX($x0);
        $pdf->Cell($labelW,7, ucfirst($status) . " Total",1);
        $pdf->Cell($valW,7, number_format($amt,2) . " LKR",1,0,'R');
        $pdf->Cell($pctW,7, number_format($pct,2) . " %",1,0,'R');
        $pdf->Ln();
    }

    // Grand total (keeps your original format)
    $pdf->Ln(4);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,6,"Grand Total",0,1,'L');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,8, "Total amount: " . number_format($totalAmount,2) . " LKR",1,1,'R');
}

// ---- Charts Section (front-end provides base64 images) ----
if (!empty($charts) && is_array($charts)) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,"Charts & Visuals",0,1,'C');
    $pdf->Ln(4);

    // Small legend-like summary above charts (re-using category totals)
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,"Quick Summary",0,1,'L');
    $pdf->Ln(2);
    $pdf->SetFont('Arial','',10);
    foreach ($categoryTotals as $st => $amt) {
        $pct = ($totalAmount > 0) ? round(($amt/$totalAmount)*100,2) : 0;
        $pdf->Cell(60,6, ucfirst($st) . ": " . number_format($amt,2) . " LKR",0,0);
        $pdf->Cell(40,6, "($pct%)",0,1);
    }

    $pdf->Ln(6);

    // Insert each chart with a title. charts keys may be 'pie' or 'bar' per frontend.
    foreach ($charts as $type => $img) {
        if (!is_string($img) || trim($img) === "") continue;

        // Title for chart
        $chartTitle = ucfirst($type) . " Chart";
        if ($type === 'pie') $chartTitle = "Payment Methods (Pie)";
        if ($type === 'bar') $chartTitle = "Revenue Trend (Bar)";

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7, $chartTitle, 0, 1, 'L');
        $pdf->Ln(2);

        // decode base64 and write temp file
        $matches = [];
        if (preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $img, $matches)) {
            $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        } else {
            $ext = 'png';
        }

        $imgData = preg_replace('#^data:image/\w+;base64,#i', '', $img);
        $imgData = base64_decode($imgData);
        if ($imgData === false) continue;

        $tmp = tempnam(sys_get_temp_dir(), 'chart_');
        $file = $tmp . '.' . $ext;
        file_put_contents($file, $imgData);

        if (!file_exists($file) || filesize($file) === 0) {
            if (file_exists($file)) unlink($file);
            continue;
        }

        // If not enough space on page, add new page
        $y = $pdf->GetY();
        if ($y + 110 > $pdf->GetPageHeight() - 20) {
            $pdf->AddPage();
        }

        // center and insert image with a fixed width (preserve aspect)
        $imageWidth = 150; // mm
        $xPos = ($pdf->GetPageWidth() - $imageWidth) / 2;
        $pdf->Image($file, $xPos, $pdf->GetY(), $imageWidth);
        $pdf->Ln(95);

        // cleanup temp file
        unlink($file);
    }
}

// clear output buffers (to avoid corrupting PDF)
while (ob_get_level()) {
    ob_end_clean();
}

// Output PDF for download
$pdf->Output("D", "Finance-Report.pdf");
exit;
?>
