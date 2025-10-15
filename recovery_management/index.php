<?php include '../authentication/auth.php'; ?>
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
    COALESCE(p.price_per_kg, (SELECT price_per_kg FROM products WHERE product_name = d.product_name LIMIT 1)) AS unit_price,
    d.actual_date
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

// ---- TOP 3 HIGHEST-LOSS RECOVERIES THIS MONTH ----
$top_losses = $conn->query("
    SELECT recovery_ref, product, action_taken, financial_impact, recovery_date, reason, quantity
    FROM recovery_records
    WHERE MONTH(recovery_date) = MONTH(CURDATE()) AND YEAR(recovery_date) = YEAR(CURDATE())
    ORDER BY financial_impact DESC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);


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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    .filter-bar { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
    .filter-bar .filter-group { display:flex; flex-direction:column; }
    .filter-bar input[type="text"], .filter-bar select, .filter-bar input[type="date"] { padding:6px 8px; }
    .filter-actions { display:flex; gap:8px; align-items:center; }
    #reportsOverlay .modal { max-width: 700px; }
    #reportsOverlay { z-index:99999; }
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
          <button id="openTopLossesBtn" style="background:#fc1717b2;border:none;border-radius:8px;padding:7px 16px;font-weight:bold;box-shadow:0 2px 5px #fc1717b2;cursor:pointer;">
              💸 Show Top Losses (This Month)
          </button>
          <button class="btn btn-secondary" id="openReportsBtn">
            <span class="icon"><i class="fa-regular fa-file fa-xl"></i></span>Monthly Reports
          </button>
          <button class="btn btn-primary" id="openModalBtn"><span class="icon"><i class="fa-solid fa-plus fa-xl"></i></span>New Recovery</button>
        </div>
      </div>
    </header>

    <section class="stats-grid">
      <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-undo"></i></div>
        <div class="stat-content"><h3><?= $totalRecoveries ?></h3><p>Total Recoveries</p></div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-cube"></i></div>
        <div class="stat-content"><h3><?= $totalItems ?></h3><p>Items Recovered</p></div>
      </div>
      <div class="stat-card red">
        <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
        <div class="stat-content"><h3>LKR <?= number_format($totalImpact, 2) ?></h3><p>Financial Impact</p></div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon"><i class="fa-solid fa-check" style="color: #0aab07;"></i></div>
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
                            data-unit-price="<?= $d['unit_price'] ?>"
                            data-delivery-date="<?= htmlspecialchars($d['actual_date']) ?>">
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

     <!-- Monthly Reports Overlay -->
    <!-- Monthly Reports Overlay -->
    <div class="modal-overlay" id="reportsOverlay" style="display:none;">
      <div class="modal" style="max-width:700px">
        <div class="modal-header">
          <h2>Monthly Recovery Report</h2>
          <button class="close-btn" id="closeReportsBtn">&times;</button>
        </div>
        <div class="modal-body">
          <form id="reportForm" style="margin-bottom:16px; display:flex; gap:6px; align-items:center;">
            <label for="monthInput"><b>Month:</b></label>
            <input type="month" id="monthInput" name="monthInput" max="<?= date('Y-m') ?>" value="<?= date('Y-m') ?>" required>
            <button type="submit" class="btn btn-primary">Generate</button>
          </form>
          <button id="downloadPdfBtn" class="btn btn-secondary" style="margin-bottom:10px; float:right;">
            Download as PDF
          </button>
          <div id="pdfReportContent">
            <!-- Chart + Table inserted by JS -->
            <!-- Ensure IDs and explicit size! -->
            <h3 style="margin-bottom:0.3em; color:#204289;">Monthly Financial Impact Trend</h3>
            <canvas id="monthlyTrendBar" width="600" height="200"></canvas>
            <div style="display: flex; gap:28px; flex-wrap:wrap; margin-bottom:26px">
              <!-- Mini stat cards, can be injected here via JS if needed -->
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 32px;">
              <div>
                <canvas id="actionPie" width="250" height="250"></canvas>
                <div style="text-align:center;font-weight:bold;color:#444">Returned vs Disposed (Qty)</div>
              </div>
              <div>
                <canvas id="impactBar" width="250" height="250"></canvas>
                <div style="text-align:center;font-weight:bold;color:#444">Financial Impact by Action</div>
              </div>
              <div>
                <canvas id="prodBar" width="250" height="250"></canvas>
                <div style="text-align:center;font-weight:bold;color:#444">Product Return/Disposal Split</div>
              </div>
            </div>
            <h3 style="margin-top:2em;margin-bottom:.5em; color:#204289;">Detailed Product-wise Flow</h3>
            <table class="data-table" style="width:100%;margin-bottom:1em;">
              <thead>
                <tr>
                  <th>Product</th>
                  <th style="color:#288943;">Returned Qty</th>
                  <th style="color:#c62c2e;">Disposed Qty</th>
                  <th style="color:#288943;">Returned Value</th>
                  <th style="color:#c62c2e;">Disposed Value</th>
                </tr>
              </thead>
              <tbody id="productBreakdownBody"></tbody>
            </table>
            <div style="color:#888;padding-bottom:10px;text-align:right;"><small>Note: Red numbers = business loss; Green = cost recovery.</small></div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Top losses -->
 
<div id="topLossesModal" class="modal-overlay" style="display:none;">
  <div class="modal" style="max-width:440px;background:#f5b9b9ff;border:1px solid #e69595ff;box-shadow:0 2px 12px #ee8282cc;">
    <div class="modal-header" style="border-bottom:1px solid #fd8a8aff;">
      <span style="font-weight:bold;font-size:1.1em;color:#b7791f">💵
      Top 3 Highest-Loss (this month)</span>
      <button id="closeTopLossesBtn" style="background:none;border:none;font-size:1.7em;float:right;color:#f5b9b9ff;cursor:pointer;margin-left:auto;">&times;</button>
    </div>
    <div class="modal-body" style="padding:18px 14px 14px 14px;">
      <ol style="margin:0;padding-left:1.15em;">
        <?php foreach($top_losses as $i => $loss): ?>
          <li style="margin-bottom:12px;">
              <span style="font-weight:bold;"><?= htmlspecialchars($loss['product']) ?></span>
              <span style="color:#b7791f;font-size:0.97em;">(<?= htmlspecialchars($loss['action_taken']) ?>, <?= htmlspecialchars($loss['reason']) ?>, Qty: <?= $loss['quantity'] ?>)</span>
              <span style="float:right; color:#e53e3e;font-weight:bold;">
                  LKR <?= number_format($loss['financial_impact'],2) ?>
                  <?php if($i == 0): ?> <?php elseif($i == 1): ?> <?php elseif($i == 2): ?> <?php endif; ?>
              </span><br>
              <span style="font-size:0.92em;color:#666;">On <?= date('M d', strtotime($loss['recovery_date'])) ?> (Ref: <?= htmlspecialchars($loss['recovery_ref']) ?>)</span>
          </li>
        <?php endforeach; ?>
        <?php if (empty($top_losses)): ?>
            <li style="color:#aaa;">No recovery records this month.</li>
        <?php endif; ?>
      </ol>
    </div>
  </div>
</div>

<script src="script.js"></script>
</body>
</html>
