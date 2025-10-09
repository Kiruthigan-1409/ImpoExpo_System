<?php
include 'db.php';

// ---- FILTERS ----
$filter_ref = isset($_GET['filter_ref']) ? trim($_GET['filter_ref']) : '';
$filter_product = isset($_GET['filter_product']) ? trim($_GET['filter_product']) : '';
$filter_reason = isset($_GET['filter_reason']) ? trim($_GET['filter_reason']) : '';
$filter_action = isset($_GET['filter_action']) ? trim($_GET['filter_action']) : '';
$filter_date_from = isset($_GET['filter_date_from']) ? trim($_GET['filter_date_from']) : '';
$filter_date_to = isset($_GET['filter_date_to']) ? trim($_GET['filter_date_to']) : '';

// ---- DELIVERY DATA FOR MODAL ----
$delivery_data = [];
$sql = "SELECT 
            d.delivery_code, 
            COALESCE(p.product_name, d.product_name) AS product_name,  
            d.quantity AS delivery_quantity, 
            COALESCE(p.price_per_kg, (SELECT price_per_kg FROM products WHERE product_name = d.product_name LIMIT 1)) AS unit_price
        FROM deliveries d
        LEFT JOIN order_table o ON d.order_no = o.order_id
        LEFT JOIN products p ON o.product_id = p.product_id
        WHERE d.delivery_status = 'Delivered'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $delivery_data[] = $row;
    }
}

// ---- STATS ----
$recoveryRef = "REC-" . date("YmdHis");
$totalRecoveries = $conn->query("SELECT COUNT(*) as count FROM recovery_records")->fetch_assoc()['count'] ?? 0;
$totalItems = $conn->query("SELECT SUM(quantity) as sum FROM recovery_records")->fetch_assoc()['sum'] ?? 0;
$totalImpact = $conn->query("SELECT SUM(financial_impact) as sum FROM recovery_records WHERE LOWER(action_taken) != 'returned'")->fetch_assoc()['sum'] ?? 0;
$returnedToStock = $conn->query("SELECT SUM(quantity) as sum FROM recovery_records WHERE action_taken='Returned'")->fetch_assoc()['sum'] ?? 0;

// ---- FILTERED RECORDS ----
$where = [];
if ($filter_ref !== '') $where[] = "recovery_ref LIKE '%" . $conn->real_escape_string($filter_ref) . "%'";
if ($filter_product !== '') $where[] = "product LIKE '%" . $conn->real_escape_string($filter_product) . "%'";
if ($filter_reason !== '') $where[] = "reason = '" . $conn->real_escape_string($filter_reason) . "'";
if ($filter_action !== '') $where[] = "action_taken = '" . $conn->real_escape_string($filter_action) . "'";
if ($filter_date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_from)) $where[] = "recovery_date >= '" . $conn->real_escape_string($filter_date_from) . "'";
if ($filter_date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_to)) $where[] = "recovery_date <= '" . $conn->real_escape_string($filter_date_to) . "'";
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
$records_sql = "SELECT * FROM recovery_records $where_sql ORDER BY id DESC";
$recordsResult = $conn->query($records_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recovery Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <style>
    .filter-bar { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
    .filter-bar .filter-group { display:flex; flex-direction:column; }
    .filter-bar input[type="text"], .filter-bar select, .filter-bar input[type="date"] { padding:6px 8px; }
    .filter-actions { display:flex; gap:8px; align-items:center; }
  </style>
</head>
<body>
<div class="app-container">
  <?php include '../layout/sidebar.php'; ?>

  <main class="main-content">
    <header class="page-header">
      <div class="header-content">
        <div class="page-title">
          <h1>Recovery Management</h1>
          <p>Track and manage product returns and rejections</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary"><span class="icon"><i class="fa-regular fa-file fa-xl"></i></span>Monthly Reports</button>
          <button class="btn btn-primary" id="openModalBtn"><span class="icon"><i class="fa-solid fa-plus fa-xl"></i></span>New Recovery</button>
        </div>
      </div>
    </header>

    <section class="stats-grid">
      <div class="stat-card blue"><div class="stat-icon"><i class="fas fa-undo"></i></div>
        <div class="stat-content"><h3><?= $totalRecoveries ?></h3><p>Total Recoveries</p></div>
      </div>
      <div class="stat-card purple"><div class="stat-icon"><i class="fas fa-cube"></i></div>
        <div class="stat-content"><h3><?= $totalItems ?></h3><p>Items Recovered</p></div>
      </div>
      <div class="stat-card red"><div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
        <div class="stat-content"><h3>LKR <?= number_format($totalImpact, 2) ?></h3><p>Financial Impact</p></div>
      </div>
      <div class="stat-card green"><div class="stat-icon"><i class="fa-solid fa-check" style="color: #0aab07;"></i></div>
        <div class="stat-content"><h3><?= $returnedToStock ?></h3><p>Returned to Stock</p></div>
      </div>
    </section>

    <section class="filters-section" role="search" aria-label="Filter recovery records">
      <form method="GET" class="filter-bar">
        <div class="filter-dropdowns">
          <div class="filter-group">
            <label for="filter_ref">Reference</label>
            <input type="text" id="filter_ref" name="filter_ref" value="<?= htmlspecialchars($filter_ref) ?>" placeholder="REC-..." class="filter-select">
          </div>
          <div class="filter-group">
            <label for="filter_product">Product</label>
            <input type="text" id="filter_product" name="filter_product" value="<?= htmlspecialchars($filter_product) ?>" placeholder="Product name" class="filter-select">
          </div>
          <div class="filter-group">
            <label for="filter_reason">Reason</label>
            <select id="filter_reason" name="filter_reason" class="filter-select">
              <option value="">-- Any --</option>
              <option value="Quality Issue" <?= $filter_reason === 'Quality Issue' ? 'selected' : '' ?>>Quality Issue</option>
              <option value="Damaged" <?= $filter_reason === 'Damaged' ? 'selected' : '' ?>>Damaged</option>
              <option value="Wrong Item" <?= $filter_reason === 'Wrong Item' ? 'selected' : '' ?>>Wrong Item</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="filter_action">Action</label>
            <select id="filter_action" name="filter_action" class="filter-select">
              <option value="">-- Any --</option>
              <option value="Disposed" <?= $filter_action === 'Disposed' ? 'selected' : '' ?>>Disposed</option>
              <option value="Returned" <?= $filter_action === 'Returned' ? 'selected' : '' ?>>Returned</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="filter_date_from">From</label>
            <input type="date" id="filter_date_from" name="filter_date_from" value="<?= htmlspecialchars($filter_date_from) ?>" class="filter-select">
          </div>
          <div class="filter-group">
            <label for="filter_date_to">To</label>
            <input type="date" id="filter_date_to" name="filter_date_to" value="<?= htmlspecialchars($filter_date_to) ?>" class="filter-select">
          </div>
        </div>
        <div class="filter-actions" style="display:flex; gap:12px; align-items:end;">
          <button type="submit" class="btn btn-primary">Apply Filters</button>
          <a href="index.php" class="btn btn-secondary">Reset</a>
        </div>
      </form>
    </section>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
      <div class="modal">
        <div class="modal-header">
          <h2 id="modalTitle">New Recovery Record</h2>
          <button class="close-btn" id="closeBtn">×</button>
        </div>
        <div class="modal-body">
          <form id="recoveryForm" method="POST" action="save_recovery.php" autocomplete="off">
            <input type="hidden" id="recordId" name="record_id" value="">
            <input type="hidden" id="formMode" name="mode" value="add">
            <div class="form-row">
              <div class="form-group">
                <label for="recoveryRef">Recovery Reference *</label>
                <input type="text" id="recoveryRef" name="recoveryRef" value="<?= $recoveryRef ?>" readonly required>
                <label for="recoveryDate">Recovery Date *</label>
                <input type="date" id="recoveryDate" name="recoveryDate" value="<?= date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label for="originalDelivery">Original Delivery *</label>
              <select id="originalDelivery" name="originalDelivery" required>
                <option value="">Select delivery</option>
                <?php if (empty($delivery_data)): ?>
                  <option value="" disabled>No eligible deliveries found.</option>
                <?php else: ?>
                  <?php foreach ($delivery_data as $d): ?>
                    <option value="<?= htmlspecialchars($d['delivery_code']) ?>"
                            data-product="<?= htmlspecialchars($d['product_name']) ?>"
                            data-quantity="<?= htmlspecialchars($d['delivery_quantity']) ?>"
                            data-unit-price="<?= $d['unit_price'] ?>">
                        <?= htmlspecialchars($d['delivery_code']) ?> - <?= htmlspecialchars($d['product_name']) ?> (Qty: <?= $d['delivery_quantity'] ?>, Unit Price: LKR <?= number_format($d['unit_price'], 2) ?>)
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="product">Product *</label>
                <input type="text" id="product" name="product" readonly required>
              </div>
              <div class="form-group">
                <label for="quantity">Quantity (Kg) *</label>
                <input type="number" id="quantity" name="quantity" value="0" required>
              </div>
              <div class="form-group">
                <label for="financialImpact">Financial Impact (LKR) *</label>
                <input type="number" id="financialImpact" name="financialImpact" value="0" step="0.01" min="0" required readonly>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="reason">Reason *</label>
                <select id="reason" name="reason" required>
                  <option value="">Select reason</option>
                  <option value="Quality Issue">Quality Issue</option>
                  <option value="Damaged">Damaged</option>
                  <option value="Wrong Item">Wrong Item</option>
                </select>
              </div>
              <div class="form-group">
                <label for="itemCondition">Item Condition *</label>
                <select id="itemCondition" name="itemCondition" required>
                  <option value="">Select condition</option>
                  <option value="New">New</option>
                  <option value="Used">Used</option>
                  <option value="Damaged">Damaged</option>
                </select>
              </div>
              <div class="form-group">
                <label for="actionTaken">Action Taken *</label>
                <select id="actionTaken" name="actionTaken" required>
                  <option value="">Select action</option>
                  <option value="Disposed">Disposed</option>
                  <option value="Returned">Returned</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="notes">Notes</label>
              <textarea id="notes" name="notes" placeholder="Details about the recovery..." rows="4" maxlength="500"></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
              <button type="submit" class="save-btn" id="saveBtn">Save Record</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <section class="table-section">
      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Product</th>
              <th>Quantity</th>
              <th>Reason</th>
              <th>Action Taken</th>
              <th>Date</th>
              <th>Financial Impact</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recordsResult && $recordsResult->num_rows > 0): ?>
              <?php while ($row = $recordsResult->fetch_assoc()): 
                $actionClass = strtolower(trim($row['action_taken'])) === 'disposed' ? 'badge-disposed' : 'badge-returned';
              ?>
                <tr data-id="<?= $row['id'] ?>">
                  <td><?= htmlspecialchars($row['recovery_ref']) ?></td>
                  <td>
                    <strong><?= htmlspecialchars($row['product']) ?></strong><br>
                    <small>Delivery: <?= htmlspecialchars($row['original_delivery']) ?></small>
                  </td>
                  <td><?= $row['quantity'] ?></td>
                  <td><?= htmlspecialchars($row['reason']) ?></td>
                  <td><span class="<?= $actionClass ?>"><?= htmlspecialchars($row['action_taken']) ?></span></td>
                  <td><?= $row['recovery_date'] ?></td>
                  <td>LKR <?= number_format($row['financial_impact'], 2) ?></td>
                  <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                  <td>
                      <button class="action-btn edit" onclick="editRecord(<?= $row['id'] ?>)">
                        <i class="fa-regular fa-pen-to-square"></i>
                      </button>
                      <button class="action-btn delete" onclick="deleteRecord(<?= $row['id'] ?>)">
                          <i class="fa-regular fa-trash-can"></i>
                      </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="9">No recovery records found for the selected filters.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>
<script src="script.js"></script>
</body>
</html>
