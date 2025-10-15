<?php
include 'db.php';

// Get month from POST (format: YYYY-MM)
$month = $_POST['month'] ?? date('Y-m');

// Validate format
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    echo json_encode(['success' => false, 'message' => 'Invalid month format']);
    exit;
}

// Calculate start/end dates for the month
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

// Query imports for the selected month (use arrival_date, this matches your reporting needs)
$stmt = $conn->prepare(
    "SELECT i.import_ref, s.suppliername, p.product_name, st.quantity, i.arrival_date
     FROM imports i
     LEFT JOIN supplier s ON i.supplier_id = s.supplier_id
     LEFT JOIN products p ON i.product_id = p.product_id
     LEFT JOIN stock st ON i.stock_id = st.stock_id
     WHERE i.arrival_date BETWEEN ? AND ?
     ORDER BY i.arrival_date ASC"
);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$importsOverTime = [];      // For Line Chart: arrival_date => count
$productCount = [];         // For Pie: product_name => count
$supplierCount = [];        // For Pie: suppliername => count
$quantityByProduct = [];    // For Bar: product_name => total kg
$quantityBySupplier = [];   // For Stacked Bar: supplier => [product => qty]

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;

    $arrival = $row['arrival_date'];
    $prod = $row['product_name'];
    $supp = $row['suppliername'];
    $qty = floatval($row['quantity'] ?? 0);

    // Line Chart
    $importsOverTime[$arrival] = ($importsOverTime[$arrival] ?? 0) + 1;

    // Product Pie
    $productCount[$prod] = ($productCount[$prod] ?? 0) + 1;

    // Supplier Pie
    $supplierCount[$supp] = ($supplierCount[$supp] ?? 0) + 1;

    // Quantity per Product Bar
    $quantityByProduct[$prod] = ($quantityByProduct[$prod] ?? 0) + $qty;

    // Stacked Bar (Product Quantities by Supplier)
    if (!isset($quantityBySupplier[$supp])) $quantityBySupplier[$supp] = [];
    $quantityBySupplier[$supp][$prod] = ($quantityBySupplier[$supp][$prod] ?? 0) + $qty;
}

echo json_encode([
    'success' => true,
    'month' => $month,
    'rows' => $rows,
    'importsOverTime' => $importsOverTime,
    'productCount' => $productCount,
    'supplierCount' => $supplierCount,
    'quantityByProduct' => $quantityByProduct,
    'quantityBySupplier' => $quantityBySupplier
]);
?>