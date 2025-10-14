
<?php

    ob_start();
    require './fpdf/fpdf.php';
    require 'fetchsuppliers.php';

    $fromDate = $_GET['fromDate'] ?? null;
    $toDate   = $_GET['toDate'] ?? null;
    $entries  = !empty($_GET['entries']) ? intval($_GET['entries']) : 10;

    $suppliers = getSuppliers($fromDate, $toDate, $entries);
    $gdAvailable = function_exists('imagecreate');
    $selectedCharts = $_GET['charts'] ?? []; 

    $generatePie = in_array('supplierdis', $selectedCharts);
    $generateBar = in_array('supplier_perf', $selectedCharts);
    $generateSummary = !empty($_GET['charts']) && in_array('s_datasummary', $_GET['charts']);


    $pieData = [];
    foreach ($suppliers as $s) {
        $country = $s['s_country'] ?? 'Unknown';
        if (!isset($pieData[$country])) $pieData[$country] = 0;
        $pieData[$country]++;
    }

    $barData = [];
    foreach ($suppliers as $s) {
        $barData[$s['suppliername']] = $s['total_imports'];
    }


    function createImportBarChart($filename, $data) {
        if(empty($data)) return false;
        $width=600; $height=400;
        $image = imagecreate($width,$height);
        $white=imagecolorallocate($image,255,255,255);
        $black=imagecolorallocate($image,0,0,0);
        $colors=[
            imagecolorallocate($image,102,178,255),
            imagecolorallocate($image,102,255,178),
            imagecolorallocate($image,255,178,102),
            imagecolorallocate($image,255,102,178),
            imagecolorallocate($image,178,102,255),
            imagecolorallocate($image,178,255,102)
        ];
        $barHeight=25; $gap=15; $margin=80;
        $maxValue=max($data);
        $y=50; $i=0;

        foreach($data as $k=>$v){
            $barLength=($width-2*$margin)*($v/$maxValue);
            $color=$colors[$i%count($colors)];
            imagefilledrectangle($image,$margin,$y,$margin+$barLength,$y+$barHeight,$color);
            imagestring($image,3,10,$y+5,substr($k,0,15),$black);
            imagestring($image,3,$margin+$barLength+5,$y+5,$v,$black);
            $y+=$barHeight+$gap;
            $i++;
        }
        imagepng($image,$filename);
        imagedestroy($image);
    }

    function createPieChart($filename, $data)
    {
        $width = 700;
        $height = 500;
        $margin = 40;
        $image = imagecreate($width, $height);

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        // Generate slice colors
        $colors = [];
        $count = count($data);
        $step = 360 / max($count, 1);
        $i = 0;
        foreach ($data as $k => $v) {
            $h = ($i * $step) % 360;
            $colors[$k] = hsl2rgb($h, 0.7, 0.6);
            $i++;
        }

        $center_x = 250; 
        $center_y = $height / 2;
        $radius = 150;
        $start_angle = 0;
        $total = array_sum($data);

        $font_file = __DIR__ . '/fonts/Roboto-Regular.ttf';
        $percentFontSize = 14;
        $legendFontSize = 12;

        // Draw pie slices with percent labels inside
        foreach ($data as $label => $value) {
            $end_angle = $start_angle + ($value / $total) * 360;
            $color = imagecolorallocate($image, $colors[$label][0], $colors[$label][1], $colors[$label][2]);
            imagefilledarc($image, $center_x, $center_y, $radius*2, $radius*2, $start_angle, $end_angle, $color, IMG_ARC_PIE);

            // Percent label
            $mid_angle = deg2rad(($start_angle + $end_angle)/2);
            $percent = round(($value/$total)*100) . '%';
            $text_x = $center_x + cos($mid_angle) * ($radius/1.5);
            $text_y = $center_y + sin($mid_angle) * ($radius/1.5);
            if(file_exists($font_file)){
                imagettftext($image, $percentFontSize, 0, $text_x-10, $text_y, $black, $font_file, $percent);
            } else {
                imagestring($image, 5, $text_x, $text_y, $percent, $black);
            }

            $start_angle = $end_angle;
        }

        // Draw legend on right
        $legendX = 450;
        $legendY = 50;
        $i = 0;
        foreach($data as $label => $value){
            $color = imagecolorallocate($image, $colors[$label][0], $colors[$label][1], $colors[$label][2]);
            imagefilledrectangle($image, $legendX, $legendY + $i*30, $legendX + 20, $legendY + 20 + $i*30, $color);

                $text = "$label ($value)";
            if(file_exists($font_file)){
                imagettftext($image, $legendFontSize, 0, $legendX + 25, $legendY + 17 + $i*30, $black, $font_file, $text);
            } else {
                imagestring($image, 3, $legendX + 25, $legendY + $i*30, $text, $black);
            }
            $i++;
        }

        imagepng($image, $filename);
        imagedestroy($image);
    }



    function hsl2rgb($h,$s,$l){
        $h/=360; $r=$g=$b=0;
        if($s==0){$r=$g=$b=$l;}else{
            $q=$l<0.5?$l*(1+$s):($l+$s-$l*$s);
            $p=2*$l-$q;
            $r=hue2rgb($p,$q,$h+1/3);
            $g=hue2rgb($p,$q,$h);
            $b=hue2rgb($p,$q,$h-1/3);
        }
        return [round($r*255),round($g*255),round($b*255)];
    }
    function hue2rgb($p,$q,$t){if($t<0)$t+=1;if($t>1)$t-=1;if($t<1/6)return $p+($q-$p)*6*$t;if($t<1/2)return $q;if($t<2/3)return $p+($q-$p)*(2/3-$t)*6;return $p;}

    if($gdAvailable){
        if($generatePie) createPieChart('pie_chart.png', $pieData);
        if($generateBar) createImportBarChart('import_chart.png', $barData);

    }


    $pdf=new FPDF('P','mm','A4');
    $pdf->AddPage();


    $pdf->SetFont('Arial','B',18);
    $pdf->Cell(0,10,'MAKGROW IMPEX PVT LTD',0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,7,'Galle Road, Colombo 04',0,1,'C');
    $pdf->Ln(4);


    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,8,'Supplier Summary',0,1,'C');
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,"From : $fromDate",0,1,'L');
    $pdf->Cell(0,6,"To : $toDate",0,1,'L');
    $pdf->Cell(0,6,"Total Entries: $entries",0,0,'L');
    $pdf->Cell(0,6,'Published : '.date('Y-m-d'),0,1,'R');
    $pdf->Ln(4);

    $totalImports = 0;
    $uniqueCountries = [];
    $activeSuppliers = count($suppliers);
    $topSupplier = '';
    $maxImports = 0;

    foreach ($suppliers as $s) {
        $totalImports += $s['total_imports'];

        $country = $s['s_country'] ?? 'Unknown';
        if (!in_array($country, $uniqueCountries)) {
            $uniqueCountries[] = $country;
        }

        if ($s['total_imports'] > $maxImports) {
            $maxImports = $s['total_imports'];
            $topSupplier = $s['suppliername'];
        }
    }

    $totalCountries = count($uniqueCountries);


    $cardCount   = 4;
    $pageWidth   = $pdf->GetPageWidth() - 20;
    $cardSpacing = 5;
    $cardWidth   = ($pageWidth - ($cardSpacing * ($cardCount - 1))) / $cardCount;
    $cardHeight  = 20; 

 if ($generateSummary) {   
    $cards = [
        ['title'=>'Top Supplier', 'value'=>$topSupplier],
        ['title'=>'Total Imports', 'value'=>$totalImports],
        ['title'=>'Total Countries', 'value'=>$totalCountries],
        ['title'=>'Active Supplier Count', 'value'=>$activeSuppliers],
    ];

    $cardBgColor = [173,216,230]; // light blue
    $titleColor  = [40,60,120];   // dark blue
    $valueColor  = [0,0,0];       // black
    $xStart = 10;
    $yStart = $pdf->GetY();

    foreach ($cards as $i => $card) {
        $x = $xStart + $i * ($cardWidth + $cardSpacing);
        $y = $yStart;

    
        $pdf->SetFillColor(...$cardBgColor);
        $pdf->Rect($x, $y, $cardWidth, $cardHeight, 'F');

      
        $pdf->SetFont('Arial','B',10);
        $pdf->SetTextColor(...$titleColor);
        $pdf->SetXY($x, $y);
        $pdf->Cell($cardWidth, $cardHeight/2, $card['title'], 0, 0, 'C');

        
        $pdf->SetFont('Arial','B',12);
        $pdf->SetTextColor(...$valueColor);
        $pdf->SetXY($x, $y + $cardHeight/2);
        $pdf->Cell($cardWidth, $cardHeight/2, $card['value'], 0, 0, 'C');
    }

   
    $pdf->SetY($y + $cardHeight + 5);

 }
  
    $colWidths=['supplier'=>35,'country'=>25,'product'=>37,'imports'=>20,'email'=>50,'contact'=>25];

    $pdf->SetFillColor(0, 51, 102);
    $pdf->SetFont('Arial','B',10);
    $pdf->SetTextColor(255,255,255);
    foreach($colWidths as $k=>$w) $pdf->Cell($w,8,ucfirst($k),1,0,'C',true);
    $pdf->Ln();

    // Data
    $pdf->SetFillColor(224,235,255);
    $pdf->SetFont('Arial','',9);
    $pdf->SetTextColor(0,0,0);
    $cellHeight=6;
    foreach($suppliers as $s){
        $pdf->Cell($colWidths['supplier'],$cellHeight,$s['suppliername'],1,0,'C',true);
        $pdf->Cell($colWidths['country'],$cellHeight,$s['s_country'],1,0,'C',true);
        $pdf->Cell($colWidths['product'],$cellHeight,$s['product_name'],1,0,'C',true);
        $pdf->Cell($colWidths['imports'],$cellHeight,$s['total_imports'],1,0,'C',true);
        $pdf->Cell($colWidths['email'],$cellHeight,$s['s_email'],1,0,'C',true);
        $pdf->Cell($colWidths['contact'],$cellHeight,$s['s_country_code'].' '.$s['s_contact'],1,0,'C',true);
        $pdf->Ln();
    }


        // --- Charts Section ---
        if($gdAvailable){
            
            $currentY = $pdf->GetY();
            if($currentY + 100 > $pdf->GetPageHeight() - 20) $pdf->AddPage();
            $pdf->Ln(5);
            $pdf->SetFont('Arial','B',12);
            $pdf->SetTextColor(40,60,120);
            // Bar Chart

            if($generateBar && file_exists('import_chart.png')){

                $pdf->SetFont('Arial','B',12);
                $pdf->SetTextColor(40,60,120);
                $pdf->Cell(0,8,'Top Suppliers by Imports',0,1,'C');
                $pdf->SetTextColor(0,0,0);
                $xBar = ($pdf->GetPageWidth()-140)/2;
                $pdf->Image('import_chart.png',$xBar,$pdf->GetY()+2,140,75);
                $pdf->SetY($pdf->GetY()+78);
            }
            
            // Pie Chart
            if($generatePie && file_exists('pie_chart.png')){
            
                if($pdf->GetY()+80>$pdf->GetPageHeight()-20) $pdf->AddPage();
                $pdf->SetFont('Arial','B',12);
                $pdf->SetTextColor(40,60,120);
                $pdf->Cell(0,8,'Supplier Distribution by Country',0,1,'C');
                $pdf->SetTextColor(0,0,0);
                $xPie = ($pdf->GetPageWidth()-100)/2;
                $pdf->Image('pie_chart.png',$xPie,$pdf->GetY()+2,100,70);
                $pdf->SetY($pdf->GetY()+75);
            }
            
        }

            $pdf->Output('I','TopSuppliers.pdf');
            exit();
        ?>