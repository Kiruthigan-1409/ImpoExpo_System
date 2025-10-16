<?php
header('Content-Type: application/json');
$conn = new mysqli('142.91.102.107','sysadmin_sliitppa25',':%ngWE6;?*wm$Qy|','sysadmin_sliitppa25');

$sql = "SELECT o.order_id, o.deadline_date, b.buyername, o.status 
        FROM order_table o 
        JOIN buyer b ON o.buyer_id = b.buyer_id
        WHERE o.status IN ('Pending', 'Confirmed')";

$result = $conn->query($sql);
$events = [];

while ($row = $result->fetch_assoc()) {
    $color = $row['status'] === 'Pending' ? '#f59e0b' 
           : ($row['status'] === 'Confirmed' ? '#10b981' : '#6b7280');

    $events[] = [
        'title' => $row['buyername'], // new line between buyer and order id
        'order_id' => $row['order_id'],
        'start' => $row['deadline_date'],
        'color' => $color
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$conn->close();
