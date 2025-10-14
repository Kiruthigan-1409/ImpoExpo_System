<?php
include 'db.php';

// Get date range from request
$start = $_POST['start_date'] ?? date('Y-m-01');
$end   = $_POST['end_date'] ?? date('Y-m-d');

// Optional: validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    echo json_encode(['success'=>false,'message'=>'Invalid date format']);
    exit;
}

// Fetch orders in range
$stmt = $conn->prepare("SELECT * FROM order_table WHERE order_placed_date BETWEEN ? AND ? ORDER BY order_id ASC");
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$productCount = [];
$statusCount = [];
$revenueByProduct = [];
$buyerCount = [];

while($row = $result->fetch_assoc()){
    $rows[] = $row;

    // Product info
    $pid = $row['product_id'];
    $pRes = $conn->query("SELECT product_name FROM products WHERE product_id=$pid");
    $pName = $pRes->fetch_assoc()['product_name'] ?? "Product $pid";

    $productCount[$pName] = ($productCount[$pName] ?? 0) + 1;
    $revenueByProduct[$pName] = ($revenueByProduct[$pName] ?? 0) + (float)$row['total_price'];

    // Status
    $statusCount[$row['status']] = ($statusCount[$row['status']] ?? 0) + 1;

    // Buyer
    $bid = $row['buyer_id'];
    $bRes = $conn->query("SELECT buyername FROM buyer WHERE buyer_id=$bid");
    $bName = $bRes->fetch_assoc()['buyername'] ?? "Buyer $bid";
    $buyerCount[$bName] = ($buyerCount[$bName] ?? 0) + 1;
}

// Return JSON for frontend
echo json_encode([
    'success' => true,
    'period' => "$start to $end",
    'rows' => $rows,
    'productCount' => $productCount,
    'revenueByProduct' => $revenueByProduct,
    'statusCount' => $statusCount,
    'buyerCount' => $buyerCount
]);
?>
