// Register Chart.js datalabels plugin globally
Chart.register(ChartDataLabels);

document.addEventListener('DOMContentLoaded', () => {
  // --- Add Import Modal ---
  const supplierSelect = document.getElementById('supplierSelect');
  const productSelect = document.getElementById('productSelect');
  const productNameInput = document.getElementById('productName');
  const productPriceInput = document.getElementById('productPrice');
  const addImportForm = document.getElementById('addImportForm');
  const importModal = document.getElementById('importModal');
  const openBtn = document.getElementById('openModal');
  const closeBtn = importModal.querySelector('.close-btn');
  const cancelBtn = document.getElementById('cancelBtn');

  openBtn.addEventListener('click', () => importModal.style.display = 'flex');
  closeBtn.addEventListener('click', () => importModal.style.display = 'none');
  cancelBtn.addEventListener('click', () => importModal.style.display = 'none');

  supplierSelect.addEventListener('change', () => {
    const supplierId = supplierSelect.value;
    Array.from(productSelect.options).forEach(option => {
      if (!option.value) return;
      option.style.display = option.dataset.supplier === supplierId ? '' : 'none';
    });
    productSelect.value = '';
    productNameInput.value = '';
    productPriceInput.value = '';
  });

  productSelect.addEventListener('change', () => {
    const selected = productSelect.options[productSelect.selectedIndex];
    productNameInput.value = selected.dataset.name || '';
    productPriceInput.value = selected.dataset.price || '';
  });

  addImportForm.addEventListener('submit', (e) => {
    const quantity = addImportForm.quantity.value.trim();
    const importDate = addImportForm.import_date.value;
    const arrivalDate = addImportForm.arrival_date.value;
    const expiryDate = addImportForm.expiry.value;

    if (!supplierSelect.value) return alert('Please select a supplier.'), e.preventDefault();
    if (!productSelect.value) return alert('Please select a product.'), e.preventDefault();
    if (!quantity || isNaN(quantity) || quantity <= 0) return alert('Quantity must be greater than 0.'), e.preventDefault();
    if (new Date(arrivalDate) < new Date(importDate)) return alert('Arrival date cannot be before import date.'), e.preventDefault();
    if (new Date(expiryDate) <= new Date(arrivalDate)) return alert('Expiry date must be after arrival date.'), e.preventDefault();
  });

  // --- Edit Import Modal ---
  const editModal = document.getElementById('editImportModal');
  const closeEditBtn = editModal.querySelector('.close-btn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');

  closeEditBtn.addEventListener('click', () => editModal.style.display = 'none');
  cancelEditBtn.addEventListener('click', () => editModal.style.display = 'none');

  window.addEventListener('click', (e) => {
    if (e.target === importModal) importModal.style.display = 'none';
    if (e.target === editModal) editModal.style.display = 'none';
  });

  document.querySelectorAll('.action-btn.edit').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const form = btn.closest('form');
      const importId = form.querySelector("input[name='edit']").value;
      const res = await fetch('save_import.php?action=get&id=' + importId);
      const data = await res.json();
      if (!data.success) return alert('Error fetching import for edit.');

      const imp = data.import;
      const editSupplierSelect = document.getElementById('edit_supplier_id');
      const editProductSelect = document.getElementById('edit_product_id');

      document.getElementById('edit_update_import').value = imp.import_id;
      document.getElementById('edit_import_ref').value = imp.import_ref;
      editSupplierSelect.value = imp.supplier_id;
      editSupplierSelect.dispatchEvent(new Event('change'));
      editProductSelect.value = imp.product_id;
      document.getElementById('edit_quantity').value = imp.quantity;
      document.getElementById('edit_import_date').value = imp.import_date;
      document.getElementById('edit_arrival_date').value = imp.stock_arrival;
      document.getElementById('edit_expiry').value = imp.stock_expiry;
      document.getElementById('edit_remarks').value = imp.remarks;

      editModal.style.display = 'flex';
    });
  });

  const editSupplierSelect = document.getElementById('edit_supplier_id');
  const editProductSelect = document.getElementById('edit_product_id');
  if (editSupplierSelect && editProductSelect) {
    editSupplierSelect.addEventListener('change', () => {
      const supplierId = editSupplierSelect.value;
      Array.from(editProductSelect.options).forEach(option => {
        if (!option.value) return;
        option.style.display = option.dataset.supplier === supplierId ? '' : 'none';
      });
      editProductSelect.value = '';
    });
  }

  // --- Table Filters ---
  const searchInput = document.getElementById('searchInput');
  const productFilter = document.getElementById('productFilter');
  const supplierFilter = document.getElementById('supplierFilter');
  const arrivalFrom = document.getElementById('arrivalDateFrom');
  const arrivalTo = document.getElementById('arrivalDateTo');
  const dataTable = document.querySelector('.data-table tbody');

  function filterTable() {
    const s = searchInput.value.toLowerCase();
    const pf = productFilter.value.toLowerCase();
    const sf = supplierFilter.value.toLowerCase();
    const from = arrivalFrom.value ? new Date(arrivalFrom.value) : null;
    const to = arrivalTo.value ? new Date(arrivalTo.value) : null;

    Array.from(dataTable.rows).forEach(row => {
      const ref = row.cells[0].textContent.toLowerCase();
      const supplier = row.cells[1].textContent.toLowerCase();
      const product = row.cells[2].textContent.toLowerCase();
      const arrival = new Date(row.cells[5].textContent);
      const show =
        ref.includes(s) &&
        (!pf || product.includes(pf)) &&
        (!sf || supplier.includes(sf)) &&
        (!from || arrival >= from) &&
        (!to || arrival <= to);
      row.style.display = show ? '' : 'none';
    });
  }

  document.getElementById('refreshBtn').addEventListener('click', () => {
    searchInput.value = productFilter.value = supplierFilter.value = arrivalFrom.value = arrivalTo.value = '';
    Array.from(dataTable.rows).forEach(row => row.style.display = '');
  });

  [searchInput, productFilter, supplierFilter, arrivalFrom, arrivalTo].forEach(el => {
    if (el) el.addEventListener('input', filterTable);
  });

  // --- Monthly Report Modal & Charts ---
  const reportMonth = document.getElementById('reportMonth');
  const reportPlaceholder = document.querySelector('.report-placeholder');
  const downloadBtn = document.getElementById('downloadPdfBtn');
  const reportModal = document.getElementById('reportModal');
  const openReportBtn = document.getElementById('openReportModal');
  const reportCloseBtn = reportModal.querySelector('.close-btn');
  let charts = [];

  // Style month selector container & add refresh button if missing
  const dateRangeSection = reportModal.querySelector('.report-date-range');
  dateRangeSection.style.display = 'flex';
  dateRangeSection.style.justifyContent = 'center';
  dateRangeSection.style.alignItems = 'center';
  dateRangeSection.style.width = "100%";

  let monthRefreshBtn = document.getElementById('monthResetBtn');
  if (!monthRefreshBtn) {
    monthRefreshBtn = document.createElement('button');
    monthRefreshBtn.id = 'monthResetBtn';
    monthRefreshBtn.title = 'Clear month';
    monthRefreshBtn.style.marginLeft = '14px';
    monthRefreshBtn.style.border = 'none';
    monthRefreshBtn.style.background = 'none';
    monthRefreshBtn.style.cursor = 'pointer';
    monthRefreshBtn.style.fontSize = '21px';
    monthRefreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
    dateRangeSection.querySelector('.date-input').appendChild(monthRefreshBtn);
  }

  openReportBtn.addEventListener('click', () => reportModal.style.display = 'flex');
  reportCloseBtn.addEventListener('click', () => reportModal.style.display = 'none');
  window.addEventListener('click', e => {
    if (e.target === reportModal) reportModal.style.display = 'none';
  });

  monthRefreshBtn.addEventListener('click', () => {
    reportMonth.value = '';
    reportPlaceholder.innerHTML = '<p>Select date range!</p>';
    downloadBtn.disabled = true;
    charts.forEach(c => c.destroy());
    charts = [];
  });

  function setupContainers() {
    if (!document.getElementById('chartsContainer')) {
      const container = document.createElement('div');
      container.id = 'chartsContainer';
      container.style.display = 'grid';
      container.style.gap = '18px';
      container.style.marginTop = '16px';
      container.style.gridTemplateColumns = '1fr 1fr';
      container.style.maxWidth = '1100px';
      container.style.margin = '16px auto';

      reportPlaceholder.innerHTML = '';
      reportPlaceholder.appendChild(container);

      // Line Chart container (full width)
      const lineDiv = document.createElement('div');
      lineDiv.style.gridColumn = '1 / span 2';
      lineDiv.style.background = '#fff';
      lineDiv.style.padding = '14px';
      lineDiv.style.borderRadius = '8px';
      lineDiv.style.boxShadow = '0px 2px 6px rgba(0,0,0,0.05)';
      const hLine = document.createElement('h4');
      hLine.textContent = 'Total Imports Over Time';
      lineDiv.appendChild(hLine);
      const canvasLine = document.createElement('canvas');
      canvasLine.id = 'lineImportsChart';
      canvasLine.height = 85;
      lineDiv.appendChild(canvasLine);
      container.appendChild(lineDiv);

      // Pie Chart containers side by side
      function createPieContainer(id, title) {
        const div = document.createElement('div');
        div.style.background = '#fff';
        div.style.padding = '14px';
        div.style.borderRadius = '8px';
        const h = document.createElement('h4');
        h.textContent = title;
        div.appendChild(h);
        const canvas = document.createElement('canvas');
        canvas.id = id;
        canvas.height = 110;
        div.appendChild(canvas);
        return div;
      }

      const pieProductDiv = createPieContainer('pieProductChart', 'Product Share');
      const pieSupplierDiv = createPieContainer('pieSupplierChart', 'Supplier Share');
      container.appendChild(pieProductDiv);
      container.appendChild(pieSupplierDiv);

      // Bar Chart containers side by side
      const barDiv = document.createElement('div');
      barDiv.style.background = '#fff';
      barDiv.style.padding = '14px';
      barDiv.style.borderRadius = '8px';
      const hBar = document.createElement('h4');
      hBar.textContent = 'Quantity per Product';
      barDiv.appendChild(hBar);
      const canvasBar = document.createElement('canvas');
      canvasBar.id = 'barQuantityChart';
      canvasBar.height = 110;
      barDiv.appendChild(canvasBar);

      const stackDiv = document.createElement('div');
      stackDiv.style.background = '#fff';
      stackDiv.style.padding = '14px';
      stackDiv.style.borderRadius = '8px';
      const hStack = document.createElement('h4');
      hStack.textContent = 'Product Quantities by Supplier';
      stackDiv.appendChild(hStack);
      const canvasStack = document.createElement('canvas');
      canvasStack.id = 'stackedBarChart';
      canvasStack.height = 110;
      stackDiv.appendChild(canvasStack);

      container.appendChild(barDiv);
      container.appendChild(stackDiv);

      // Summary Table full width
      const tableDiv = document.createElement('div');
      tableDiv.style.gridColumn = '1 / span 2';
      tableDiv.style.marginTop = '18px';
      const tableHeading = document.createElement('h4');
      tableHeading.textContent = 'Summary Table';
      tableDiv.appendChild(tableHeading);
      const table = document.createElement('table');
      table.id = 'summaryTable';
      table.style.width = '100%';
      table.style.borderCollapse = 'collapse';
      table.style.border = '1px solid #ccc';
      tableDiv.appendChild(table);
      container.appendChild(tableDiv);
    }
  }

  function createChart(ctx, config) {
    return new Chart(ctx, config);
  }

  reportMonth.addEventListener('change', async () => {
    const month = reportMonth.value;
    if (!month) {
      reportPlaceholder.innerHTML = '<p>Select a month to see report</p>';
      downloadBtn.disabled = true;
      return;
    }

    try {
      const res = await fetch('import_reports.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `month=${month}`
      });
      const data = await res.json();

      if (!data.success || !data.rows || !data.rows.length) {
        reportPlaceholder.innerHTML = '<p>No imports found for selected month.</p>';
        downloadBtn.disabled = true;
        charts.forEach(c => c.destroy());
        charts = [];
        return;
      }

      setupContainers();
      charts.forEach(c => c.destroy());
      charts = [];

      const ctxLine = document.getElementById('lineImportsChart').getContext('2d');
      charts.push(createChart(ctxLine, {
        type: 'line',
        data: {
          labels: Object.keys(data.importsOverTime),
          datasets: [{
            label: 'Total Imports',
            data: Object.values(data.importsOverTime),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.19)',
            fill: true,
            tension: 0.35,
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true } }
        }
      }));

      const ctxPieProduct = document.getElementById('pieProductChart').getContext('2d');
      charts.push(createChart(ctxPieProduct, {
        type: 'pie',
        data: {
          labels: Object.keys(data.productCount),
          datasets: [{
            data: Object.values(data.productCount),
            backgroundColor: [
              "#3b82f6", "#9333ea", "#22c55e", "#f59e0b", "#ef4444",
              "#10b981", "#6366f1", "#f43f5e", "#8b5cf6", "#db2777"
            ]
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            datalabels: {
              color: '#fff',
              font: { weight: 'bold', size: 14 },
              formatter: (val) => val
            }
          }
        },
        plugins: [ChartDataLabels]
      }));

      const ctxPieSupplier = document.getElementById('pieSupplierChart').getContext('2d');
      charts.push(createChart(ctxPieSupplier, {
        type: 'pie',
        data: {
          labels: Object.keys(data.supplierCount),
          datasets: [{
            data: Object.values(data.supplierCount),
            backgroundColor: [
              "#3b82f6", "#9333ea", "#22c55e", "#f59e0b", "#ef4444",
              "#10b981", "#6366f1", "#f43f5e", "#8b5cf6", "#db2777"
            ]
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            datalabels: {
              color: '#fff',
              font: { weight: 'bold', size: 14 },
              formatter: (val) => val
            }
          }
        },
        plugins: [ChartDataLabels]
      }));

      const ctxBarQuantity = document.getElementById('barQuantityChart').getContext('2d');
      charts.push(createChart(ctxBarQuantity, {
        type: 'bar',
        data: {
          labels: Object.keys(data.quantityByProduct),
          datasets: [{
            label: 'Quantity (kg)',
            data: Object.values(data.quantityByProduct),
            backgroundColor: '#22c55e',
            borderRadius: 5
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true }
          },
          plugins: {
            legend: { display: false },
            datalabels: {
              anchor: 'center',
              align: 'center',
              color: '#555',
              font: { size: 13 },
              formatter: (val) => val
            }
          }
        },
        plugins: [ChartDataLabels]
      }));

      const ctxStacked = document.getElementById('stackedBarChart').getContext('2d');
      const suppliers = Object.keys(data.quantityBySupplier);
      const productSet = new Set();
      suppliers.forEach(s => Object.keys(data.quantityBySupplier[s]).forEach(p => productSet.add(p)));
      const products = Array.from(productSet);

      const stackDatasets = products.map((product, i) => ({
        label: product,
        data: suppliers.map(supp => data.quantityBySupplier[supp][product] || 0),
        backgroundColor: [
          "#3b82f6", "#9333ea", "#22c55e", "#f59e0b", "#ef4444",
          "#10b981", "#6366f1", "#f43f5e", "#8b5cf6", "#db2777"
        ][i % 10]
      }));

      charts.push(createChart(ctxStacked, {
        type: 'bar',
        data: {
          labels: suppliers,
          datasets: stackDatasets
        },
        options: {
          responsive: true,
          scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
          },
          plugins: {
            legend: { position: 'center' },
            datalabels: {
              color: '#444',
              font: { weight: 'bold', size: 13 },
              formatter: (val) => val > 0 ? val : '',
              anchor: 'center',
              align: 'center'
            }
          }
        },
        plugins: [ChartDataLabels]
      }));

      const table = document.getElementById('summaryTable');
      table.innerHTML = `
        <thead>
          <tr>
            <th style="border:1px solid #ccc; padding:8px;">Reference</th>
            <th style="border:1px solid #ccc; padding:8px;">Supplier</th>
            <th style="border:1px solid #ccc; padding:8px;">Product</th>
            <th style="border:1px solid #ccc; padding:8px;">Quantity</th>
            <th style="border:1px solid #ccc; padding:8px;">Arrival Date</th>
          </tr>
        </thead>
        <tbody>
          ${data.rows.map(row => `
            <tr>
              <td style="border:1px solid #ccc; padding:8px;">${row.import_ref || ''}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.suppliername || ''}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.product_name || ''}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.quantity || 0}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.arrival_date || ''}</td>
            </tr>`).join('')}
        </tbody>`;

      downloadBtn.disabled = false;

      if (data.rows.length > 10) {
        showActivityMessage();
        launchConfetti();
      }

    } catch (error) {
      reportPlaceholder.innerHTML = `<p>Error loading report.</p>`;
      downloadBtn.disabled = true;
      charts.forEach(c => c.destroy());
      charts = [];
      console.error(error);
    }
  });

  function showReport(data) {
  // Your existing chart rendering
  renderCharts(data);

  // Trigger confetti if more than 10 records
  if (data.length > 10) {
    // Delay slightly so charts appear first
    setTimeout(() => {
      launchConfetti();
    }, 800);
  }
}

function showActivityMessage() {
  const msg = document.getElementById('activityMessage');
  if (!msg) return;
  msg.classList.add('show');
  setTimeout(() => {
    msg.classList.remove('show');
  }, 3000); // Show for 3 seconds
}

function launchConfetti() {
  const duration = 3 * 1000;
  const animationEnd = Date.now() + duration;
  const defaults = { startVelocity: 25, spread: 360, ticks: 60, zIndex: 1000 };

  function randomInRange(min, max) {
    return Math.random() * (max - min) + min;
  }

  const interval = setInterval(() => {
    const timeLeft = animationEnd - Date.now();

    if (timeLeft <= 0) {
      return clearInterval(interval);
    }

    const particleCount = 50 * (timeLeft / duration);
    // Two bursts from random sides
    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
    });
    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
    });
  }, 250);
}

  // PDF Download handler
  downloadBtn.addEventListener('click', () => {
    const monthVal = reportMonth.value;
    if (!monthVal) return;

    const filename = `${monthVal}-Report.pdf`;
    const chartsContainer = document.getElementById('chartsContainer');
    if (!chartsContainer) return alert('Report not ready to download.');

    downloadBtn.style.display = "none";

    setTimeout(() => {
      html2canvas(chartsContainer, { scale: 2, backgroundColor: "#ffffff" }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdfWidth = 800;
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width + 500;

        const pdf = new window.jspdf.jsPDF({
          orientation: "vertical",
          unit: "px",
          format: [pdfWidth, pdfHeight]
        });

        pdf.setFillColor(240, 248, 255);
        pdf.rect(0, 0, pdfWidth, 60, "F");
        pdf.setTextColor(0, 102, 153);
        pdf.setFont("helvetica", "bold");
        pdf.setFontSize(24);
        pdf.text(`Makgrow Impex Pvt Ltd`, 30, 35);

        pdf.setFontSize(18);
        pdf.setTextColor(0);
        pdf.text(`Monthly Import Report - ${monthVal}`, pdfWidth / 2, 35, { align: "center" });

        const now = new Date();
        const formattedDateTime = now.toLocaleString('en-LK', {
          dateStyle: 'medium',
          timeStyle: 'short',
          hour12: true
        });
        pdf.setFontSize(12);
        pdf.setTextColor(60);
        pdf.text(`Generated on: ${formattedDateTime}`, pdfWidth - 30, 35, { align: "right" });

        const imgY = 70;
        const imgHeight = canvas.height * (pdfWidth - 40) / canvas.width;
        pdf.addImage(imgData, 'PNG', 20, imgY, pdfWidth - 40, imgHeight);

        const footerY = imgY + imgHeight + 30;
        pdf.setDrawColor(180);
        pdf.line(20, footerY, pdfWidth - 20, footerY);
        pdf.setFontSize(12);
        pdf.setTextColor(100);
        pdf.text("Prepared by: ImpoExpo System", 30, footerY + 25);
        pdf.text("Page 1 of 1", pdfWidth - 80, footerY + 25);

        pdf.save(filename);
        downloadBtn.style.display = "";
      }).catch(err => {
        alert("Could not generate PDF: " + err.message);
        downloadBtn.style.display = "";
      });
    }, 400);
  });
});
