<?php
require('fpdf/fpdf.php');

// --------------------
// DB Connection
// --------------------
define('DB_SERVER', '142.91.102.107');
define('DB_USER', 'sysadmin_sliitppa25');
define('DB_PASS', ':%ngWE6;?*wm$Qy|');
define('DB_NAME', 'sysadmin_sliitppa25');

$conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

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

function createBarChart($data, $labels, $file, $title, $xTitle='X-axis', $yTitle='Y-axis'){
    $w=400; $h=400;
    $img=imagecreatetruecolor($w,$h);
    $white=imagecolorallocate($img,255,255,255);
    $black=imagecolorallocate($img,0,0,0);
    $blue=imagecolorallocate($img,54,162,235);
    imagefill($img,0,0,$white);

    imagestring($img,5,($w/2)-strlen($title)*2.5,8,$title,$black);

    $marginLeft=40; $marginBottom=35; $marginTop=25; $marginRight=15;
    $plotW=$w-$marginLeft-$marginRight; $plotH=$h-$marginTop-$marginBottom;

    $max=max($data) ?: 1;
    imageline($img,$marginLeft,$marginTop,$marginLeft,$h-$marginBottom,$black);
    imageline($img,$marginLeft,$h-$marginBottom,$w-$marginRight,$h-$marginBottom,$black);

    $barCount=count($data);
    $barWidth=$plotW/($barCount*1.5);
    $gap=$barWidth/2;
    $x=$marginLeft+$gap;

    foreach($data as $i=>$val){
        $bh=($val/$max)*$plotH;
        $y1=$h-$marginBottom;
        $y2=$y1-$bh;
        imagefilledrectangle($img,$x,$y2,$x+$barWidth,$y1,$blue);

        $percent=round(($val/$max)*100).'%';
        imagestring($img,2,$x+($barWidth/2)-7,$y2-10,$percent,$black);
        imagestringup($img,2,$x+($barWidth/2)-4,$h-5,$labels[$i],$black);

        $x+=$barWidth+$gap;
    }

    imagestring($img,3,($w/2)-strlen($xTitle)*2.5,$h-20,$xTitle,$black);
    imagestringup($img,3,8,$marginTop+$plotH/2,$yTitle,$black);

    imagepng($img,$file);
    imagedestroy($img);
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

    $rows=[]; $productCount=[]; $statusCount=[]; $buyerCount=[];
    while($row=$result->fetch_assoc()){
        $rows[]=$row;
        $statusCount[$row['status']] = ($statusCount[$row['status']]??0)+1;

        $pid = $row['product_id'];
        $pRes = $conn->query("SELECT product_name FROM products WHERE product_id=$pid");
        $pName = $pRes->fetch_assoc()['product_name'] ?? "Product $pid";
        $productCount[$pName] = ($productCount[$pName]??0)+1;

        $bid = $row['buyer_id'];
        $bRes = $conn->query("SELECT buyername FROM buyer WHERE buyer_id=$bid");
        $bName = $bRes->fetch_assoc()['buyername'] ?? "Buyer $bid";
        $buyerCount[$bName] = ($buyerCount[$bName]??0)+1;
    }

    // --------------------
    // Excel Output
    // --------------------
    if($downloadType==='excel'){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=Order_Report.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $output = fopen('php://output','w');
        fputcsv($output, [$periodText]);
        fputcsv($output, []);
        fputcsv($output, ['Order ID','Product ID','Product Name','Buyer ID','Buyer Name','Quantity','Total','Status','Placed Date','Address','Description']);

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
                fputcsv($output,['Most Popular Product']);
                foreach($productCount as $name=>$count) fputcsv($output,[$name,$count]);
            }
            if(in_array('order_status',$dataOptions)){
                fputcsv($output,['Order Status Breakdown']);
                foreach($statusCount as $status=>$count) fputcsv($output,[$status,$count]);
            }
            if(in_array('buyer_volume',$dataOptions)){
                fputcsv($output,['Order Volume by Buyers']);
                foreach($buyerCount as $buyer=>$count) fputcsv($output,[$buyer,$count]);
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
        if(in_array('buyer_volume',$dataOptions) && $buyerCount) createBarChart(array_values($buyerCount),array_keys($buyerCount),'buyers.png',"Order Volume by Buyers","Buyers","% of Orders");

        $pdf = new PDF();
        $pdf->SetAutoPageBreak(true,15);
        $pdf->AddPage();

        if(in_array('popular_product',$dataOptions) && $productCount){ $pdf->Image('popular.png',25,40,140,0); $pdf->Ln(85);}
        if(in_array('order_status',$dataOptions) && $statusCount){ $pdf->Image('status.png',25,$pdf->GetY(),140,0); $pdf->Ln(85);}
        if(in_array('buyer_volume',$dataOptions) && $buyerCount){ $pdf->Image('buyers.png',15,$pdf->GetY(),90,0); $pdf->Ln(90);}

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
        exit;
    }
}
?>
