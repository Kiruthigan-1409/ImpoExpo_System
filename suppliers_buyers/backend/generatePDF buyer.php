<?php
    ob_start();
    require './fpdf/fpdf.php';
    require 'fetchbuyer.php';

    $fromDate = $_GET['fromDate'] ?? null;
    $toDate   = $_GET['toDate'] ?? null;
    $entries  = !empty($_GET['entries']) ? intval($_GET['entries']) : 10;
    $selectedCharts = $_GET['buyercharts'] ?? [];

    $buyers = getBuyers($fromDate, $toDate, $entries);
   

    $generatePie = in_array('buyerdis', $selectedCharts);
    $generateBar = in_array('buyercoloumn', $selectedCharts);
    $generateSummary = in_array('b_datasummary', $selectedCharts);

    // Summary calculations
    $totalDistribution = 0;
    $uniqueCities = [];
    $activeBuyers = count($buyers);
    $topBuyer = '';
    $maxdistribution = 0;

    foreach ($buyers as $b) {
        $totalDistribution += $b['total_deliveries'];
        $city = $b['b_city'] ?? 'Unknown';
        if (!in_array($city, $uniqueCities)) $uniqueCities[] = $city;

        if ($b['total_deliveries'] > $maxdistribution) {
            $maxdistribution = $b['total_deliveries'];
            $topBuyer = $b['buyername'];
        }
    }
    $totalCities = count($uniqueCities);

    // chart data
    $pieData = [];
    foreach ($buyers as $b) {
        $city = $b['b_city'] ?? 'Unknown';
        $pieData[$city] = ($pieData[$city] ?? 0) + 1;
    }

    $barData = [];
    foreach ($buyers as $b) {
        $barData[$b['buyername']] = (float)($b['totalRevenue'] ?? 0);
    }


    // HSL → RGB HELPERS 
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



    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();

    // Header
    $pdf->SetFont('Arial','B',18);
    $pdf->Cell(0,10,'MAKGROW IMPEX PVT LTD',0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,7,'Galle Road, Colombo 04',0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,8,'Buyer Summary',0,1,'C');
    $pdf->Ln(3);
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0,6,"From : $fromDate",0,1,'L');
    $pdf->Cell(0,6,"To : $toDate",0,1,'L');
    $pdf->Cell(0,6,"Total Entries: $entries",0,0,'L');
    $pdf->Cell(0,6,'Published : '.date('Y-m-d'),0,1,'R');
    $pdf->Ln(6);

    //---- SUMMARY CARDS--- //
    if ($generateSummary) {
        $cards = [
            ['title'=>'Top Buyer', 'value'=>$topBuyer],
            ['title'=>'Total Distributions', 'value'=>$totalDistribution],
            ['title'=>'Total Cities', 'value'=>$totalCities],
            ['title'=>'Active Buyer Count', 'value'=>$activeBuyers],
        ];

        $cardCount   = 4;
        $pageWidth   = $pdf->GetPageWidth() - 20;
        $cardSpacing = 5;
        $cardWidth   = ($pageWidth - ($cardSpacing * ($cardCount - 1))) / $cardCount;
        $cardHeight  = 20;

        $cardBgColor = [173,216,230];
        $titleColor  = [40,60,120];
        $valueColor  = [0,0,0];

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

        $pdf->SetY($y + $cardHeight + 8);
    }

    //  BUYER TABLE  //
    $colWidths = [
        'Buyer' => 30,
        'City' => 20,
        'Product' => 30,
        'Deliveries' => 20,
        'Email' => 45,
        'Contact' => 20,
        'Total Revenue' => 25
    ];

    $pdf->SetFillColor(0, 51, 102);
    $pdf->SetFont('Arial','B',9);
    $pdf->SetTextColor(255,255,255);
    foreach($colWidths as $k=>$w) $pdf->Cell($w,8,ucfirst($k),1,0,'C',true);
    $pdf->Ln();

    $pdf->SetFillColor(224,235,255);
    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(0,0,0);
    $cellHeight = 6;

    foreach($buyers as $b) {
        if ($pdf->GetY() > 270) { 
            $pdf->AddPage();
            $pdf->SetFillColor(0, 51, 102);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFont('Arial','B',9);
            foreach($colWidths as $k=>$w) $pdf->Cell($w,8,ucfirst($k),1,0,'C',true);
            $pdf->Ln();
            $pdf->SetFillColor(224,235,255);
            $pdf->SetFont('Arial','',8);
            $pdf->SetTextColor(0,0,0);
        }

        $pdf->Cell($colWidths['Buyer'],$cellHeight,$b['buyername'],1,0,'C',true);
        $pdf->Cell($colWidths['City'],$cellHeight,$b['b_city'],1,0,'C',true);
        $pdf->Cell($colWidths['Product'],$cellHeight,$b['product_name'],1,0,'C',true);
        $pdf->Cell($colWidths['Deliveries'],$cellHeight,$b['total_deliveries'],1,0,'C',true);
        $pdf->Cell($colWidths['Email'],$cellHeight,$b['b_email'],1,0,'C',true);
        $pdf->Cell($colWidths['Contact'],$cellHeight,$b['b_contact'],1,0,'C',true);
        $pdf->Cell($colWidths['Total Revenue'],$cellHeight,$b['totalRevenue'],1,0,'C',true);
        $pdf->Ln();
    }


    //Pie chart creation
  function createPieChart($data) {
        global $pdf;
         $chartHeight = 70; 

        if ($pdf->GetY() + $chartHeight > $pdf->GetPageHeight() - 20) {
            $pdf->AddPage();
        }
        $total = array_sum($data);
        if ($total == 0) return;

        $pdf->SetFont('Arial','B',10);
        $pdf->Ln(10);
        $pdf->Cell(0,10,'Buyer Distribution Across Cities',0,1,'C');
        $pdf->Ln(3);

        $x = 60;
        $y = $pdf->GetY();
        $width = 90;
        $height = 10;
        $colors = [
            [255, 99, 132],[54, 162, 235],[255, 206, 86],
            [75, 192, 192],[153, 102, 255],[255, 159, 64],[201,203,207]
        ];

        $i = 0;
        foreach($data as $label=>$value){
            $pdf->SetFillColor(...$colors[$i % count($colors)]);
            $pdf->Rect($x, $y + $i*($height+2), $width*($value/$total), $height, 'F');
            $pdf->SetXY($x + $width*($value/$total) + 2, $y + $i*($height+2));
            $pdf->Cell(0,$height,"$label ($value)",0,1);
            $i++;
        }

        $pdf->Ln($i*($height+2) + 5);
    }


    //bar chart functiion
    function createBarChartRevenuebuyer($filename, $data) {

        global $pdf;
        $chartHeight = 90;

        if ($pdf->GetY() + $chartHeight > $pdf->GetPageHeight() - 20) {
        $pdf->AddPage();
        }

        $chartX = 20;
        $chartY = $pdf->GetY() + 10;
        $chartWidth = 160;
        $chartTopPadding = 10;
        $chartLeftPadding = 20;
        $chartBottomPadding = 20;
        $chartRightPadding = 10;
        $chartBoxX = $chartX + $chartLeftPadding;
        $chartBoxY = $chartY + $chartTopPadding;
        $chartBoxWidth = $chartWidth - $chartLeftPadding - $chartRightPadding;
        $chartBoxHeight = $chartHeight - $chartTopPadding - $chartBottomPadding;
        $barWidth = 20;

        $dataMax = 0;
        foreach ($data as $item) if ($item['value'] > $dataMax) $dataMax = $item['value'];
        if ($dataMax == 0) $dataMax = 1;

        $dataStep = ceil($dataMax / 5);
        $yAxisUnits = $chartBoxHeight / $dataMax;

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0);
        $pdf->Line($chartBoxX, $chartBoxY, $chartBoxX, $chartBoxY + $chartBoxHeight);
        $pdf->Line($chartBoxX, $chartBoxY + $chartBoxHeight, $chartBoxX + $chartBoxWidth, $chartBoxY + $chartBoxHeight);

        for ($i = 0; $i <= $dataMax; $i += $dataStep) {
            $yPos = $chartBoxY + $chartBoxHeight - ($i * $yAxisUnits);
            $pdf->SetXY($chartBoxX - 15, $yPos - 3);
            $pdf->Cell(10, 5, number_format($i), 0, 0, 'R');
            $pdf->Line($chartBoxX - 2, $yPos, $chartBoxX, $yPos);
        }

        $xLabelWidth = $chartBoxWidth / count($data);
        $barXPos = 0;

        foreach ($data as $label => $item) {
              $barHeight = $item['value'] * $yAxisUnits;
                $barX = $chartBoxX + ($barXPos * $xLabelWidth) + (($xLabelWidth - $barWidth) / 2);
                $barY = $chartBoxY + $chartBoxHeight - $barHeight;

            
                $pdf->SetFillColor($item['color'][0], $item['color'][1], $item['color'][2]);
                $pdf->Rect($barX, $barY, $barWidth, $barHeight, 'DF');

             
                $pdf->SetXY($barX, $barY - 5); 
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell($barWidth, 5, number_format($item['value']), 0, 0, 'C');

                
                $pdf->SetXY($barX, $chartBoxY + $chartBoxHeight + 2);
                $pdf->MultiCell($barWidth + 5, 5, $label, 0, 'C');

                $barXPos++;
        }

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY($chartX, $chartY - 5);
        $pdf->Cell(0, 5, 'Revenue (Amount)', 0, 1, 'L');
        $pdf->SetXY($chartBoxX + $chartBoxWidth/2 - 20, $chartBoxY + $chartBoxHeight + 8); 
        $pdf->Cell(40, 5, 'Buyer Name', 0, 0, 'C');
       
    }


    //pie chart insert
    if ($generatePie) {
        $pdf->Ln(5);

        $pieData = [];
        foreach($buyers as $b) {
            $city = $b['b_city'] ?? 'Unknown';
            $pieData[$city] = ($pieData[$city] ?? 0) + 1;
        }
        createPieChart($pieData);
    
    }
    


        //  bar chart insert
    if($generateBar){
        $colors = [
            [255, 99, 132],
            [54, 162, 235],
            [255, 206, 86],
            [75, 192, 192],
            [153, 102, 255]
        ];

        usort($buyers, fn($a, $b) => ($b['totalRevenue'] ?? 0) <=> ($a['totalRevenue'] ?? 0));
        $top5 = array_slice($buyers, 0, 5);

        $chartData = [];
        foreach ($top5 as $i => $buyer) {
            $name = $buyer['buyername'] ?? "Unknown";
            $revenue = (float)($buyer['totalRevenue'] ?? 0);
            $chartData[$name] = [
                'value' => $revenue,
                'color' => $colors[$i % count($colors)]
            ];
        }

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Ln(5);
        $pdf->Cell(0, 10, 'Top 5 Buyers by Revenue', 0, 1, 'C');
        $pdf->Ln(5);
        createBarChartRevenuebuyer("buyer_revenue_chart", $chartData);
  }

    $pdf->Output('I','Buyersummary.pdf');
    exit();
?>
