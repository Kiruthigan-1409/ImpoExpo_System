<?php include '../authentication/auth.php'; ?>
<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Impex - Import Management</title>
  <link rel="stylesheet" href="import.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
  <div class="container">

    <?php include '../layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
      <header class="page-header">
        <div class="header-content">
          <h1>Import Management</h1>
          <p>Track and manage all incoming shipments</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary">
            <span class="icon"><i class="fa-regular fa-file fa-xl"></i></span> Reports
          </button>
          <button id="openModal" class="btn btn-primary">
            <span class="icon"><i class="fa-solid fa-plus fa-xl"></i></span> New Import
          </button>
        </div>
      </header>

      <!-- Stats Cards -->
      <section class="stats-grid">
        <?php
          $total = $conn->query("SELECT COUNT(*) as c FROM imports")->fetch_assoc()['c'];
          $today = date('Y-m-d');
          $arriving = $conn->query("SELECT COUNT(*) as c FROM imports WHERE arrival_date > '$today'")->fetch_assoc()['c'];
          $completed = $conn->query("SELECT COUNT(*) as c FROM imports WHERE arrival_date <= '$today'")->fetch_assoc()['c'];
        ?>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fa-solid fa-dolly" style="color:#0d1ce7;"></i></div>
          <div class="stat-content"><h3><?= $total ?></h3><p>Total Imports</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fa-regular fa-calendar-days" style="color:#6a43df;"></i></div>
          <div class="stat-content"><h3><?= $arriving ?></h3><p>Arriving Soon</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fa-solid fa-check" style="color:#0aab07;"></i></div>
          <div class="stat-content"><h3><?= $completed ?></h3><p>Completed</p></div>
        </div>
      </section>

      <!-- Filters -->
      <section class="filters-section">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input type="text" class="search-input" id="searchInput" placeholder="Search by reference...">
        </div>

        <div class="filter-dropdowns">
          <div class="filter-group">
            <label>Product</label>
            <select class="filter-select" id="productFilter">
              <option value="">All Products</option>
              <?php
                $products = $conn->query("SELECT product_id, product_name FROM products");
                while($p = $products->fetch_assoc()){
                  echo "<option value='{$p['product_name']}'>{$p['product_name']}</option>";
                }
              ?>
            </select>
          </div>

          <div class="filter-group">
            <label>Supplier</label>
            <select class="filter-select" id="supplierFilter">
              <option value="">All Suppliers</option>
              <?php
                $suppliers = $conn->query("SELECT supplier_id, suppliername FROM supplier");
                while($s = $suppliers->fetch_assoc()){
                  echo "<option value='{$s['suppliername']}'>{$s['suppliername']}</option>";
                }
              ?>
            </select>
          </div>

          <div class="filter-group filter-date-group">
            <div class="date-label-refresh">
              <label>Arrival Date</label>
              <button type="button" id="refreshBtn" title="Reset Filters">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            <div class="date-filters">
              <input type="date" id="arrivalDateFrom">
              <input type="date" id="arrivalDateTo">
            </div>
          </div>
        </div>
      </section>


      <!-- Data Table -->
      <section class="table-section">
        <table class="data-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Supplier</th>
              <th>Product</th>
              <th>Quantity (kg)</th>
              <th>Import Date</th>
              <th>Arrival Date</th>
              <th>Expiry</th>
              <th>Remarks</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $sql = "SELECT i.import_id, i.import_ref, s.suppliername AS supplier,
                        p.product_name AS product, st.quantity, i.import_date, 
                        i.arrival_date, st.expiry_date AS expiry, i.remarks
                      FROM imports i
                      LEFT JOIN supplier s ON i.supplier_id = s.supplier_id
                      LEFT JOIN products p ON i.product_id = p.product_id
                      LEFT JOIN stock st ON i.stock_id = st.stock_id
                      ORDER BY i.import_id DESC";

              $res = $conn->query($sql);

              while($row = $res->fetch_assoc()) {
                $arrivalDate = $row['arrival_date'];
                if($arrivalDate <= $today){
                  $dateClass = "date-done";  //green
                } else{
                  $dateClass = "date-pending";  //yellow
                }

                echo "
                  <tr>
                    <td>{$row['import_ref']}</td>
                    <td>{$row['supplier']}</td>
                    <td>{$row['product']}</td>
                    <td>{$row['quantity']}</td>
                    <td>{$row['import_date']}</td>
                    <td><span class='{$dateClass}'>{$row['arrival_date']}</span></td>
                    <td>{$row['expiry']}</td>
                    <td>{$row['remarks']}</td>
                    <td class='actions'>
                      <form method='GET' action='import_management.php' style='display:inline;'>
                        <input type='hidden' name='edit' value='{$row['import_id']}'>
                        <button type='submit' class='action-btn edit' title='Edit'>
                          <i class='fa-solid fa-pen'></i>
                        </button>
                      </form>
                      <form method='POST' action='save_import.php' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this import?\");'>
                        <input type='hidden' name='delete_import' value='{$row['import_id']}'>
                        <button type='submit' class='action-btn delete' title='Delete'>
                          <i class='fa-regular fa-trash-can fa-lg'></i>
                        </button>
                      </form>
                    </td>
                  </tr>";
              }
            ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <!-- Add Import Modal -->
    <div id="importModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Add Import</h2>
      <form id="addImportForm" method="POST" action="save_import.php">
        <?php
        $lastImport = $conn->query("SELECT import_ref FROM imports ORDER BY import_id DESC LIMIT 1")->fetch_assoc();
        $num = $lastImport ? intval(str_replace('IMP-', '', $lastImport['import_ref'])) + 1 : 1;
        $nextImportRef = 'IMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);
        ?>

        <label>Import Reference</label>
        <input type="text" name="import_ref" value="<?= $nextImportRef ?>" readonly>

        <label>Supplier</label>
        <select name="supplier_id" id="supplierSelect" required>
          <option value="">-- Select Supplier --</option>
          <?php
          $suppliers = $conn->query("SELECT supplier_id, suppliername FROM supplier");
          while($s = $suppliers->fetch_assoc()){
            echo "<option value='{$s['supplier_id']}'>{$s['suppliername']}</option>";
          }
          ?>
        </select>

        <label>Product</label>
        <select name="product_id" id="productSelect" required>
          <option value="">-- Select Product --</option>
          <?php
          $products = $conn->query("SELECT p.product_id, p.product_name, p.price_per_kg, s.supplier_id 
                                    FROM products p
                                    LEFT JOIN supplier s ON p.product_id = s.s_productid");
          while($p = $products->fetch_assoc()){
            echo "<option value='{$p['product_id']}' 
                        data-supplier='{$p['supplier_id']}' 
                        data-name='{$p['product_name']}' 
                        data-price='{$p['price_per_kg']}'>
                        {$p['product_name']}
                  </option>";
          }
          ?>
        </select>

        <input type="hidden" name="update_import" id="add_update_import">

        <label>Product Name</label>
        <input type="text" id="productName" name="product_name" readonly>

        <label>Price per Kg</label>
        <input type="text" id="productPrice" name="price_per_kg" readonly>

        <label>Quantity(kg)</label>
        <input type="number" name="quantity" required>

        <label>Import Date</label>
        <input type="date" name="import_date" required>

        <label>Arrival Date</label>
        <input type="date" name="arrival_date" required>

        <label>Expiry</label>
        <input type="date" name="expiry" required>

        <label>Remarks</label>
        <textarea name="remarks"></textarea>

        <div style="display:flex; gap:8px; margin-top:12px;">
          <button type="submit" class="btn btn-primary">Save Import</button>
          <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Import Modal -->
  <div id="editImportModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Edit Import</h2>
      <form id="editImportForm" method="POST" action="save_import.php">
        <input type="hidden" name="id" id="edit_update_import">

        <label>Import Reference</label>
        <input type="text" name="import_ref" id="edit_import_ref" readonly>

        <select id="edit_supplier_id" name="supplier_id" required>
          <option value="">Select Supplier</option>
          <?php
          $suppliers = $conn->query("SELECT supplier_id, suppliername FROM supplier");
          if ($suppliers && $suppliers->num_rows > 0):
              while($s = $suppliers->fetch_assoc()):
          ?>
              <option value="<?= $s['supplier_id'] ?>"><?= $s['suppliername'] ?></option>
          <?php
              endwhile;
          else:
          ?>
              <option value="">No suppliers found</option>
          <?php endif; ?>
        </select>

        <select id="edit_product_id" name="product_id" required>
          <option value="">Select Product</option>
          <?php foreach($products as $product): ?>
            <option value="<?= $product['product_id'] ?>" data-supplier="<?= $product['supplier_id'] ?>">
              <?= $product['product_name'] ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Quantity (kg)</label>
        <input type="number" name="quantity" id="edit_quantity" required>

        <label>Import Date</label>
        <input type="date" name="import_date" id="edit_import_date" required>

        <label>Arrival Date</label>
        <input type="date" name="arrival_date" id="edit_arrival_date" required>

        <label>Expiry</label>
        <input type="date" name="expiry" id="edit_expiry" required>

        <label>Remarks</label>
        <textarea name="remarks" id="edit_remarks"></textarea>

        <div style="display:flex; gap:8px; margin-top:12px;">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" id="cancelEditBtn" class="btn btn-secondary">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Report Modal -->
  <div id="reportModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Generate Import Report</h2>

      <!-- Date Range Filter -->
      <div class="report-filters">
        <label>From:</label>
        <input type="date" id="reportFromDate">
        
        <label>To:</label>
        <input type="date" id="reportToDate">
        
        <button id="applyReportFilter" class="btn btn-primary">Generate Report</button>
      </div>

      <!-- Chart / Diagram Area -->
      <div id="reportCharts" style="margin-top:20px;">
        <!-- Charts will be rendered here -->
        <p style="text-align:center; color:#888;">Select a date range.</p>
      </div>

      <!-- PDF Download Button -->
      <div style="margin-top:20px; text-align:right;">
        <button id="downloadPdfBtn" class="btn btn-secondary">Download PDF</button>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>
