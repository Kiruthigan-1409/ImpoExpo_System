<?php include '../authentication/auth.php'; ?>
<?php
include "db.php";

// Stat numbers
$total_products = $conn->query("SELECT COUNT(*) AS cnt FROM products")->fetch_assoc()['cnt'];
$active_imports = $conn->query("SELECT COUNT(*) AS cnt FROM imports WHERE arrival_date >= CURDATE()")->fetch_assoc()['cnt'];
$pending_deliveries = $conn->query("SELECT COUNT(*) AS cnt FROM deliveries WHERE delivery_status='In-transit'")->fetch_assoc()['cnt'];
$monthly_revenue = $conn->query("SELECT SUM(amount) AS revenue FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetch_assoc()['revenue'] ?? 0;

// Improved LOW STOCK: sum quantity per product, filter by threshold (<=25)
// Get threshold from SESSION or use default 25
$low_stock_threshold = isset($_SESSION['low_stock_threshold']) ? intval($_SESSION['low_stock_threshold']) : 25;

$low_stock = $conn->query(
    "SELECT p.product_name, SUM(s.quantity) AS quantity
     FROM products p
     JOIN stock s ON p.product_id = s.product_id
     GROUP BY p.product_id
     HAVING SUM(s.quantity) <= " . intval($low_stock_threshold)
)->fetch_all(MYSQLI_ASSOC);
$low_stock_count = count($low_stock);

$activities = [];

// Payments
foreach ($conn->query("
    SELECT 'Payment' AS type, p.amount, b.buyername, p.payment_date, p.status
    FROM payments p
    JOIN buyer b ON p.buyer_id = b.buyer_id
    ORDER BY p.payment_date DESC LIMIT 30
")->fetch_all(MYSQLI_ASSOC) as $p) {
    $activities[] = [
        'type' => 'Payment',
        'desc' => "Payment of LKR " . number_format($p['amount']) . " received from " . htmlspecialchars($p['buyername']),
        'date' => date('Y-m-d H:i:s', strtotime($p['payment_date'])),
        'status' => $p['status']
    ];
}

// Shipments (delivery)
foreach ($conn->query("
    SELECT 'Shipment' AS type, delivery_code, delivery_status, scheduled_date
    FROM deliveries
    ORDER BY scheduled_date DESC LIMIT 30
")->fetch_all(MYSQLI_ASSOC) as $d) {
    $activities[] = [
        'type' => 'Shipment',
        'desc' => "Delivery " . $d['delivery_code'] . " (" . $d['delivery_status'] . ") scheduled",
        'date' => date('Y-m-d H:i:s', strtotime($d['scheduled_date'])),
        'status' => $d['delivery_status']
    ];
}

// Import Activities (uses created_at for activity date)
foreach ($conn->query("
    SELECT 'Import' AS type, import_ref, suppliername, product_name, import_date, arrival_date, created_at
    FROM imports
    ORDER BY import_date DESC LIMIT 30
")->fetch_all(MYSQLI_ASSOC) as $i) {
    $activities[] = [
        'type' => 'Import',
        'desc' => "Import " . $i['import_ref'] . ": " . $i['product_name'] . " from " . $i['suppliername'] . ", Arrives: " . date('M d', strtotime($i['arrival_date'])),
        'date' => $i['created_at'], // uses created_at as activity date
        'status' => 'Recorded'
    ];
}


// Recovery
foreach ($conn->query("
    SELECT 'Recovery' AS type, recovery_ref, reason, recovery_date, product, quantity, action_taken, item_condition, created_at
    FROM recovery_records
    ORDER BY recovery_date DESC LIMIT 30
")->fetch_all(MYSQLI_ASSOC) as $r) {
    $activities[] = [
        'type' => 'Recovery',
        'desc' => "Recovery " . $r['recovery_ref'] . " (" . $r['product'] . "): " . $r['reason'] . " – Qty: " . $r['quantity'],
        'date' => $r['created_at'],
        'status' => $r['action_taken'] . " / " . $r['item_condition']
    ];
}

// Defensive fallback: ensure every entry has 'date'
foreach ($activities as &$a) {
    if (!isset($a['date']) || empty($a['date'])) {
        $a['date'] = '1970-01-01 00:00:00';
    }
}
unset($a);

// Sort, newest to oldest, show latest 10 only
usort($activities, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
$activities = array_slice($activities, 0, 9);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="dashboard-container">
    <?php include '../layout/sidebar.php'; ?>
    <div class="main-content">
        <header class="header">
            <div class="header-content">
                <h1>Business Overview</h1>
                <p>Monitor your import and distribution operations</p>
            </div>
            <div class="header-time">
                <?php
                $now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
                echo $now->format("M d, Y \\a\\t h:i A");
                ?>
            </div>
        </header>
        <section class="stats-row">
            <div class="stat-card blue">
                <span class="stat-label">Total Products</span>
                <div class="stat-card-bottom">
                    <strong><?= $total_products ?></strong>
                    <span class="stat-icon"><i class="fas fa-cube"></i></span>
                </div>
            </div>
            <div class="stat-card green">
                <span class="stat-label">Active Imports</span>
                <div class="stat-card-bottom">
                    <strong><?= $active_imports ?></strong>
                    <span class="stat-icon"><i class="fas fa-cart-shopping"></i></span>
                </div>
            </div>
            <div class="stat-card orange">
                <span class="stat-label">Pending Deliveries</span>
                <div class="stat-card-bottom">
                    <strong><?= $pending_deliveries ?></strong>
                    <span class="stat-icon"><i class="fas fa-truck"></i></span>
                </div>
            </div>
            <div class="stat-card purple">
                <span class="stat-label">Monthly Revenue</span>
                <div class="stat-card-bottom">
                    <strong>LKR <?= number_format($monthly_revenue) ?></strong>
                    <span class="stat-icon"><i class="fas fa-dollar-sign"></i></span>
                </div>
            </div>
            <div class="stat-card red">
                <span class="stat-label">Low Stock Alerts</span>
                <div class="stat-card-bottom">
                    <strong><?= $low_stock_count ?></strong>
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                </div>
            </div>
        </section>
        <section class="main-cards-row">
            <div class="card activities-card">
                <div class="card-head">
                    <i class="fas fa-clock"></i> Recent Activities
                </div>
                <div class="activities-list">
                    <?php foreach($activities as $a): ?>
                        <div class="activity-row">
                            <span class="activity-icon <?php echo strtolower($a['type']); ?>">
                                <i class="fas fa-<?php echo ($a['type']=="Payment"?"dollar-sign":"truck"); ?>"></i>
                            </span>
                            <div>
                                <div class="activity-title"><?= htmlspecialchars($a['desc']) ?></div>
                                <div class="activity-date"><?= $a['date'] ?></div>
                            </div>
                            <span class="activity-status <?= strtolower($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card low-stock-card">
                <div class="card-head red">
                    <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
                    <span style="font-size:13px;color:#d32f2f;">
                        (Threshold: <?= htmlspecialchars($low_stock_threshold) ?> kg)
                    </span>
                </div>
                <div class="alerts-list">
                    <?php if ($low_stock_count === 0): ?>
                        <div class="alert-row">
                            <span>
                                <div class="alert-title">No low stock items</div>
                                <div class="alert-desc">All stocks are above threshold</div>
                            </span>
                            <span class="alert-badge">0</span>
                        </div>
                    <?php else: ?>
                        <?php foreach($low_stock as $s): ?>
                            <div class="alert-row">
                                <span>
                                    <div class="alert-title"><?= htmlspecialchars($s['product_name']) ?></div>
                                    <div class="alert-desc">Current: <?= $s['quantity'] ?> kg</div>
                                </span>
                                <span class="alert-badge"><?= $s['quantity'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>
</body>
</html>
