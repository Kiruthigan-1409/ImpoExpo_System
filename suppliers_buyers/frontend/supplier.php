<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>import Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="supplier.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/parsleyjs/src/parsley.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>




  
</head>
<body>
  <div class="app-container">
    <?php include '../../layout/sidebar.php'; ?>
    <main class="main-content">
      <header class="page-header">
        <div class="header-content">
          <div class="page-title">
            <h1>Suppliers & Buyers</h1>
            <p> Manage Supplier Information</p>
          </div>
          <div class="header-actions">
            <button class="btn btn-secondary" id="openReportModal" style="background-color: #635c5c; color: aliceblue;">
              <span class="icon">
                <i class="fa-regular fa-file fa-xl" ></i>
              </span>
              Monthly Reports
            </button>

            
        <button id="viewMapBtn" class="btn btn-primary"><i class="fa-solid fa-earth-americas"></i></button>

          </div>
        </div>

        <div id="mapContainer" style="display: none; margin-top: 20px;">
            <div id="networkMap" style="height: 500px; width: 100%; border-radius: 10px;"></div>
        </div>
      </header>

      
      <section class="stats-grid">
        <div class="stat-card green">
          <div class="stat-icon">
            <i class="fas fa-users" style="color: #1d7057;"></i>
            
          </div>
          <div class="stat-content">
            <h3 class="activesuppliercount"></h3>
            <p> <b>Active suppliers</b></p>
          </div>
        </div>

        <div class="stat-card green">
          <div class="stat-icon">
            <i class="fas fa-users" style="color: #1d7057;"></i>
          </div>
          <div class="stat-content">
            <h3 class="activecountbuyer"></h3>
            <p><b>Active Buyers</b></p>
          </div>
        </div>

        <div class="stat-card red">
          <div class="stat-icon">
            <i class="fa-solid fa-globe" style="color: #581d70;"></i>
          </div>
          <div class="stat-content">
            <h3 class="countrycount"></h3>
            <p><b>Total Countries of Import</b></p>
          </div>
        </div>

        <div class="stat-card yellow">
          <div class="stat-icon">
            <i class="fa-solid fa-van-shuttle" style="color: #c17f2a;"></i>
          </div>
          <div class="stat-content">
            <h3 class="citycount"></h3>
            <p><b>Total Cities of Distribution</b></p>
          </div>
        </div>
      </section>

      <section class="stats-grid">
        <div style="display: flex; gap: 16px;">
          <div >            
            <a href="supplier.php" class="btn btn-secondary"  style="background-color: #342b6a; color: aliceblue; text-decoration: none;">
              <span class="icon">
                <i class="fa-solid fa-globe"></i>
              </span>
              Supplier
            </a>

          </div>
       <div >            
          <a href="buyer.php" class="btn btn-secondary" style="background-color: #342b6a; color: aliceblue; text-decoration: none;">
                <i class="fa-solid fa-users"></i>
              </span>
              Buyer
            </a>
      </div>
      </div>
      <div class="container" >
        <div> 
          <button   class="btn btn-secondary" id="openAddForm" style="background-color: #135226; color: rgb(255, 255, 255);">
               <i class="fa-solid fa-user-plus"></i>
              Add Supplier
          </button>
      </div>
      </div>
        

      </section>
      
      <section class="filters-section">
        <div class="search-bar">
          <input type="text" id="suppliersearch" placeholder= "Search by Supplier name, Email, Contact.." class="search-input">
          <span class="search-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
          </span>
        </div>
        <div class="filter-group filter-country">
              <label>Countries</label>
              <div class="custom-dropdown">
                <div class="dropdown-selected" id="countryFilter">All</div>
                <ul class="dropdown-options">
                 <li>All</li>
                </ul>
              </div>
            </div>
          <div class="filter-group filter-status">
              <label>Status</label>
              <div class="custom-dropdown">
                <div class="dropdown-selected">All</div>
                <ul class="dropdown-options">
                  <li>All</li>
                  <li>Active</li>
                  <li>Inactive</li>
                </ul>
              </div>
            </div>

          <div class="filter-group filter-product">
              <label>Products</label>
              <div class="custom-dropdown">
                <div class="dropdown-selected" id="productFilter">All</div>
                <ul class="dropdown-options">
                  <li>All</li>
                </ul>
              </div>
          </div>
          <div class="filter-group">
                <button class="btn btn-reset" id="resetSuppliers">
                  <i class="fa-solid fa-rotate-right"></i>
                  </button>
          </div>

      </section>

      <!-- Data Table -->
      <section class="table-section">
        <div class="table-container">
          <table id="supplierTable" class="data-table">
            <thead>
              <tr>
                <th>Supplier name</th>
                <th>Company</th>
                <th>Country</th>
                <th>City </th>
                <th>Email</th>
                <th>Contact</th>
                <th>Product</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

<!-- Modal -->
<div id="addSupplierModal" class="modal" data-parsley-validate> 
   <div class="modal-dialog">
    <div class="modal-header">
      <div  class="modal-title">
           <h2 id="modalTitle" >Add Supplier</h2>
      </div>
     
      <div class="modal-close">
        <span id="closeForm" class="close">&times;</span>
      </div>
      
    </div>

    <form id="supplierForm" class="modal-form">
      <div class="form-group">
        <label>Supplier Name</label>
        <input type="text" id="supplierName" name="suppliername" required data-parsley-pattern="^[a-zA-Z\s]+$" data-parsley-error-message="Name must contain only letters">
      </div>

      <div class="form-group">
        <label>Company</label>
        <input type="text" id="companyName" name="s_company" required data-parsley-pattern="^[a-zA-Z\s]+$" data-parsley-error-message="Company name must contain only letters" >
      </div>

     <div class="form-row">
      <div class="form-group small" style="position: relative;">
        <label>Country</label>
        <input type="text" id="countryInput" name="s_country" autocomplete="off" required data-parsley-pattern="^[a-zA-Z\s]+$" data-parsley-error-message="Country name must contain only letters" />
        <ul id="countryList" class="dropdown-list"></ul>
      </div>

      <div class="form-group small">
        <label>City</label>
        <input type="text" id="city" name="s_city" required data-parsley-pattern="^[a-zA-Z\s]+$" data-parsley-error-message="City name must contain only letters">
      </div>
    </div>

  <div class="form-row">
       <div class="form-group small">
        <label>Email</label>
        <input type="email" id="email" name="s_email" required  data-parsley-type="email" data-parsley-error-message="Enter a valid email">
      </div>
     
        <div class="form-group small">
          <div id="contact-error"></div>
          <label>Contact number</label>
          
          <div class="contact-row">
            <input type="text" name="s_country_code" placeholder="+94"  class="country-code" readonly>    
            <input type="text" id="contact" name="s_contact" required class="contact-number" data-parsley-type="digits"  data-parsley-length="[9,15]" 
            data-parsley-error-message="Enter a valid contact number " data-parsley-errors-container="#contact-error">
          </div>
            
        </div>
    </div>

    <div class="form-row">

      <div class="form-group small">
        <label>Product</label>
        <select id="productSelect" name="s_productid" value="--Select --" required data-parsley-error-message="Please select a product">
          <option value="">-- Select --</option>
        </select>
      </div>

      <div class="form-group small">
        <label>Status</label>
        <select id="status" name="s_status" required data-parsley-error-message="Please select a status" >
          <option value="">-- Select --</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>
      </div>


      <div style="display: flex; justify-content: center; margin-top: 20px;">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
  </div>
</div>


<!-- Monthly Report Modal -->
<div id="monthlyReportModal" class="modal">
  <div class="modal-dialog" style="max-width: 400px;" >
    <div class="modal-header">

      <div class="modal-close">
        <span id="closeReportModal" class="close">&times;</span>
      </div>
    </div>

    <div class="modal-form">
      <div class="form-group" style="display:flex; margin:10px 0;">
        <button type="button" id="topSuppliersBtn" class="btn btn-primary" style="justify-content:center; "> <a href="topSupplier.php" style="text-decoration: none; color: #fff;">Supplier Summary</a></button>
      </div>

      <div class="form-group" style="display:flex; margin:10px 0;">
        <button type="button" id="topBuyersBtn" class="btn btn-primary" style="justify-content:center; ">TBuyer summary</button>
      </div>
    </div>
  </div>
</div>





<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="test.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/parsleyjs"></script>

</body>
</html>
