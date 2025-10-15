<?php
require "../backend/fetchsuppliers.php";

$suppliers = getSuppliers();
$publishedDate = date("F j, Y");
$entries = count($suppliers);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>import Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="supplierreport.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/parsleyjs/src/parsley.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



</head>

<body>
  <div class="app-container">
    <?php include '../../layout/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-content">
          
          <div class="header-actions">
            <button class="btn btn-secondary" style="background-color: #635c5c; color: aliceblue;">
              <a href="supplier.php" style="text-decoration: none; color: #fff;">
                <span class="icon">
                  <i class="fa-solid fa-backward-step"></i>
                </span>
                back
              </a>

            </button>
          </div>
        </div>
      </header>


      <!-- Filters -->
<div class="summary-container">
<section class="filters-card">
  <div class="filters-header">
    <h3>Supplier summary</h3>
  </div>
  <div class="filters-body">
    <div class="filters-left">
      <div class="filter-group">
        <label for="fromDate">From</label>
        <input type="date" id="fromDate" placeholder="From">
      </div>

      <div class="filter-group">
        <label for="toDate">To</label>
        <input type="date" id="toDate" placeholder="To">
      </div>

      <div class="filter-group">
        <label for="entries">Entries</label>
        <input type="number" id="entries" min="1">
      </div>

    </div>

    <div class="checkbox-list">
      <label style="font-weight:bold;">Data visual</label>
      <div class="checkbox-item">
        <input type="checkbox" class="charts" id="supplierdis" name="charts[]" value="supplierdis">
        <label for="supplierdis">Show data visual for supplier distribution across countries</label>
      </div>

      <div class="checkbox-item">
        <input type="checkbox" class="charts" id="supplier_perf" name="charts[]" value="supplier_perf">
        <label for="supplier_perf">Show bar chart for top suppliers according to imports</label>
      </div>
       <label style="font-weight:bold;">Data Summary</label>
       <div class="checkbox-item">
        <input type="checkbox" class="charts" id="s_datasummary" name="charts[]" value="s_datasummary">
        <label for="s_datasummary">Include data summary</label>
      </div>
    </div>
  </div>
 

    <div class="filters-right">
      <button class="btn btn-pdf" id="pdfbtn">
        <i class="fa-solid fa-file-pdf"></i> PDF
      </button>
      <button class="btn btn-excel" id="excelbtn">
        <i class="fa-solid fa-file-excel"></i> CSV
      </button>
    </div>
  </section>

  <section class="filters-card">
  <div class="filters-header">
    <h3>Buyer summary</h3>
  </div>
  <div class="filters-body">
    <div class="filters-left">
      <div class="filter-group">
        <label for="buyerfromDate">From</label>
        <input type="date" id="buyerfromDate" placeholder="From">
      </div>

      <div class="filter-group">
        <label for="buyertoDate">To</label>
        <input type="date" id="buyertoDate" placeholder="To">
      </div>

      <div class="filter-group">
        <label for="buyerentries">Entries</label>
        <input type="number" id="buyerentries" min="1">
      </div>

    </div>

    <div class="checkbox-list">
      <label style="font-weight:bold;">Data visual</label>
      <div class="checkbox-item">
        <input type="checkbox" class="buyercharts" id="buyerdis" name="buyercharts[]" value="buyerdis">
        <label for="buyerdis">Show data visual for buyer distribution across cites</label>
      </div>

      <div class="checkbox-item">
        <input type="checkbox" class="buyercharts" id="buyerbarchart" name="buyercharts[]" value="buyerbarchart">
        <label for="buyerbarchart">Show pie chart for product distribution according to buyer count</label>
      </div>
      <div class="checkbox-item">
        <input type="checkbox" class="buyercharts" id="buyercoloumn" name="buyercharts[]" value="buyercoloumn">
        <label for="buyercoloumn">Show coloumn chart for top 5 buyer according to revenue </label>
      </div>
       <label style="font-weight:bold;">Data Summary</label>
       <div class="checkbox-item">
        <input type="checkbox" class="buyercharts" id="b_datasummary" name="buyercharts[]" value="b_datasummary">
        <label for="b_datasummary">Include data summary</label>
      </div>
    </div>
  </div>
 

    <div class="filters-right">
      <button class="btn btn-pdf" id="buyerpdfbtn">
        <i class="fa-solid fa-file-pdf"></i> PDF
      </button>
      <button class="btn btn-excel" id="buyerexcelbtn">
        <i class="fa-solid fa-file-excel"></i> CSV
      </button>
    </div>
  </section>
</div>
    </main>
  </div>


  <div class="report-container" id="printReport" style="display:none;"></div>
  <section class="charts-section">
    <canvas id="supplierDistChart" style="display:none; max-height: 300px;"></canvas>
    <canvas id="supplierPerfChart" style="display:none; max-height: 300px;"></canvas>
    <canvas id="countryChart" style="display:none; max-height: 300px;"></canvas>
  </section>


  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/parsleyjs"></script>
  
  <canvas id="supplierPieChart" width="400" height="400"></canvas>
  <script>
    document.addEventListener("DOMContentLoaded", () => {

     
      function validatesupplierFilters() {
        const fromDate = document.getElementById("fromDate").value;
        const toDate = document.getElementById("toDate").value;

        if (!fromDate || !toDate) {
          alert("Please select both From and To dates.");
          return false;
        }
        return true;
      }

      function validatebuyerFilters() {
        const fromDate = document.getElementById("buyerfromDate").value;
        const toDate = document.getElementById("buyertoDate").value;

        if (!fromDate || !toDate) {
          alert("Please select both From and To dates.");
          return false;
        }
        return true;
      }

      // PDF Export
      document.getElementById("pdfbtn").addEventListener("click", () => {
        
         if (!validatesupplierFilters()) return;
          const fromDate = document.getElementById("fromDate").value;
          const toDate = document.getElementById("toDate").value;
          const entries = document.getElementById("entries").value;

          if (!fromDate || !toDate) {
              alert("Please select both From and To dates.");
              return;
          }

          const from = new Date(fromDate);
          const to = new Date(toDate);

        
          if (to < from) {
              alert("The 'To' date cannot be earlier than the 'From' date.");
              return;
          }

          const selectedCharts = Array.from(document.querySelectorAll(".charts:checked"))
                                      .map(chk => chk.value);

          const params = new URLSearchParams({ fromDate, toDate, entries });
          selectedCharts.forEach(chart => params.append('charts[]', chart));

          window.location.href = "../backend/generatePDF.php?" + params.toString();
      });


      //buyerpdf
      document.getElementById("buyerpdfbtn").addEventListener("click", () => {
        
         if (!validatebuyerFilters()) return;
          const fromDate = document.getElementById("buyerfromDate").value;
          const toDate = document.getElementById("buyertoDate").value;
          const entries = document.getElementById("buyerentries").value;

          if (!fromDate || !toDate) {
              alert("Please select both From and To dates.");
              return;
          }

          const from = new Date(fromDate);
          const to = new Date(toDate);

        
          if (to < from) {
              alert("The 'To' date cannot be earlier than the 'From' date.");
              return;
          }

          const selectedCharts = Array.from(document.querySelectorAll(".buyercharts:checked"))
                                      .map(chk => chk.value);

          const params = new URLSearchParams({ fromDate, toDate, entries });
          selectedCharts.forEach(chart => params.append('buyercharts[]', chart));

          window.location.href = "../backend/generatePDF buyer.php?" + params.toString();
      });

   
      document.getElementById("excelbtn").addEventListener("click", () => {
        if (!validateFilters()) return;

        const fromDate = document.getElementById("fromDate").value;
        const toDate = document.getElementById("toDate").value;
        const entries = document.getElementById("entries").value;

        const params = new URLSearchParams({ fromDate, toDate, entries });
        window.location.href = "../backend/generateExceltopsupplier.php?" + params.toString();
      });

      const cards = document.querySelectorAll('.filters-card');

      cards.forEach(card => {
          card.addEventListener('click', () => {
             
              cards.forEach(c => c.classList.remove('active'));

              
              card.classList.add('active');
          });
      });


           

    });
</script>

</body>
</html>