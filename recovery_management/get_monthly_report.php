<?php
include 'db.php';

$month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

// Get daily financial impact for current month
$impact_sql="
    SELECT DATE(recovery_date) as day, SUM(financial_impact) as impact
    FROM recovery_records
    WHERE recovery_date >= ? AND recovery_date <= ?
    GROUP BY day ORDER BY day";
$stmt = $conn->prepare($impact_sql);
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$selected_impact = [];
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $selected_impact[$row['day']] = floatval($row['impact']);

// Previous month daily impact
$prev_month = date('Y-m', strtotime("$month-01 -1 month"));
$prev_start = $prev_month . "-01";
$prev_end = date('Y-m-t', strtotime($prev_start));
$stmt2 = $conn->prepare($impact_sql);
$stmt2->bind_param("ss", $prev_start, $prev_end);
$stmt2->execute();
$prev_impact = [];
$res2 = $stmt2->get_result();
while($row = $res2->fetch_assoc()) $prev_impact[$row['day']] = floatval($row['impact']);

// KPIs
$product_sql = "SELECT product, action_taken, SUM(quantity) as total_quantity, SUM(financial_impact) as total_impact
    FROM recovery_records WHERE recovery_date >= ? AND recovery_date <= ? GROUP BY product, action_taken";
$stmt3 = $conn->prepare($product_sql);
$stmt3->bind_param("ss", $start, $end);
$stmt3->execute();
$res3 = $stmt3->get_result();

$product_data = [];
$returned_sum = 0;
$disposed_sum = 0;
$returned_impact = 0;
$disposed_impact = 0;
while ($row = $res3->fetch_assoc()) {
    if (!isset($product_data[$row['product']])) {
        $product_data[$row['product']] = ['Returned'=>0, 'Disposed'=>0, 'ReturnImpact'=>0, 'DisposeImpact'=>0];
    }
    if (strtolower($row['action_taken']) === 'returned') {
        $product_data[$row['product']]['Returned'] += $row['total_quantity'];
        $product_data[$row['product']]['ReturnImpact'] += $row['total_impact'];
        $returned_sum += $row['total_quantity'];
        $returned_impact += $row['total_impact'];
    }
    if (strtolower($row['action_taken']) === 'disposed') {
        $product_data[$row['product']]['Disposed'] += $row['total_quantity'];
        $product_data[$row['product']]['DisposeImpact'] += $row['total_impact'];
        $disposed_sum += $row['total_quantity'];
        $disposed_impact += $row['total_impact'];
    }
}
$product_breakdown = [];
foreach ($product_data as $product => $row) {
    $product_breakdown[] = [
        'product' => $product,
        'returned' => $row['Returned'],
        'disposed' => $row['Disposed'],
        'return_impact' => $row['ReturnImpact'],
        'dispose_impact' => $row['DisposeImpact']
    ];
}
$sql2 = "SELECT action_taken, SUM(quantity) as total_quantity, SUM(financial_impact) as total_impact
     FROM recovery_records WHERE recovery_date >= ? AND recovery_date <= ? GROUP BY action_taken";
$stmt4 = $conn->prepare($sql2);
$stmt4->bind_param("ss", $start, $end);
$stmt4->execute();
$res4 = $stmt4->get_result();
$action_data = [];
while($row = $res4->fetch_assoc()) {
    $action_data[] = $row;
}
$labels = [];
$max_days = max(date('t', strtotime($prev_start)), date('t', strtotime($start)));
for($i=1;$i<=$max_days;$i++) { $labels[] = str_pad($i,2,"0",STR_PAD_LEFT); }
$final_selected = [];
$final_prev = [];
foreach($labels as $day) {
    $sel_key = "$month-$day";
    $pre_key = "$prev_month-$day";
    $final_selected[] = array_key_exists($sel_key, $selected_impact) ? $selected_impact[$sel_key] : 0;
    $final_prev[] = array_key_exists($pre_key, $prev_impact) ? $prev_impact[$pre_key] : 0;
}
echo json_encode([
    "success" => true,
    "month" => $month,
    "product_breakdown" => $product_breakdown,
    "returned_sum" => $returned_sum,
    "disposed_sum" => $disposed_sum,
    "returned_impact" => $returned_impact,
    "disposed_impact" => $disposed_impact,
    "action_data" => $action_data,
    "impact_labels" => $labels,
    "impact_selected" => $final_selected,
    "impact_prev" => $final_prev,
    "month_name" => date('F Y', strtotime($month.'-01')),
    "prev_month_name" => date('F Y', strtotime($prev_month.'-01'))
]);
?>
