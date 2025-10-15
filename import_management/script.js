document.addEventListener('DOMContentLoaded', () => {
  const reportMonth = document.getElementById('reportMonth');
  const reportPlaceholder = document.querySelector('.report-placeholder');
  const downloadBtn = document.getElementById('downloadPdfBtn');
  const reportModal = document.getElementById('reportModal');
  const openReportBtn = document.getElementById('openReportModal');
  const reportCloseBtn = reportModal.querySelector('.close-btn');
  let charts = [];

  openReportBtn.addEventListener('click', () => reportModal.style.display = 'flex');
  reportCloseBtn.addEventListener('click', () => reportModal.style.display = 'none');
  window.addEventListener('click', (e) => {
    if (e.target === reportModal) reportModal.style.display = 'none';
  });

  function setupContainers() {
    if (!document.getElementById('chartsContainer')) {
      const container = document.createElement('div');
      container.id = 'chartsContainer';
      container.style.display = 'grid';
      container.style.gridTemplateColumns = 'repeat(auto-fit, minmax(300px, 1fr))';
      container.style.gap = '20px';
      container.style.marginTop = '20px';
      reportPlaceholder.innerHTML = '';
      reportPlaceholder.appendChild(container);

      // Chart containers
      const chartsInfo = [
        {id: 'lineImportsChart', title: 'Total Imports Over Time', type: 'line'},
        {id: 'pieProductChart', title: 'Product Share', type: 'pie'},
        {id: 'pieSupplierChart', title: 'Supplier Share', type: 'pie'},
        {id: 'barQuantityChart', title: 'Quantity per Product', type: 'bar'},
        {id: 'stackedBarChart', title: 'Product Quantities by Supplier', type: 'bar', stacked: true}
      ];

      chartsInfo.forEach(chart => {
        const div = document.createElement('div');
        div.style.border = '1px solid #ccc';
        div.style.padding = '10px';
        div.style.borderRadius = '8px';
        div.style.backgroundColor = '#fff';
        const h = document.createElement('h4');
        h.textContent = chart.title;
        div.appendChild(h);
        const canvas = document.createElement('canvas');
        canvas.id = chart.id;
        canvas.height = 250;
        div.appendChild(canvas);
        container.appendChild(div);
      });

      // Summary table container
      const tableDiv = document.createElement('div');
      tableDiv.style.gridColumn = '1 / -1';
      tableDiv.style.marginTop = '20px';
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
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `month=${month}`
      });
      const data = await res.json();

      if (!data.success || !data.rows || !data.rows.length) {
        reportPlaceholder.innerHTML = '<p>No imports found for selected month.</p>';
        downloadBtn.disabled = true;
        return;
      }

      setupContainers();
      charts.forEach(c => c.destroy());
      charts = [];

      // Line Chart: total imports over time
      const ctxLine = document.getElementById('lineImportsChart').getContext('2d');
      charts.push(createChart(ctxLine, {
        type: 'line',
        data: {
          labels: Object.keys(data.importsOverTime),
          datasets: [{
            label: 'Total Imports',
            data: Object.values(data.importsOverTime),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            fill: true,
            tension: 0.3,
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true } }
        }
      }));

      // Pie Chart: product share
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
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
      }));

      // Pie Chart: supplier share
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
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
      }));

      // Bar Chart: quantity per product
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
          scales: { y: { beginAtZero: true } },
          plugins: { legend: { display: false } }
        }
      }));

      // Stacked Bar Chart: product quantities by supplier
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
          plugins: { legend: { position: 'bottom' } },
          scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
          }
        }
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
              <td style="border:1px solid #ccc; padding:8px;">${row.import_ref}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.suppliername}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.product_name}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.quantity || 0}</td>
              <td style="border:1px solid #ccc; padding:8px;">${row.arrival_date || ''}</td>
            </tr>`).join('')}
        </tbody>`;

      downloadBtn.disabled = false;

    } catch (error) {
      reportPlaceholder.innerHTML = '<p>Error loading report.</p>';
      downloadBtn.disabled = true;
      console.error(error);
    }
  });
});
