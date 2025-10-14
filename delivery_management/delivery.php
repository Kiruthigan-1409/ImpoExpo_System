<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Delivery Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="styles_2.css">
</head>

<body>
  <div class="app-container">
    <!-- Sidebar -->
    <?php include '../layout/sidebar.php'; ?>

    <!-- Main -->
    <main class="main-content">
      <div class="page-header">
        <div class="page-title"><i class="fa-solid fa-truck-fast"></i><span>Delivery Management</span></div>
        <div class="toolbar">
          <button class="btn btn-secondary" id="exportExcel"><i class="fa-solid fa-file-excel"></i> Export
            Excel</button>
          <button class="btn btn-secondary" id="exportPdfBtn"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
          <button class="btn btn-primary" id="newDelivery"><i class="fa-solid fa-plus"></i> New Delivery</button>
        </div>
      </div>

      <!-- Filters -->
      <section class="filters-section">
        <div class="filter-row">
          <div class="filter-group search-wrap">
            <label>Search</label>
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="search-input" id="searchInput"
              placeholder="Search buyer, product, ref, driver...">
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select id="statusFilter">
              <option value="">All</option>
              <option value="Pending">Pending</option>
              <option value="In Transit">In Transit</option>
              <option value="Delivered">Delivered</option>
              <option value="Failed">Failed</option>
              <option value="Overdue">Overdue</option>
              <option value="Returned">Returned</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Driver</label>
            <select id="driverFilter">
              <option value="">All</option>
            </select>
          </div>
          <div class="filter-group"><label>Scheduled From</label><input type="date" id="fromDate"></div>
          <div class="filter-group"><label>Scheduled To</label><input type="date" id="toDate"></div>
        </div>
      </section>

      <!-- Stats -->
      <section class="stats-grid" id="statsGrid"></section>

      <!-- DataTable controls -->
      <div class="dt-controls">
        <div class="dt-left">
          <label for="rowsPerPage" class="dt-info">Rows per page</label>
          <select id="rowsPerPage" class="dt-select">
            <option>10</option>
            <option selected>25</option>
            <option>50</option>
            <option>100</option>
          </select>
        </div>
        <div class="dt-right">
          <div id="dtInfo" class="dt-info">Showing 0–0 of 0</div>
          <div id="dtPagination" class="dt-pagination"></div>
        </div>
      </div>

      <!-- Delivery List -->
      <section class="delivery-list">
        <table class="delivery-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Buyer</th>
              <th>Product</th>
              <th>Quantity</th>
              <th>Scheduled Date</th>
              <th>Actual Date</th>
              <th>Driver</th>
              <th>Address</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="deliveryTableBody"></tbody>
        </table>
      </section>
    </main>
  </div>

  <!-- Modal -->
  <div class="modal-backdrop" id="modalBackdrop">
    <div class="modal">

      <header>
        <h3 id="modalTitle">New Delivery</h3>
        <button class="btn btn-ghost" id="closeModal"><i class="fa-solid fa-xmark"></i> Close</button>
      </header>

      <form id="deliveryForm">
        <div class="grid">
          <div class="filter-group">
            <label>Order No</label>
            <select id="orderNo" name="orderNo" required>
              <option value="">Select Order</option>
            </select>
          </div>
          
          <div class="filter-group"><label>Buyer Name</label><input type="text" id="buyerName" name="buyerName" required></div>
          <div class="filter-group"><label>City</label><input type="text" id="city" name="city" required></div>
          <div class="filter-group"><label>Address</label><input type="text" id="address" name="address" required></div>
          <div class="filter-group"><label>Product Name</label><input type="text" id="productName" name="productName" required></div>
          <div class="filter-group"><label>Quantity</label><input type="number" id="quantity" name="quantity" required> </div>
          <div class="filter-group"><label>Driver</label><input type="text" id="driver" name="driver" required pattern="[a-zA-Z]*" title="Only letters are allowed"></div>

          <div class="form-group">
            <label for="deliveryStatus">Status</label>
            <select id="deliveryStatus" name="deliveryStatus" required>
              <option value="Pending" selected>Pending</option>
              <option value="In-transit">In Transit</option>
              <option value="Delivered">Delivered</option>
              <option value="Returned">Returned</option>
            </select>
          </div>

          <div class="filter-group"><label>Scheduled Date</label><input type="date" id="scheduledDate" name="scheduledDate" required></div>
          <div class="filter-group"><label>Actual Date</label><input type="date" id="actualDate" name="actualDate"> </div>
        </div>
        
        <div class="footer">
          <button type="button" class="btn btn-secondary" id="resetForm"><i class="fa-solid fa-rotate"></i>Reset</button>
          <button type="button" id="saveDelivery" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i>Save</button>
        </div>

        <input type="hidden" id="editIndex" value="-1">
      </form>
    </div>
  </div>

  <div class="toast" id="toast">Saved</div>

  <!-- Hidden canvases for charts -->
  <div id="chartArea" style="position:absolute; left:-9999px; top:-9999px;">
    <canvas id="statusChart" width="1000" height="450"></canvas>
    <canvas id="dailyChart" width="1000" height="450"></canvas>
    <canvas id="driverQtyChart" width="1000" height="450"></canvas>
    <!-- NEW -->
    <canvas id="statusPieChart" width="1000" height="450"></canvas>
  </div>


  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>



  <script src="delivery_api.js"></script>
  <script src="export_handler.js"></script>
  <script src="table_filters.js"></script>

</body>

</html>