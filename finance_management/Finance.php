<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finance Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="Finance.css">
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
        <?php include '../layout/sidebar.php'; ?>


    <!-- Main Content -->
    <main class="main-content">
      <header class="page-header">
        <div class="header-content">
          <div class="page-title">
            <h1>Finance & Payment Tracking</h1>
            <p>Monitor payments and financial transactions</p>
          </div>
          <div class="header-actions">
            <button class="btn btn-secondary" id="monthlyReportBtn">
              <span class="icon"><i class="fa-regular fa-file fa-xl"></i></span>
              Monthly Reports
            </button>
            <button class="btn btn-primary" id="newPaymentBtn">
              <span class="icon"><i class="fa-solid fa-plus fa-xl"></i></span>
              Record Payment
            </button>
          </div>
        </div>
      </header>

      <!-- Stats Cards -->
      <section class="stats-grid" id="statsGrid">
        <div class="stat-card revenue">
          <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
          <div class="stat-content">
            <h3 id="totalRevenue">LKR 0</h3>
            <p>Total Revenue</p>
            <p><span id="completedCount">0</span> completed payments</p>
          </div>
        </div>
        <div class="stat-card m-revenue">
          <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
          <div class="stat-content">
            <h3 id="monthlyRevenue">0</h3>
            <p>Monthly Revenue</p>
            <p>This month's earnings</p>
          </div>
        </div>
        <div class="stat-card pending">
          <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
          <div class="stat-content">
            <h3 id="pendingRevenue">LKR 0</h3>
            <p>Pending Payments</p>
            <p><span id="pendingCount">0</span> awaiting completion</p>
          </div>
        </div>
        <div class="stat-card failed">
          <div class="stat-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
          <div class="stat-content">
            <h3 id="failedCount">0</h3>
            <p>Failed Payments</p>
            <p>Require attention</p>
          </div>
        </div>
      </section>

      <!-- Filters -->
      <section class="filters-section">
        <div class="search-bar">
          <input type="text" placeholder="Search by reference or buyer..." class="search-input" id="searchInput">
          <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
        </div>
        <div class="filter-dropdowns">
          <div class="filter-group">
            <select class="filter-select" id="statusFilter">
              <option value="">All Status</option>
              <option>Pending</option>
              <option>Completed</option>
              <option>Failed</option>
              <option>Refunded</option>
            </select>
          </div>
          <div class="filter-group">
            <select class="filter-select" id="methodFilter">
              <option value="">All Methods</option>
              <option>Cash</option>
              <option>Bank Transfer</option>
              <option>Cheque</option>
              <option>Credit</option>
            </select>
          </div>
          <div class="filter-group">
            <select class="filter-select" id="buyerFilter">
              <option value="">All Buyers</option>
            </select>
          </div>
          <div class="filter-group">
            <select class="filter-select" id="timeFilter">
              <option value="all">All Time</option>
              <option value="month">This Month</option>
              <option value="year">This Year</option>
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
          <th>Reference</th>
          <th>Buyer</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Payment Date</th>
          <th>Related Delivery</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="paymentsTable">
        <!-- Rows will be injected -->
      </tbody>
    </table>
  </div>
</section>

<!-- Edit Modal (reuses same form as Add) -->
<div id="editPaymentModal" class="modal hidden">
  <div class="modal-content">
    <header class="modal-header">
      <h2><i class="fa-solid fa-pen-to-square"></i> Update Payment</h2>
      <button id="closeEditModal" class="close-btn">&times;</button>
    </header>

    <form id="editPaymentForm">
      <div class="form-grid">
        <label>
          Payment Reference *
          <input type="text" id="edit_payment_reference" name="payment_reference" readonly />
        </label>

        <label>
          Payment Date *
          <input type="date" name="payment_date" id="edit_payment_date" required />
        </label>

        <label>
          Buyer *
          <select name="buyer_id" id="edit_buyer_id" required></select>
        </label>

        <label>
          Related Delivery
          <select name="delivery_id" id="edit_delivery_id"></select>
        </label>

        <label class="amount-label">
          <i class="fa-regular fa-credit-card"></i> Payment Amount:
          <input type="number" name="amount" id="edit_amount" min="0" step="0.01" required />
        </label>

        <label>
          Payment Method *
          <select name="payment_method" id="edit_payment_method" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="credit">Credit</option>
          </select>
        </label>

        <label>
          Status *
          <select name="status" id="edit_status" required>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </label>

        <!-- Hidden extra fields -->
        <label id="editBankRefField" class="hidden">
          Bank Reference Number
          <input type="text" name="bank_reference" id="edit_bank_reference" />
        </label>

        <label id="editChequeRefField" class="hidden">
          Cheque Number
          <input type="text" name="cheque_reference" id="edit_cheque_reference" />
        </label>

        <label class="full-width">
          Payment Notes
          <textarea name="notes" id="edit_notes"></textarea>
        </label>
      </div>

      <footer class="modal-footer">
        <button type="button" id="cancelEditForm" class="btn cancel">Cancel</button>
        <button type="submit" class="btn success"><i class="fa-solid fa-floppy-disk"></i> Update Payment</button>
      </footer>
    </form>
  </div>
</div>

<!-- Report Generation Modal -->
<div id="reportModal" class="modal hidden">
  <div class="modal-content">
    <span id="closeReportModal" class="close">&times;</span>
    <h2>Report Generation</h2>

    <form id="reportForm">
      <!-- Report Period -->
      <label><strong>Report Period</strong></label><br>
      <input type="radio" name="period" value="month" checked> This Month<br>
      <input type="radio" name="period" value="year"> This Year<br>
      <input type="radio" name="period" value="all"> Full Report (All Time)<br><br>

      <!-- Charts -->
      <label><strong>Include Charts</strong></label><br>
      <input type="checkbox" name="charts[]" value="pie"> Piechart (Payment Methods)<br>
      <input type="checkbox" name="charts[]" value="bar"> Barchart (Revenue Trends)<br><br>

      <!-- Buttons -->
      <div class="modal-actions">
        <button type="button" id="cancelReportBtn">Cancel</button>
        <button type="button" id="generateExcelBtn">Generate Excel</button>
        <button type="button" id="generatePdfBtn">Generate PDF</button>
      </div>
    </form>
  </div>
</div>

  <!-- Modal Form -->
<div id="paymentModal" class="modal hidden">
  <div class="modal-content">
    <header class="modal-header">
      <h2><i class="fa-solid fa-sack-dollar"></i> Record New Payment</h2>
      <button id="closeModal" class="close-btn">&times;</button>
    </header>

    <form id="paymentForm">
      <div class="form-grid">
        <label>
          Payment Reference *
          <input type="text" id="payment_reference" name="payment_reference" readonly />
        </label>

        <label>
          Payment Date *
          <input type="date" name="payment_date" id="payment_date" required />
        </label>

        <label>
          <label for="buyer_id">Buyer *</label>
            <select id="buyer_id" name="buyer_id" required>
              <option value="">Select buyer</option>
              <!-- Options will be loaded dynamically via fetch_buyers.php -->
            </select>
        </label>

        <label>
          Related Delivery
          <select name="delivery_id">
            <option value="">Select delivery (optional)</option>
          </select>
        </label>

        <label class="amount-label">
          <i class="fa-regular fa-credit-card"></i> Payment Amount:
          <input type="number" name="amount" min="0" step="0.01" required value="0" />
        </label>

        <label>
          Payment Method *
          <select name="payment_method" id="payment_method" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="credit">Credit</option>
          </select>
        </label>

        <label>
          Status *
          <select name="status" required>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </label>

        <!-- Hidden extra fields -->
        <label id="bankRefField" class="hidden">
          Bank Reference Number
          <input type="text" name="bank_reference" />
        </label>

        <label id="chequeRefField" class="hidden">
          Cheque Number
          <input type="text" name="cheque_reference" />
        </label>

        <label class="full-width">
          Payment Notes
          <textarea name="notes" placeholder="Additional payment details, terms, or conditions..."></textarea>
        </label>
      </div>

      <footer class="modal-footer">
        <button type="button" id="cancelForm" class="btn cancel">Cancel</button>
        <button type="submit" class="btn success"><i class="fa-solid fa-floppy-disk"></i> Record Payment</button>
      </footer>
    </form>
  </div>
</div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script src="Finance.js"></script>
  
</body>
</html>
