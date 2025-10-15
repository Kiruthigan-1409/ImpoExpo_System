
<?php
include "fetchsuppliers.php";

$fromDate = $_POST['fromDate'] ?? null;
$toDate   = $_POST['toDate'] ?? null;
$entries  = !empty($_POST['entries']) ? intval($_POST['entries']) : 10;

$suppliers = getSuppliers($fromDate, $toDate, $entries);


header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="Supplier summary.csv"');


$output = fopen('php://output', 'w');


fputcsv($output, ['Supplier Name', 'Country', 'Product', 'Total Imports', 'Email', 'Contact']);


foreach ($suppliers as $s) {
    fputcsv($output, [
        $s['suppliername'],
        $s['s_country'],
        $s['product_name'],
        $s['total_imports'],
        $s['s_email'],
        $s['s_country_code'].' '.$s['s_contact']
    ]);
}

fclose($output);
exit;
?>
