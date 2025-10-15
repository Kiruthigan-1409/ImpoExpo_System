<?php
require('fpdf/fpdf.php');

include 'db.php';

// --------------------
// PDF Class
// --------------------
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 8, 'Makgrow Impex', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, 'Business Owner: Devakumar Sheron', 0, 1, 'C');
        $this->Ln(5);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
    }
}

// --------------------
// Chart Functions
// --------------------
function createPieChart($data, $labels, $file, $title){
    $size = 400;
    $img = imagecreatetruecolor($size,$size);
    $white = imagecolorallocate($img,255,255,255);
    $black = imagecolorallocate($img,0,0,0);
    imagefill($img,0,0,$white);

    $colors = [
        imagecolorallocate($img,255,99,132),
        imagecolorallocate($img,54,162,235),
        imagecolorallocate($img,75,192,192),
        imagecolorallocate($img,255,206,86),
        imagecolorallocate($img,153,102,255),
    ];

    // Title
    imagestring($img, 5, ($size/2) - strlen($title)*3, 5, $title, $black);

    $total = array_sum($data) ?: 1;
    $cx = $size/2 - 20;
    $cy = $size/2 - 70;
    $r = 70;
    $startAngle = 0;

    $legendX = $cx + $r + 30;
    $legendY = 70;

    foreach ($data as $i => $val){
        $angle = ($val/$total)*360;
        $color = $colors[$i % count($colors)];

        imagefilledarc($img,$cx,$cy,$r*2,$r*2,$startAngle,$startAngle+$angle,$color,IMG_ARC_PIE);

        if($val>0){
            $mid = deg2rad($startAngle + $angle/2);
            $x = $cx + cos($mid)*($r*0.6);
            $y = $cy + sin($mid)*($r*0.6);
            imagestring($img,3,$x-10,$y-7,round(($val/$total)*100)."%",$black);
        }

        $legendItemY = $legendY + $i*20;
        imagefilledrectangle($img,$legendX,$legendItemY,$legendX+12,$legendItemY+12,$color);
        imagestring($img,2,$legendX+16,$legendItemY,$labels[$i]." ($val)",$black);

        $startAngle += $angle;
    }

    imagepng($img,$file);
    imagedestroy($img);
}
function createBarChart($data, $labels, $filename, $title, $xlabel, $ylabel){
    // Chart dimensions
    $width = 1800;
    $height = 1000;
    $margin = 100;
    $image = imagecreatetruecolor($width, $height);

    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $barColor = imagecolorallocate($image, 52, 168, 83);
    $gridColor = imagecolorallocate($image, 220, 220, 220);

    imagefill($image, 0, 0, $white);

    // ✅ Use TTF font (make sure the file path exists)
    $font = __DIR__ . '/Roboto-Regular.ttf'; // you can use any .ttf font file in your project folder

    // Title (large)
    imagettftext($image, 36, 0, ($width/2) - (strlen($title)*9), 60, $black, $font, $title);

    // Chart area
    $chartLeft = $margin + 120;
    $chartTop = 120;
    $chartRight = $width - 150;
    $chartBottom = $height - 200;

    // Axes
    imageline($image, $chartLeft, $chartTop, $chartLeft, $chartBottom, $black);
    imageline($image, $chartLeft, $chartBottom, $chartRight, $chartBottom, $black);

    // Scale
    $maxValue = max($data);
    $numYTicks = 6;
    $yTickStep = $maxValue / $numYTicks;

    // Grid + Y labels (bigger font)
    for($i=0; $i<=$numYTicks; $i++){
        $y = $chartBottom - (($chartBottom - $chartTop) / $numYTicks * $i);
        imageline($image, $chartLeft, $y, $chartRight, $y, $gridColor);

        $labelVal = number_format($yTickStep * $i, 0);
        imagettftext($image, 22, 0, $chartLeft - 120, $y + 8, $black, $font, $labelVal);
    }

    // Bars
    $numBars = count($data);
    $barSpacing = ($chartRight - $chartLeft) / $numBars;
    $barWidth = $barSpacing * 0.6;

    for($i=0; $i<$numBars; $i++){
        $x1 = $chartLeft + ($i * $barSpacing) + ($barSpacing - $barWidth)/2;
        $barHeight = ($data[$i] / $maxValue) * ($chartBottom - $chartTop);
        $y1 = $chartBottom - $barHeight;

        // Bar
        imagefilledrectangle($image, $x1, $y1, $x1 + $barWidth, $chartBottom, $barColor);

        // Value above bar (big bold)
        $valText = "LKR " . number_format($data[$i], 0);
        imagettftext($image, 22, 0, $x1, $y1 - 15, $black, $font, $valText);

        // X labels (horizontal, big)
        imagettftext($image, 22, 0, $x1, $chartBottom + 40, $black, $font, $labels[$i]);
    }

    // Axis labels (large)
    imagettftext($image, 28, 0, ($width/2) - (strlen($xlabel)*9), $height - 120, $black, $font, $xlabel);
    imagettftext($image, 28, 90, 60, ($height/2) + (strlen($ylabel)*9), $black, $font, $ylabel);

    // Save
    imagepng($image, $filename);
    imagedestroy($image);
}


// --------------------
// Handle Form Submit
// --------------------
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $downloadType = $_POST['download_type'] ?? 'pdf';
    $reportType = $_POST['period_mode'] ?? 'lifetime';
    $dataOptions = $_POST['data_options'] ?? [];

    // Fetch rows
    if($reportType==='lifetime'){
        $periodText="Lifetime (All Orders)";
        $stmt=$conn->prepare("SELECT * FROM order_table ORDER BY order_id ASC");
    } elseif($reportType==='month'){
        $month=$_POST['report_month'] ?? date('Y-m');
        $periodText="Month: $month";
        $stmt=$conn->prepare("SELECT * FROM order_table WHERE DATE_FORMAT(order_placed_date,'%Y-%m')=? ORDER BY order_id ASC");
        $stmt->bind_param('s',$month);
    } elseif($reportType==='range'){
        $start=$_POST['start_date']??date('Y-m-d');
        $end=$_POST['end_date']??date('Y-m-d');
        $periodText="Date Range: $start to $end";
        $stmt=$conn->prepare("SELECT * FROM order_table WHERE order_placed_date BETWEEN ? AND ? ORDER BY order_id ASC");
        $stmt->bind_param('ss',$start,$end);
    } else { die("Invalid report type"); }

    $stmt->execute();
    $result=$stmt->get_result();

 $rows=[]; 
$productCount=[]; 
$statusCount=[]; 
$buyerCount=[];
$revenueByProduct=[]; // NEW

while($row=$result->fetch_assoc()){
    $rows[]=$row;

    // Count order status
    $statusCount[$row['status']] = ($statusCount[$row['status']] ?? 0) + 1;

    // Product details
    $pid = $row['product_id'];
    $pRes = $conn->query("SELECT product_name FROM products WHERE product_id=$pid");
    $pName = $pRes->fetch_assoc()['product_name'] ?? "Product $pid";

    // Count and revenue
    $productCount[$pName] = ($productCount[$pName] ?? 0) + 1;
    $revenueByProduct[$pName] = ($revenueByProduct[$pName] ?? 0) + (float)$row['total_price'];

    // Buyer details
    $bid = $row['buyer_id'];
    $bRes = $conn->query("SELECT buyername FROM buyer WHERE buyer_id=$bid");
    $bName = $bRes->fetch_assoc()['buyername'] ?? "Buyer $bid";
    $buyerCount[$bName] = ($buyerCount[$bName] ?? 0) + 1;
}



    // --------------------
    // Excel Output
    // --------------------
    // --------------------
// Excel Output
// --------------------
// --------------------
// Excel Output
// --------------------
// Excel Output
if($downloadType==='excel'){
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=Order_Report.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $output = fopen('php://output','w');

    

// Title row (centered visually)
fputcsv($output, array_merge(array_fill(0, intval(5), ''), ['Makgrow Impex']));
fputcsv($output, array_merge(array_fill(0, intval(4), ''), ['Business Owner: Devakumar Sheron']));
fputcsv($output, array_merge(array_fill(0, intval(4), ''), ["Database Records ($periodText)"]));
fputcsv($output, []); // empty row

    // Column headers (simulate bold by all caps)
    fputcsv($output, ['ORDER ID','PRODUCT ID','PRODUCT NAME','BUYER ID','BUYER NAME','QUANTITY','TOTAL','STATUS','PLACED DATE','ADDRESS','DESCRIPTION']);

    // Data rows
    foreach($rows as $row){
        $pid = $row['product_id'];
        $pRes = $conn->query("SELECT product_name FROM products WHERE product_id=$pid");
        $pName = $pRes->fetch_assoc()['product_name'] ?? "Product $pid";

        $bid = $row['buyer_id'];
        $bRes = $conn->query("SELECT buyername FROM buyer WHERE buyer_id=$bid");
        $bName = $bRes->fetch_assoc()['buyername'] ?? "Buyer $bid";

        fputcsv($output, [
            $row['order_id'],
            $pid,
            $pName,
            $bid,
            $bName,
            $row['quantity'],
            $row['total_price'],
            $row['status'],
            $row['order_placed_date'],
            $row['order_address'],
            $row['description'] ?: 'Not specified'
        ]);
    }

    // Stats only if selected
    if(!empty($dataOptions)){
        fputcsv($output, []);
        fputcsv($output, ['--- Stats ---']);

        if(in_array('popular_product',$dataOptions)){
            fputcsv($output,['Product Sales Distribution']);
            foreach($productCount as $name=>$count) fputcsv($output,[$name,$count]);
        }
        fputcsv($output, []); // empty row

        if(in_array('order_status',$dataOptions)){
            fputcsv($output,['Order Status Breakdown']);
            foreach($statusCount as $status=>$count) fputcsv($output,[$status,$count]);
        }
        fputcsv($output, []); // empty row
        if(in_array('revenue_by_product',$dataOptions)){
            fputcsv($output,['Revenue by Product']);
            foreach($revenueByProduct as $product=>$revenue){
                fputcsv($output, [$product, 'LKR '.number_format($revenue,2)]);
            }
        }
    }

    fclose($output);
    exit;
}



    // --------------------
    // PDF Output
    // --------------------
    if($downloadType==='pdf'){
        // Generate charts only for PDF
        if(in_array('popular_product',$dataOptions) && $productCount) createPieChart(array_values($productCount),array_keys($productCount),'popular.png',"Most Popular Product");
        if(in_array('order_status',$dataOptions) && $statusCount) createPieChart(array_values($statusCount),array_keys($statusCount),'status.png',"Order Status Breakdown");
if(in_array('revenue_by_product',$dataOptions) && $revenueByProduct)
    createBarChart(array_values($revenueByProduct), array_keys($revenueByProduct), 'revenue.png', 'Revenue by Products', 'Products', 'Revenue (LKR)');

        $pdf = new PDF();
        $pdf->SetAutoPageBreak(true,15);
        $pdf->AddPage();

        if(in_array('popular_product',$dataOptions) && $productCount){ $pdf->Image('popular.png',25,40,140,0); $pdf->Ln(85);}
        if(in_array('order_status',$dataOptions) && $statusCount){ $pdf->Image('status.png',25,$pdf->GetY(),140,0); $pdf->Ln(85);}
if(in_array('revenue_by_product',$dataOptions) && $revenueByProduct){ 
    $pdf->Image('revenue.png',20,$pdf->GetY(),170,0); 
    $pdf->Ln(95);
}

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Database Records',0,1,'C');
        $pdf->Ln(3);

        $pdf->SetFont('Arial','',11);
        foreach($rows as $row){
            $pdf->SetFont('Arial','B',12); $pdf->SetFillColor(200,220,255);
            $pdf->Cell(0,8,"Order ID: ".$row['order_id'],0,1,'L',true); $pdf->Ln(2);

            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Product ID:',0,0); $pdf->SetFont('Arial','',10);
            $pdf->Cell(50,6,$row['product_id'],0,0);
            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Buyer ID:',0,0); $pdf->SetFont('Arial','',10);
            $pdf->Cell(50,6,$row['buyer_id'],0,1);

            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Quantity:',0,0); $pdf->SetFont('Arial','',10);
            $pdf->Cell(50,6,$row['quantity'],0,0);
            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Total:',0,0); $pdf->SetFont('Arial','',10);
            $pdf->Cell(50,6,'LKR '.$row['total_price'],0,1);

            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Status:',0,0); $pdf->SetFont('Arial','',10);
            $pdf->Cell(50,6,$row['status'],0,0);
            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Placed:',0,0); $pdf->SetFont('Arial','',10);
            $pdf->Cell(50,6,$row['order_placed_date'],0,1);

            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Address:',0,1); $pdf->SetFont('Arial','',10);
            $pdf->MultiCell(0,6,$row['order_address']);
            $pdf->SetFont('Arial','B',10); $pdf->Cell(30,6,'Description:',0,1); $pdf->SetFont('Arial','',10);
            $pdf->MultiCell(0,6,$row['description']?:'Not specified');
            $pdf->Ln(3);
            $pdf->SetDrawColor(200,200,200); $pdf->Line(10,$pdf->GetY(),200,$pdf->GetY()); $pdf->Ln(3);
        }

        $pdf->Output('I','Order_Report.pdf');
        // Clean up temporary chart images
@unlink('popular.png');
@unlink('status.png');
@unlink('revenue.png');

        exit;
    }
}
?>
