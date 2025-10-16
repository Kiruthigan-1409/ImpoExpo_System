
document.getElementById('exportExcel').addEventListener('click', function () {
    const table = document.querySelector('.delivery-table');
    const rows = Array.from(table.querySelectorAll('tr'));
    const visibleRows = rows.filter(r => r.style.display !== 'none');

    const data = visibleRows.map(row => {
        return Array.from(row.querySelectorAll('th, td'))
            .slice(0, -1) 
            .map(cell => cell.innerText.replace(/\n/g, ' ').trim());
    });

    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Deliveries");
    XLSX.writeFile(wb, "delivery_report.xlsx");

    const toast = document.getElementById('toast');
    toast.textContent = 'Excel report exported successfully!';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
});

function getFilteredRows(includeHiddenFromPagination = true) {
    const rows = Array.from(document.querySelectorAll('tbody#deliveryTableBody tr'));
    return rows.filter(r => {
        const filteredOut = r.style.display === 'none'; 
        const pagedOut = r.classList.contains('dt-hidden');
        return !filteredOut && (includeHiddenFromPagination ? true : !pagedOut);
    });
}

function collectData() {
    const rows = getFilteredRows(true); 
    const getCell = (row, idx) => (row.cells[idx]?.textContent || '').trim();

    const COL = { REF: 0, BUYER: 1, PRODUCT: 2, QTY: 3, SCHED: 4, ACT: 5, DRIVER: 6, ADDR: 7, STATUS: 8 };

    const body = rows.map(r => [
        getCell(r, COL.REF),
        getCell(r, COL.BUYER),
        getCell(r, COL.PRODUCT),
        getCell(r, COL.QTY),
        getCell(r, COL.SCHED),
        getCell(r, COL.ACT),
        getCell(r, COL.DRIVER),
        getCell(r, COL.ADDR),
        getCell(r, COL.STATUS),
    ]);
    
    const statusCounts = {};
    const dailyCounts = {}; 
    const qtyByDriver = {};

    const toYMD = (str) => {
        if (!str) return '';
        const d = new Date(str);
        return isNaN(d) ? '' : d.toISOString().slice(0, 10);
    };

    rows.forEach(r => {
        const status = getCell(r, COL.STATUS).trim();
        const sched = toYMD(getCell(r, COL.SCHED));
        const qty = Number((getCell(r, COL.QTY) || '0').replace(/[^\d.-]/g, '')) || 0;
        const drv = getCell(r, COL.DRIVER).trim() || 'Unknown';

        if (status) statusCounts[status] = (statusCounts[status] || 0) + 1;
        if (sched) dailyCounts[sched] = (dailyCounts[sched] || 0) + 1;
        qtyByDriver[drv] = (qtyByDriver[drv] || 0) + qty;
    });
    
    const dailyKeys = Object.keys(dailyCounts).sort((a, b) => new Date(a) - new Date(b));
    const statusKeys = Object.keys(statusCounts).sort();
    const driverKeys = Object.keys(qtyByDriver).sort();

    return { body, statusKeys, statusCounts, dailyKeys, dailyCounts, driverKeys, qtyByDriver };
}

async function renderChart(canvasId, type, labels, data, titleText) {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext('2d');
  if (ctx.__chart) { ctx.__chart.destroy(); }
  
  const palette = [
    '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#0ea5e9',
    '#14b8a6', '#e11d48', '#84cc16', '#f97316'
  ];
  
  const dataset = {
    label: titleText,
    data,
    borderWidth: (type === 'pie' || type === 'doughnut') ? 0 : 2,
    tension: (type === 'line') ? 0.3 : 0,
  };
  
  if (type === 'pie' || type === 'doughnut') {
    dataset.backgroundColor = labels.map((_, i) => palette[i % palette.length]);
  }

  const chart = new Chart(ctx, {
    type,
    data: { labels, datasets: [dataset] },
    options: {
      responsive: false,
      animation: false,
      plugins: {
        title: { display: true, text: titleText, font: { weight: 'bold', size: 16 } },
        legend: { display: (type === 'pie' || type === 'doughnut') } 
      },
      scales: (type === 'pie' || type === 'doughnut') ? {} : { x: { ticks: { autoSkip: true, maxRotation: 0 } } }
    }
  });

  chart.update('none');
  
  await new Promise(requestAnimationFrame);
  return canvas.toDataURL('image/png', 1.0);
}


function addImageFullWidth(doc, imgData, topY, margin = 40, canvasId = 'statusChart') {
  const pageWidth = doc.internal.pageSize.getWidth();
  const usableW = pageWidth - margin * 2;
  const canvas = document.getElementById(canvasId);
  const cw = canvas ? canvas.width : 1000;
  const ch = canvas ? canvas.height : 450;
  const ratio = ch / cw;
  const h = usableW * ratio;
  doc.addImage(imgData, 'PNG', margin, topY, usableW, h);
  return topY + h + 18;
}

function computeColumnWidths(doc, headCells, bodyRows, options = {}) {
  const min = options.min || [70, 80, 90, 60, 90, 90, 90, 120, 80]; 
  const max = options.max || [140,150,170,80,130,130,130,260,110];    
  const pad = options.pad || 16;                                      
  const sample = Math.min(options.sample || 200, bodyRows.length);   

  let w = headCells.map(txt => doc.getTextWidth(String(txt)) + pad);

  for (let r = 0; r < sample; r++) {
    const row = bodyRows[r];
    for (let c = 0; c < row.length; c++) {
      const cellText = String(row[c] ?? '');
      const cw = doc.getTextWidth(cellText) + pad;
      if (cw > w[c]) w[c] = cw;
    }
  }
  
  w = w.map((val, i) => Math.min(Math.max(val, min[i] || 60), max[i] || 220));
  return w;
}

function scaleToFit(widths, targetTotal) {
  const sum = widths.reduce((a,b)=>a+b,0);
  if (sum <= targetTotal) return widths;
  const scale = targetTotal / sum;
  return widths.map(v => Math.max(40, v * scale)); 
}

document.getElementById('exportPdfBtn').addEventListener('click', async function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
    const margin = 40;
    let y = margin;
    
    const title = 'Delivery Report - Makgrow Impex';
    const ts = new Date().toLocaleString();
    doc.setFontSize(16); doc.text(title, margin, y); y += 18;
    doc.setFontSize(10); doc.setTextColor(120); doc.text(`Generated: ${ts}`, margin, y); y += 10;
    doc.setTextColor(0);
    
    const statusVal = (document.getElementById('statusFilter').value || 'All');
    const driverVal = (document.getElementById('driverFilter').value || 'All');
    const qVal = (document.getElementById('searchInput').value || '—');
    const fromVal = (document.getElementById('fromDate').value || '—');
    const toVal = (document.getElementById('toDate').value || '—');

    doc.setFontSize(11);
    doc.text(`Filters — Status: ${statusVal} | Driver: ${driverVal} | Search: ${qVal} | From: ${fromVal} | To: ${toVal}`, margin, y);
    y += 20;
    
    const { body, statusKeys, statusCounts, dailyKeys, dailyCounts, driverKeys, qtyByDriver } = collectData();
    
    const statusImg = await renderChart(
        'statusChart',
        'bar',
        statusKeys,
        statusKeys.map(k => statusCounts[k] || 0),
        'Deliveries by Status'
    );
    y = addImageFullWidth(doc, statusImg, y, margin);

    const dailyImg = await renderChart(
        'dailyChart',
        'line',
        dailyKeys,
        dailyKeys.map(k => dailyCounts[k] || 0),
        'Deliveries per Day (Scheduled Date)'
    );
    
    if (y > doc.internal.pageSize.getHeight() - 220) { doc.addPage(); y = margin; }
    y = addImageFullWidth(doc, dailyImg, y, margin);

    const driverImg = await renderChart(
        'driverQtyChart',
        'bar',
        driverKeys,
        driverKeys.map(k => qtyByDriver[k] || 0),
        'Total Quantity by Driver'
    );
    if (y > doc.internal.pageSize.getHeight() - 220) { doc.addPage(); y = margin; }
    y = addImageFullWidth(doc, driverImg, y, margin);
    
    const headCells = Array.from(document.querySelectorAll('.delivery-table thead th'))
        .slice(0, -1).map(th => th.innerText.trim());

    const pageW   = doc.internal.pageSize.getWidth();
    const pageH   = doc.internal.pageSize.getHeight();
    const usableW = pageW - margin * 2;

    let colWidths = computeColumnWidths(doc, headCells, body, { pad: 18 });
    colWidths = scaleToFit(colWidths, usableW);
    
    let fontSize = 9;
    if (colWidths.reduce((a,b)=>a+b,0) > usableW * 1.02) fontSize = 8;

    // Pie chart 
    const pieLabels = ['Pending','In Transit','Delivered','Failed','Overdue','Returned'];
    const norm = s => s.toLowerCase().replace(/[\s_-]/g,'');
    const countFor = want =>
    Object.entries(statusCounts).reduce((sum,[k,v]) => sum + (norm(k) === norm(want) ? v : 0), 0);
    let pieData = pieLabels.map(lbl => countFor(lbl));
    
    if (pieData.reduce((a,b)=>a+b,0) === 0) {
    pieData = [1];
    pieLabels.splice(0, pieLabels.length, 'No Data');
    }

    const statusPieImg = await renderChart(
    'statusPieChart',
    'pie',
    pieLabels,
    pieData,
    'Deliveries by Status (Pie)'
    );

    if (y > doc.internal.pageSize.getHeight() - 220) { doc.addPage(); y = margin; }
    y = addImageFullWidth(doc, statusPieImg, y, margin, 'statusPieChart');
    
    doc.autoTable({
        head: [headCells],
        body: body,
        startY: (typeof y !== 'undefined' && y > pageH - 120) ? margin : (y || margin),
        styles: {
            fontSize: fontSize,
            cellPadding: 6,
            overflow: 'linebreak',     
            cellWidth: 'wrap',         
            valign: 'middle'
        },
        tableWidth: usableW,         
        columnStyles: Object.fromEntries(colWidths.map((cw, i) => [i, { cellWidth: cw }])),
        headStyles: { fillColor: [45,108,223], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [245,247,250] },
        
        rowPageBreak: 'auto',
        didDrawPage: (data) => {
            const page = doc.internal.getCurrentPageInfo().pageNumber;
            const pages = doc.getNumberOfPages();
            doc.setFontSize(10); doc.setTextColor(120);
            doc.text(`Page ${page} of ${pages}`, pageW - 90, pageH - 20);
            doc.setTextColor(0);
        },
        margin: { left: margin, right: margin }
    });

    doc.save('delivery_report.pdf');

    const toast = document.getElementById('toast');
    toast.textContent = 'PDF report exported with charts!';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
});