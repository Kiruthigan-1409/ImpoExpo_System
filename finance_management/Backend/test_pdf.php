<?php
require('fpdf186/fpdf.php');

ob_start();
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40, 10, 'Hello PDF Works!');
ob_clean();
header('Content-Type: application/pdf');
$pdf->Output("I", "test.pdf");
exit;
