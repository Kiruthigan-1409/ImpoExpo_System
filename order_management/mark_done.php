<?php
// Database connection
define('DB_SERVER', '142.91.102.107');
define('DB_USER', 'sysadmin_sliitppa25');
define('DB_PASS', ':%ngWE6;?*wm$Qy|');
define('DB_NAME', 'sysadmin_sliitppa25');

$conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

$order_id = $_POST['order_id'];
$product_id = $_POST['product_id'];

// Get order details
$orderResult = $conn->query("SELECT * FROM order_table WHERE order_id='$order_id'");
if ($orderResult->num_rows == 0) {
    header("Location: index.php?done=0&msg=insufficient_stock");
    exit;
}
$order = $orderResult->fetch_assoc();
$required_qty = $order['size'] * $order['quantity']; // total kg

$today = date('Y-m-d');

// Get all stock for the product, ordered by stock_id (oldest first)
$stockResult = $conn->query("
    SELECT * FROM stock 
    WHERE product_id='$product_id'
    ORDER BY stock_id ASC
");

if ($stockResult->num_rows == 0) {
    header("Location: index.php?done=0&msg=insufficient_stock");
    exit;
}

$remaining_needed = $required_qty;
$expired_exists = false;
$stocks_to_deduct = [];

// Loop through stock to check expiry and calculate deduction
while ($stock = $stockResult->fetch_assoc()) {
    $stock_id = $stock['stock_id'];
    $available_qty = $stock['quantity'];
    $expiry = $stock['expiry_date'];

    if ($expiry < $today) {
        $expired_exists = true; // expired stock exists
        continue; // skip expired stock
    }

    if ($available_qty >= $remaining_needed) {
        $stocks_to_deduct[$stock_id] = $remaining_needed;
        $remaining_needed = 0;
        break;
    } else {
        $stocks_to_deduct[$stock_id] = $available_qty;
        $remaining_needed -= $available_qty;
    }
}

if ($remaining_needed > 0) {
    // Not enough stock
    header("Location: index.php?done=0&msg=insufficient_stock");
    exit;
}

// Deduct stock and delete if quantity hits 0
foreach ($stocks_to_deduct as $stock_id => $deduct_qty) {
    $conn->query("UPDATE stock SET quantity = quantity - $deduct_qty WHERE stock_id = $stock_id");
    $conn->query("DELETE FROM stock WHERE stock_id = $stock_id AND quantity <= 0");
}

// Mark order as done and set confirmations
$conn->query("
    UPDATE order_table
    SET status='Done', payment_confirmation=1, delivery_confirmation=1
    WHERE order_id='$order_id'
");

// Redirect back with proper popup message
// Example: after checking stock deduction logic
if ($insufficient_stock) {
    header('Location: index.php?done=0&msg=insufficient_stock');
    exit();
}

if ($expired_exists) {
    // Deduction successful but some expired stocks ignored
    header('Location: index.php?done=1&msg=done_with_expired_warning');
    exit();
} else {
    // Deduction fully successful
    header('Location: index.php?done=1&msg=done_success');
    exit();
}

exit;
?>
