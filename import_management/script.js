//datalabels plugins
Chart.register(ChartDataLabels);

document.addEventListener('DOMContentLoaded', () => {
  const reportMonth = document.getElementById('reportMonth');
  const reportPlaceholder = document.querySelector('.report-placeholder');
  const downloadBtn = document.getElementById('downloadPdfBtn');
  const reportModal = document.getElementById('reportModal');
  const openReportBtn = document.getElementById('openReportModal');
  const reportCloseBtn = reportModal.querySelector('.close-btn');
  let charts = [];

  // Center and style the month selector container
  const dateRangeSection = reportModal.querySelector('.report-date-range');
  dateRangeSection.style.display = 'flex';
  dateRangeSection.style.justifyContent = 'center';
  dateRangeSection.style.alignItems = 'center';
  dateRangeSection.style.width = "100%";

  // Add Refresh Icon Button beside month selector
  let refreshBtn = document.getElementById('monthResetBtn');
  if (!refreshBtn) {
    refreshBtn = document.createElement('button');
    refreshBtn.id = 'monthResetBtn';
    refreshBtn.title = 'Clear selection';
    refreshBtn.style.marginLeft = '14px';
    refreshBtn.style.border = 'none';
    refreshBtn.style.background = 'none';
    refreshBtn.style.cursor = 'pointer';
    refreshBtn.style.fontSize = '21px';
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
    const dateInput = dateRangeSection.querySelector('.date-input');
    dateInput.appendChild(refreshBtn);
  }

  openReportBtn.addEventListener('click', () => reportModal.style.display = 'flex');
  reportCloseBtn.addEventListener('click', () => reportModal.style.display = 'none');
  window.addEventListener('click', (e) => {
    if (e.target === reportModal) reportModal.style.display = 'none';
  });

  refreshBtn.addEventListener('click', () => {
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

      const lineDiv = document.createElement('div');
      lineDiv.style.gridColumn = '1 / span 2';
      lineDiv.style.background = '#fff';
      lineDiv.style.padding = '14px';
      lineDiv.style.borderRadius = '8px';
      lineDiv.style.boxShadow = '0px 2px 6px rgba(0,0,0,0.05)';
      const lineH = document.createElement('h4');
      lineH.textContent = 'Total Imports Over Time';
      lineDiv.appendChild(lineH);
      const canvasLine = document.createElement('canvas');
      canvasLine.id = 'lineImportsChart';
      canvasLine.height = 85;
      lineDiv.appendChild(canvasLine);
      container.appendChild(lineDiv);

      const pieProductDiv = document.createElement('div');
      pieProductDiv.style.background = '#fff';
      pieProductDiv.style.padding = '14px';
      pieProductDiv.style.borderRadius = '8px';
      const pieProductH = document.createElement('h4');
      pieProductH.textContent = 'Product Share';
      pieProductDiv.appendChild(pieProductH);
      const canvasPieProduct = document.createElement('canvas');
      canvasPieProduct.id = 'pieProductChart';
      canvasPieProduct.height = 110;
      pieProductDiv.appendChild(canvasPieProduct);

      const pieSupplierDiv = document.createElement('div');
      pieSupplierDiv.style.background = '#fff';
      pieSupplierDiv.style.padding = '14px';
      pieSupplierDiv.style.borderRadius = '8px';
      const pieSupplierH = document.createElement('h4');
      pieSupplierH.textContent = 'Supplier Share';
      pieSupplierDiv.appendChild(pieSupplierH);
      const canvasPieSupplier = document.createElement('canvas');
      canvasPieSupplier.id = 'pieSupplierChart';
      canvasPieSupplier.height = 110;
      pieSupplierDiv.appendChild(canvasPieSupplier);

      container.appendChild(pieProductDiv);
      container.appendChild(pieSupplierDiv);

      const barDiv = document.createElement('div');
      barDiv.style.background = '#fff';
      barDiv.style.padding = '14px';
      barDiv.style.borderRadius = '8px';
      const barH = document.createElement('h4');
      barH.textContent = 'Quantity per Product';
      barDiv.appendChild(barH);
      const canvasBar = document.createElement('canvas');
      canvasBar.id = 'barQuantityChart';
      canvasBar.height = 110;
      barDiv.appendChild(canvasBar);

      const stackDiv = document.createElement('div');
      stackDiv.style.background = '#fff';
      stackDiv.style.padding = '14px';
      stackDiv.style.borderRadius = '8px';
      const stackH = document.createElement('h4');
      stackH.textContent = 'Product Quantities by Supplier';
      stackDiv.appendChild(stackH);
      const canvasStack = document.createElement('canvas');
      canvasStack.id = 'stackedBarChart';
      canvasStack.height = 110;
      stackDiv.appendChild(canvasStack);

      container.appendChild(barDiv);
      container.appendChild(stackDiv);

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

      // Line Chart
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

      // Pie Chart: Product Share
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
              formatter: (value) => value
            }
          }
        },
        plugins: [ChartDataLabels]
      }));

      // Pie Chart: Supplier Share
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
              formatter: (value) => value
            }
          }
        },
        plugins: [ChartDataLabels]
      }));

      // Bar Chart: Quantity per Product
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
          plugins: {
            legend: { position: 'center' },
            datalabels: {
              color: '#444',
              font: { weight: 'bold', size: 13 },
              formatter: (value) => value > 0 ? value : '',
              anchor: 'center',
              align: 'center'
            }
          },
          scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
          }
        },
        plugins: [ChartDataLabels]
      }));

      // Stacked Bar Chart: Product Quantities by Supplier
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
          plugins: {
            legend: { position: 'center' },
            datalabels: {
              color: '#444',
              font: { weight: 'bold', size: 13 },
              formatter: (value) => value > 0 ? value : '',
              anchor: 'center',
              align: 'center'
            }
          },
          scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
          }
        },
        plugins: [ChartDataLabels]
      }));

      // Build summary table
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

    } catch (error) {
      reportPlaceholder.innerHTML = '<p>Error loading report.</p>';
      downloadBtn.disabled = true;
      charts.forEach(c => c.destroy());
      charts = [];
      console.error(error);
    }
  });

  // PDF Download handler (unchanged from before)
  downloadBtn.addEventListener('click', () => {
    const monthVal = reportMonth.value;
    if (!monthVal) return;

    const filename = `${monthVal}-Report.pdf`;
    const chartsContainer = document.getElementById('chartsContainer');
    if (!chartsContainer) return alert('Report not ready to download.');

    downloadBtn.style.display = "none";

    setTimeout(() => {
      html2canvas(chartsContainer, { scale: 2, backgroundColor: "#ffffff" }).then((canvas) => {
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
