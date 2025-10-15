<?php
include 'db.php';
$result = $conn->query("SELECT order_id FROM order_table ORDER BY order_placed_date DESC, order_id DESC LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $lastId = $row['order_id']; 
    $num = (int) substr($lastId, 4); 
    $num++;
    $orderID = "ORD-" . str_pad($num, 3, "0", STR_PAD_LEFT); 
} else {
    $orderID = "ORD-001";
}
// -------- Orders Placed (total orders) --------
$sql_total = "SELECT COUNT(*) AS total_orders FROM order_table";
$result_total = $conn->query($sql_total);
$totalOrders = $result_total->fetch_assoc()['total_orders'];

// -------- Pending Orders --------
$sql_pending = "SELECT COUNT(*) AS pending_orders FROM order_table WHERE status='Pending'";
$result_pending = $conn->query($sql_pending);
$pendingOrders = $result_pending->fetch_assoc()['pending_orders'];

// -------- Orders Due Soon (deadline within 7 days & not Done) --------
$sql_due = "SELECT COUNT(*) AS due_soon FROM order_table 
            WHERE status='Pending' 
            AND DATEDIFF(deadline_date, CURDATE()) <= 7
            AND DATEDIFF(deadline_date, CURDATE()) >= 0";
$result_due = $conn->query($sql_due);
$dueSoon = $result_due->fetch_assoc()['due_soon'];

// -------- Completed Orders --------
$sql_completed = "SELECT COUNT(*) AS completed_orders FROM order_table WHERE status='Confirmed' OR status='Done'";
$result_completed = $conn->query($sql_completed);
$completedOrders = $result_completed->fetch_assoc()['completed_orders'];
//==========================================================================================================================
// Fetch distinct months from orders
$monthsResult = $conn->query("
    SELECT DISTINCT DATE_FORMAT(order_placed_date, '%Y-%m') AS month
    FROM order_table
    ORDER BY month ASC
");

$availableMonths = [];
while ($row = $monthsResult->fetch_assoc()) {
    $availableMonths[] = $row['month'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
</head>
<body>
  <div class="app-container">
    <?php include '../layout/sidebar.php'; ?>
    <!-- Main Content -->
    <main class="main-content">
      <header class="page-header">
        <div class="header-content">
          <div class="page-title">
            <h1>Order Management</h1>
            <p>Add and Manage your Order</p>
          </div>
          <div class="header-actions">
            <button id="toggleCalendarBtn" class="btn btn-secondary">
              <i class="fa-regular fa-calendar-days fa-xl"></i> Show Calendar
            </button>
            <button class="btn btn-secondary" onclick="openReportOverlay()">
              <span class="icon">
                <i class="fa-regular fa-file fa-xl"></i>
              </span>
              Monthly Reports
            </button>
           <button class="btn btn-primary" id="placeorderButton">
    <span class="icon">
        <i class="fa-solid fa-plus fa-xl"></i>
    </span>
    Place Order
</button>
          </div>
        </div>
      </header>

      <section class="stats-grid">
    <div class="stat-card total_orders">
        <div class="stat-icon">
            <i class="fas fa-cube"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $totalOrders; ?></h3>
            <p>Total Orders</p>
        </div>
    </div>

    <div class="stat-card pending_orders">
        <div class="stat-icon">
            <i class="fa-solid fa-clipboard-list"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $pendingOrders; ?></h3>
            <p>Pending Orders</p>
        </div>
    </div>

    <div class="stat-card due_soon">
        <div class="stat-icon">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $dueSoon; ?></h3>
            <p>Orders Due Soon</p>
        </div>
    </div>

    <div class="stat-card completed_orders">
        <div class="stat-icon">
            <i class="fa-solid fa-clipboard-check"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $completedOrders; ?></h3>
            <p>Completed Orders</p>
        </div>
    </div>
    </section>
    <div id="successMessage" class="success-message" style="display:none;">
    <p>✅ Order added successfully!</p>
</div>

<!-- Add this near your existing success message -->
<div id="deleteSuccessMessage" class="success-message" style="display:none;">
    <p>✅ Order deleted successfully!</p>
</div>

<div id="deleteErrorMessage" class="error-message" style="display:none;">
    <p>❌ <span id="errorText"></span></p>
</div>
<div id="doneSuccessMessage" class="success-message" style="display:none;">
  ✅ Order marked as done and stocks updated successfully!
</div>

<div id="doneErrorMessage" class="error-message" style="display:none;">
  ❌ <span id="doneErrorText"></span>
</div>

<!-- Filters -->
<section class="filters-section">
  <div class="search-bar">
    <input type="text" placeholder="Search by reference or product..." class="search-input" id="searchInput">
    <span class="search-icon">
      <i class="fa-solid fa-magnifying-glass"></i>
    </span>
  </div>
  <div class="filter-dropdowns">
    <!--reset button -->
    <div class="filter-group">
      <label>&nbsp;</label>
      <button class="reset-filter-btn" id="resetFilterBtn" title="Reset all filters">
        <i class="fa-solid fa-rotate-left"></i> 
      </button>
    </div>
    
    <div class="filter-group">
      <label>Product</label>
      <select class="filter-select" id="productFilter">
        <option value="">All Products</option>
        <?php
        $result = $conn->query("SELECT product_id, product_name FROM products");
        while ($row = $result->fetch_assoc()) {
            echo '<option value="'.$row['product_id'].'">'.$row['product_name'].'</option>';
        }
        ?>
      </select>
    </div>
    
    <div class="filter-group">
      <label>Status</label>
      <select class="filter-select" id="statusFilter">
        <option value="All">All</option>
        <option value="Pending">Pending</option>
        <option value="Confirmed">Confirmed</option>
        <option value="Done">Done</option>
      </select>
    </div>
    
    <div class="filter-group">
      <label>Buyers</label>
      <select class="filter-select" id="buyerFilter">
        <option value="">All Buyers</option>
        <?php
        $result = $conn->query("SELECT buyer_id, buyername FROM buyer WHERE b_status='Active'");
        while ($row = $result->fetch_assoc()) {
            echo '<option value="'.$row['buyer_id'].'">'.$row['buyername'].'</option>';
        }
        ?>
      </select>
    </div>
  </div>
</section>
      
      <!-- Data Table -->
      <section class="table-section">
  <div class="table-container">
    <table class="data-table">
      <thead>
  <tr>
    <th onclick="sortTable(0)">Order ID <i class="fas fa-sort"></i></th>
    <th onclick="sortTable(1)">Product Name <i class="fas fa-sort"></i></th>
    <th onclick="sortTable(2)">Buyer <i class="fas fa-sort"></i></th>
    <th onclick="sortTable(3)">Date <i class="fas fa-sort"></i></th>
    <th onclick="sortTable(4)">Size(kg) <i class="fas fa-sort"></i></th>
    <th onclick="sortTable(5)">Quantity <i class="fas fa-sort"></i></th>
    <th onclick="sortTable(6)">Total price <i class="fas fa-sort"></i></th>
    <th onclick="sortTable(7)">Status <i class="fas fa-sort"></i></th>
    <th>Actions</th>
  </tr>
</thead>
      <tbody>
<?php

$sql = "
SELECT o.*, p.product_name, p.price_per_kg, b.buyername 
FROM order_table o
JOIN products p ON o.product_id = p.product_id
JOIN buyer b ON o.buyer_id = b.buyer_id
ORDER BY 
    CASE o.status
        WHEN 'Pending' THEN 1
        WHEN 'Confirmed' THEN 2
        WHEN 'Done' THEN 3
        ELSE 4
    END,
    -- Deadline for Pending/Confirmed ascending
    CASE WHEN o.status IN ('Pending','Confirmed') THEN o.deadline_date END ASC,
    -- Deadline for Done descending
    CASE WHEN o.status = 'Done' THEN o.deadline_date END DESC;

";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orderId = $row['order_id'];
        $status = $row['status'];

        $statusClass = '';
        if (strtolower($status) === 'pending') {
            $statusClass = 'pending'; // orange
        } elseif (strtolower($status) === 'confirmed') {
            $statusClass = 'confirmed'; // green
        } elseif (strtolower($status) === 'done') {
            $statusClass = 'done'; // black/grey
        }
        $statusBadge = '<span class="status-badge '.$statusClass.'">'.$status.'</span>';

        // -------- Deadline Badge --------
// -------- Deadline Badge --------
$deadline = $row['deadline_date'];
$today = date("Y-m-d");
$daysLeft = ceil((strtotime($deadline) - strtotime($today)) / (60 * 60 * 24)); 
$statusLower = strtolower($status);

if ($statusLower === 'pending' || $statusLower === 'confirmed') {

    // Define color classes differently for each status
    $prefix = ($statusLower === 'confirmed') ? 'confirmed-' : 'deadline-';

    if ($daysLeft > 0) {
        if ($daysLeft <= 2) {
            $deadlineClass = $prefix . "overdue"; // red tone
        } elseif ($daysLeft <= 5) {
            $deadlineClass = $prefix . "soon"; // orange tone
        } else {
            $deadlineClass = $prefix . "safe"; // green tone
        }
        $deadlineBadge = '<span class="deadline-badge '.$deadlineClass.'">'.$deadline.'</span>';
    } else {
        $overdueDays = abs($daysLeft);
        $dueText = ($overdueDays == 0) 
            ? '<span class="due-label"><i>Overdue by :</span> <span class="due-number">Today</i></span>'
            : '<span class="due-label"><i>Overdue by :</span> <span class="due-number">'.$overdueDays.' day'.($overdueDays > 1 ? 's' : '').'</i></span>';

        $deadlineBadge = '
        <div class="past-deadline">
          <strong>'.date("Y-n-j", strtotime($deadline)).'</strong>
          <small class="due-by">'.$dueText.'</small>
        </div>';
    }

} else {
    // Done or cancelled
    $deadlineClass = "deadline-inactive"; // grey
    $deadlineBadge = '<span class="deadline-badge '.$deadlineClass.'">'.$deadline.'</span>';
}
        // -------- Boolean fields (True/False) --------
        $paymentConfirm = $row['payment_confirmation'] ? "Confirmed" : "Not confirmed";
        $deliveryConfirm = $row['delivery_confirmation'] ? "Confirmed" : "Not confirmed";

        // -------- Description (if empty) --------
        $description = !empty(trim($row['description'])) ? $row['description'] : "Not specified";

        echo '
        <tr class="order-row" onclick="toggleDetails(\'order'.$orderId.'\')" 
    data-buyer-id="'.$row['buyer_id'].'" 
    data-product-id="'.$row['product_id'].'">
          <td><strong><i class="fa-solid fa-caret-down"></i> '.$orderId.'</strong></td>
          <td class="product">
            <div class="product-info">
              <strong>'.$row['product_name'].'</strong>
              <small>Price per kg: LKR '.$row['price_per_kg'].'</small>
            </div>
          </td>
          <td><strong>'.$row['buyername'].'</strong></td>
          <td>'.$deadlineBadge.'</td>
          <td><span>'.$row['size'].'</span></td>
          <td><span>'.$row['quantity'].'</span></td>
          <td>LKR '.$row['total_price'].'</td>
          <td>'.$statusBadge.'</td>
          <td class="actions">
    <!-- Show Edit button only if status is not Done -->
    '.($status !== 'Done' ? '
    <button class="action-btn edit" title="Edit" onclick="event.stopPropagation(); editOrder(
        \''.$row['order_id'].'\',
        \''.$row['buyer_id'].'\',
        \''.$row['product_id'].'\',
        \''.$row['size'].'\',
        \''.$row['quantity'].'\',
        \''.$row['total_price'].'\',
        \''.$row['deadline_date'].'\',
        \''.$row['status'].'\',
        \''.$row['payment_confirmation'].'\',
        \''.$row['delivery_confirmation'].'\',
        `'.addslashes($row['order_address']).'`,
        `'.addslashes($row['description']).'`
    )">
        <i class="fa-regular fa-pen-to-square fa-lg"></i>
    </button>' : '').'

    <!-- Delete button always visible -->
    <button class="action-btn delete" title="Delete" onclick="event.stopPropagation(); confirmDelete(
        \''.$row['order_id'].'\',
        \''.$row['product_name'].'\',
        \''.$row['buyername'].'\',
        \''.$row['deadline_date'].'\',
        \''.$row['size'].'\',
        \''.$row['quantity'].'\',
        \''.$row['total_price'].'\'
    )">
        <i class="fa-regular fa-trash-can fa-lg" style="color: #ff0000;"></i>
    </button>

    <!-- Mark as Done button only if not already done -->
    '.($status !== 'Done' ? '
    <form method="POST" action="mark_done.php" style="display:inline;">
        <input type="hidden" name="order_id" value="'.$row['order_id'].'">
        <input type="hidden" name="product_id" value="'.$row['product_id'].'">
        <button type="submit" class="action-btn done" title="Mark as Done">
            <i class="fa-solid fa-check fa-lg" style="color: #009900;"></i>
        </button>
    </form>' : '').'
</td>

</tr>

    <!-- Hidden details row -->
    <tr class="order-details" id="order'.$orderId.'" style="display:none;">
      <td colspan="9">
        <div class="order-details-content">
          <p><strong>Delivery Address:</strong> '.$row['order_address'].'</p>
          <p><strong>Payment Confirmation:</strong> '.$paymentConfirm.'</p>
          <p><strong>Delivery Confirmation:</strong> '.$deliveryConfirm.'</p>
          <p><strong>Order Placed Date:</strong> '.$row['order_placed_date'].'</p>
          <p><strong>Additional Notes:</strong> '.$description.'</p>
        </div>
      </td>
    </tr>';
}

} else {
    echo "<tr><td colspan='9'>No orders found</td></tr>";
}
?>
</tbody>
    </table>
  </div>
</section>
    </main>
  </div>
  <!-- Modal -->
<!-- Report Modal Overlay -->
<div class="modal-overlay" id="reportOverlay" style="display: none;">
  <div class="modal" style="max-width: 650px;">
    
    <!-- Header -->
    <div class="modal-header">
      <h2>Generate Report</h2>
      <button class="close-btn" onclick="closeReportOverlay()">×</button>
    </div>

    <!-- Body -->
    <div class="modal-body">
      <form id="reportForm" method="POST" action="generate_pdf.php" target="_blank">

        <!-- Period Selection -->
        <div class="form-section">
          <h3>Select Report Period</h3>

          <label>
            <input type="radio" name="period_mode" value="range" checked>
            Date Range
          </label>
          <br>
          <label>
            <input type="radio" name="period_mode" value="month">
            Month
          </label>
          <br>
          <label>
            <input type="radio" name="period_mode" value="lifetime">
            Lifetime (All Orders)
          </label>

          <!-- Date Range -->
          <div class="form-row period-range" style="margin-top:10px;">
            <div class="form-group">
              <label for="reportDateFrom">From</label>
              <input type="date" id="reportDateFrom" name="start_date">
            </div>
            <div class="form-group">
              <label for="reportDateTo">To</label>
              <input type="date" id="reportDateTo" name="end_date">
            </div>
          </div>

          <!-- Month Picker -->
          <div class="form-group period-month" style="margin-top:10px;">
            <label for="reportMonth">Select Month</label>
            <select id="reportMonth" name="report_month" required>
    <option value="">-- Select Month --</option>
    <?php foreach($availableMonths as $m): ?>
        <option value="<?= $m ?>"><?= date("F Y", strtotime($m.'-01')) ?></option>
    <?php endforeach; ?>
</select>

          </div>
        </div>

        <!-- Data Options -->
        <div class="form-section">
          <h3>Data Options</h3>
          <label>
    <input type="checkbox" name="data_options[]" value="records" checked>
    Database Records
  </label>
  <br>
  <label>
    <input type="checkbox" name="data_options[]" value="popular_product">
    Product Sales Distribution
  </label>
  <br>
  <label>
    <input type="checkbox" name="data_options[]" value="order_status">
    Order Status Breakdown
  </label>
  <br>
  <label>
    <input type="checkbox" name="data_options[]" value="revenue_by_product">
    Revenue by order Product
  </label>
        </div>
<!-- Footer Note -->
<div class="report-note">
  <strong>Note:</strong> Excel export includes only <em>raw order data and textual statistics</em>, while PDF export can include <em>Order data and charts</em>.
</div>

        <!-- Footer Buttons -->
        <div class="modal-footer">
          <button type="button" class="cancel-btn" onclick="closeReportOverlay()">Cancel</button>
          <button type="submit" name="download_type" value="pdf" class="save-btn">Download as PDF <i class="fa-solid fa-file-pdf"></i></button>
          <button type="submit" name="download_type" value="excel" class="save-btn">Download as Excel <i class="fas fa-file-excel"></i></button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- JS -->
<script>
// Modal open/close
const reportOverlay = document.getElementById('reportOverlay');
function openReportOverlay() { reportOverlay.style.display = 'flex'; }
function closeReportOverlay() { reportOverlay.style.display = 'none'; }

const periodRadios = document.querySelectorAll('input[name="period_mode"]');
const periodRange = document.querySelector('.period-range');
const periodMonthDiv = document.querySelector('.period-month');
const reportMonthSelect = document.getElementById('reportMonth');
const reportDateFrom = document.getElementById('reportDateFrom');
const reportDateTo = document.getElementById('reportDateTo');
const reportForm = document.getElementById('reportForm');

// Toggle fields
function updatePeriodFields() {
    const selected = document.querySelector('input[name="period_mode"]:checked').value;
    if(selected === 'range'){
        periodRange.querySelectorAll('input').forEach(i => i.disabled = false);
        reportMonthSelect.disabled = true;
    } else if(selected === 'month'){
        periodRange.querySelectorAll('input').forEach(i => i.disabled = true);
        reportMonthSelect.disabled = false;
    } else { // Lifetime
        periodRange.querySelectorAll('input').forEach(i => i.disabled = true);
        reportMonthSelect.disabled = true;
    }
}

periodRadios.forEach(r => r.addEventListener('change', updatePeriodFields));
updatePeriodFields(); // initial call


// Validation on submit
reportForm.addEventListener('submit', function(e){
    const selected = document.querySelector('input[name="period_mode"]:checked').value;

    // --- Period validation ---
    if(selected === 'range'){
        if(!reportDateFrom.value || !reportDateTo.value){
            alert('Please select both From and To dates.');
            e.preventDefault();
            return;
        }
    } else if(selected === 'month'){
        if(!reportMonthSelect.value){
            alert('Please select a month.');
            e.preventDefault();
            return;
        }
    }
    // --- Data options validation ---
    const dataOptions = document.querySelectorAll('input[name="data_options[]"]:checked');
    if(dataOptions.length === 0){
        alert('Please select at least one Data Option to generate the report.');
        e.preventDefault();
        return;
    }
    if(selected === 'range'){
        if(!reportDateFrom.value || !reportDateTo.value){
            alert('Please select both From and To dates.');
            e.preventDefault();
            return;
        }
    } else if(selected === 'month'){
        if(!reportMonthSelect.value){
            alert('Please select a month.');
            e.preventDefault();
            return;
        }
    }
    // Lifetime: no validation needed
});
</script>
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <h2>Place Order</h2>
      <button class="close-btn" id="closeBtn">×</button>
    </div>

    <div class="modal-body">
      <form id="orderForm" method="POST" action="add_order.php">
          <input type="hidden" id="isEdit" name="is_edit" value="0">

  <div class="form-row">
    <div class="form-group">
      <label for="orderId">Order ID *</label>
      <input type="text" id="orderId" name="order_id" value="<?php echo $orderID; ?>" readonly>
    </div>

    <div class="form-group">
  <label for="buyer">Buyer *</label>
  <select id="buyer" name="buyer" required>
    <option value="">Select Buyer</option>
    <?php
    $result = $conn->query("SELECT buyer_id, buyername FROM buyer WHERE b_status='Active'");
    while ($row = $result->fetch_assoc()) {
        echo '<option value="'.$row['buyer_id'].'">'.$row['buyername'].'</option>';
    }
    ?>
  </select>
</div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label for="product">Product *</label>
      <select id="product" name="product_id" required>
        <option value="">Select Product</option>
        <?php
        // $result = $conn->query("SELECT product_id, product_name, price_per_kg FROM products WHERE Status='Active'");
        $result = $conn->query("SELECT product_id, product_name, price_per_kg FROM products");
        while ($row = $result->fetch_assoc()) {
            echo '<option value="'.$row['product_id'].'" data-price="'.$row['price_per_kg'].'">'.$row['product_name'].'</option>';
        }
        ?>
      </select>
    </div>
    <div class="form-group">
      <label for="size">Packet Size (kg) *</label>
      <input type="number" id="size" name="size" min="1" required>
    </div>

    <div class="form-group">
      <label for="quantity">Number of Packets *</label>
      <input type="number" id="quantity" name="quantity" min="1" required>
    </div>

    <div class="form-group total_price">
      <label for="total_price">Total Price *</label>
      <input type="number" id="total_price" name="total_price" readonly required>
    </div>
  </div>

  <div class="form-row">
  <div class="form-group">
    <label for="deadline_date">Deadline Date *</label>
    <input type="date" id="deadline_date" name="deadline_date" required min="<?php echo date('Y-m-d'); ?>">
  </div>

  <div class="form-group">
    <label for="status">Status *</label>
    <select id="status" name="status" required>
      <option value="Pending">Pending</option>
      <option value="Confirmed">Confirmed</option>
      <option value="Done">Done</option>
    </select>
  </div>

  <div class="checkbox-group">
    <label for="payment">
      <input type="checkbox" id="payment" name="payment">
      Payment Confirmed
    </label>
  </div>

  <div class="checkbox-group">
    <label for="delivery">
      <input type="checkbox" id="delivery" name="delivery">
      Delivery Confirmed
    </label>
  </div>
</div>


  <div class="form-row">
    <div class="form-group full-width">
      <label for="address">Delivery Address *</label>
      <textarea id="address" name="address" required></textarea>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group full-width">
      <label for="description">Description</label>
      <textarea id="description" name="description"></textarea>
    </div>
  </div>

  <div class="modal-footer">
    <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
    <button type="submit" class="save-btn">Save Order</button>
  </div>
</form>
    </div>
  </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModalOverlay" style="display: none;">
  <div class="modal" style="max-width: 500px;">
    <div class="modal-header">
      <h2>Confirm Deletion</h2>
      <button class="close-btn" onclick="closeDeleteModal()">×</button>
    </div>

    <div class="modal-body">
      <form id="deleteForm" method="POST" action="delete_order.php">
        <input type="hidden" id="deleteOrderIdInput" name="order_id" value="">
        
        <div class="delete-confirmation-content">
          <p>Are you sure you want to delete this order?</p>
          <div class="order-details-preview">
            <p><strong>Order ID:</strong> <span id="deleteOrderId"></span></p>
            <p><strong>Product:</strong> <span id="deleteProductName"></span></p>
            <p><strong>Buyer:</strong> <span id="deleteBuyerName"></span></p>
            <p><strong>Delivery Date:</strong> <span id="deleteDeadline"></span></p>
            <p><strong>Size:</strong> <span id="deleteSize"></span> kg × <span id="deleteQuantity"></span> packets</p>
            <p><strong>Total Price:</strong> LKR <span id="deleteTotalPrice"></span></p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="delete-confirm-btn">Delete Order</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Calendar Overlay -->
<div id="calendarOverlay" class="overlay" style="display:none;">
  <div class="overlay-content">
    <button class="close-btn" id="closeCalendarBtn">×</button>
    <div id="calendar"></div>
  </div>
</div>
<!--Calculating Total price in modal overlay------------------------------------------------>
<script>
// Calculate total price automatically
const productSelect = document.getElementById('product');
const sizeInput = document.getElementById('size'); // packet size in kg
const quantityInput = document.getElementById('quantity'); // number of packets
const totalPriceInput = document.getElementById('total_price');

function calculateTotal() {
  const selectedOption = productSelect.options[productSelect.selectedIndex];
  const price_per_kg = parseFloat(selectedOption.getAttribute('data-price')) || 0;
  const size = parseFloat(sizeInput.value) || 0;
  const quantity = parseFloat(quantityInput.value) || 0;
  totalPriceInput.value = price_per_kg * size * quantity;
}
//---------------------------------------------
productSelect.addEventListener('change', calculateTotal);
sizeInput.addEventListener('input', calculateTotal);
quantityInput.addEventListener('input', calculateTotal);
</script>
<!----------------------------------------------------------------------------------------->
<script>
document.addEventListener('DOMContentLoaded', function() {

const successMessage = document.getElementById('successMessage');

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('added') === '1') {
    successMessage.style.display = 'flex';
    setTimeout(() => { successMessage.style.display = 'none'; }, 4000);
    history.replaceState(null, '', window.location.pathname);
}

});
</script>

<?php
if (isset($_GET['done'])) {
    $msg = $_GET['msg'] ?? '';
    echo '<script>';
    if ($_GET['done'] == '1') {
        if ($msg === 'done_success') {
            echo 'document.getElementById("doneSuccessMessage").style.display = "flex";';
        } elseif ($msg === 'done_with_expired_warning') {
            echo 'document.getElementById("doneSuccessMessage").innerHTML = "✅ Order marked as done! ⚠️ Some expired stocks were ignored.";';
            echo 'document.getElementById("doneSuccessMessage").style.display = "flex";';
        }
    } elseif ($_GET['done'] == '0') {
        if ($msg === 'insufficient_stock') {
            echo 'document.getElementById("doneErrorText").textContent = "❌ Insufficient stock to complete this order!";';
            echo 'document.getElementById("doneErrorMessage").style.display = "flex";';
        }
    }
    echo 'setTimeout(() => {';
    echo 'document.getElementById("doneSuccessMessage").style.display = "none";';
    echo 'document.getElementById("doneErrorMessage").style.display = "none";';
    echo '}, 4000);';
    echo '</script>';
}
?>  
  <?php $conn->close(); ?>
  <script src="script.js" onerror="console.error('Failed to load script.js');"></script>
</body>
</html>
