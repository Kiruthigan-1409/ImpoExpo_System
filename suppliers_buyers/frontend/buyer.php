
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>import Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="supplier.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/parsleyjs/src/parsley.css">
</head>
<body>
  <div class="app-container">
    <?php include '../../layout/sidebar.php'; ?>
    <!-- Main Content -->
    <main class="main-content">
      <header class="page-header">
        <div class="header-content">
          <div class="page-title">
            <h1>Suppliers & Buyers</h1>
            <p> Manage Buyer Information</p>
          </div>
          <div class="header-actions">
            <button class="btn btn-secondary" style="background-color: #635c5c; color: aliceblue;" id="buyerReport">
              <span class="icon">
                <i class="fa-regular fa-file fa-xl" ></i>
              </span>
              Monthly Reports
            </button>
          </div>
        </div>
      </header>

      <!-- Stats Cards -->
      <section class="stats-grid">
        <div class="stat-card green">
          <div class="stat-icon">
            <i class="fas fa-users" style="color: #1d7057;"></i>
            
          </div>
          <div class="stat-content">
            <h3 class="activesuppliercount" ></h3>
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
            <h3 class="countrycount" ></h3>
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
            <a href="supplier.php" class="btn btn-secondary" style="background-color: #342b6a; color: aliceblue; text-decoration: none;">
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
          <button id="openbuyerform" class="btn btn-secondary" style="background-color: #135226; color: rgb(255, 255, 255);">
               <i class="fa-solid fa-user-plus"></i>
              </span>
              Add Buyer
            </button>
         
          
      </div>
      </div>
        

      </section>      

      <section class="filters-section">
        <div class="search-bar">
          <input type="text" id="buyersearch" placeholder="Search by Buyer name, Email or contact.. " class="search-input">
          <span class="search-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
          </span>
        </div>
          <div class="filter-group filter-city" >
              <label>City</label>
              <div class="custom-dropdown">
                <div class="dropdown-selected">All</div>
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
                <div class="dropdown-selected">All</div>
                <ul class="dropdown-options">
                  <li>All</li>
                </ul>
              </div>
            </div>

            <div class="filter-group">
                <button class="btn btn-reset" id="resetbuyers">
                  <i class="fa-solid fa-rotate-right"></i>
                </button>
          </div>
      </section>

      <!-- Data Table -->
      <section class="table-section">
        <div class="table-container">
          <table  id="buyerTable" class="data-table">
            <thead>
              <tr>
                <th>Buyer name</th>
                <th>Address</th>
                <th>City </th>
                <th>Email</th>
                <th>Contact</th>
                <th>Products</th>
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
<div id="addBuyerModal" class="modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div  class="modal-title">
           <h2 id="modalTitle" >Add Buyer</h2>
      </div>
     
      <div class="modal-close">
        <span id="closebuyerform" class="close">&times;</span>
      </div>
      
    </div>

    <form id="buyerform" class="modal-form" data-parsley-validate>
      <div class="form-group">
        <label>Buyer Name</label>
        <input type="text" name="buyername" required data-parsley-pattern="^[a-zA-Z\s]+$" data-parsley-error-message="Name must contain only letters">
      </div>



     <div class="form-row">
     

      <div class="form-group small">
        <label>Address</label>
        <input type="text" name="b_address" required>
      </div>

       <div class="form-group small" style="position: relative;">
        <label>District</label>
        <input type="text" id="cityIunput" name="b_city" required data-parsley-pattern="^[a-zA-Z\s]+$" data-parsley-error-message="City name must contain only letters"/>
        
      </div>
    </div>

  <div class="form-row">
       <div class="form-group small">
        <label>Email</label>
        <input type="email" name="b_email" required>
      </div>
     
        <div class="form-group small">
          <div id="contact-error"></div>
          <label>Contact number</label>
          <div class="contact-row">
            <input type="text" style="width: 50px;" name="b_country_code" value="+94" required readonly>    
            <input type="text" name="b_contact" required class="contact-number" data-parsley-type="digits"  data-parsley-length="[10, 10]" data-parsley-error-message=" must be exactly 10 digits" data-parsley-errors-container="#contact-error">
          </div>
        </div>
    </div>

      <div class="form-row">

      <div class="form-group small">
        <label>Product</label>
        <select id="productSelect" name="b_productid" value="--Select --" required data-parsley-error-message="Please select a product">
          <option value="">-- Select --</option>
        </select>
      </div>

      <div class="form-group small">
        <label>Status</label>
        <select name="b_status" required data-parsley-error-message="Please select a status">
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
  <script src="b_script.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/parsleyjs"></script>

</body>
</html>
