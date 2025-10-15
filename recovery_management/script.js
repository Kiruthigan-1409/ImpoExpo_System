document.addEventListener("DOMContentLoaded", () => {
  const modalOverlay = document.getElementById("modalOverlay");
  const openBtn = document.getElementById("openModalBtn");
  const closeBtn = document.getElementById("closeBtn");
  const cancelBtn = document.getElementById("cancelBtn");

  const form = document.getElementById("recoveryForm");
  const recoveryRefInput = document.getElementById("recoveryRef");
  const recoveryDateInput = document.getElementById("recoveryDate");
  const originalDelivery = document.getElementById("originalDelivery");
  const productInput = document.getElementById("product");
  const quantityInput = document.getElementById("quantity");
  const financialImpactInput = document.getElementById("financialImpact");
  const reasonInput = document.getElementById("reason");
  const itemConditionInput = document.getElementById("itemCondition");
  const actionTakenInput = document.getElementById("actionTaken");

  let unitPrice = 0;
  let maxQuantity = 1;
  let isEditMode = false;
  modalOverlay.style.display = "none";

  // --- Open modal (Add Mode) ---
  function openModal() {
    isEditMode = false;
    modalOverlay.style.display = "flex";
    form.reset();
    recoveryRefInput.value = "REC-" + Date.now();
    recoveryDateInput.value = new Date().toISOString().split("T")[0];
    productInput.value = "";
    quantityInput.value = "";
    financialImpactInput.value = "0";
    unitPrice = 0;
    maxQuantity = 1;
    document.getElementById('formMode').value = 'add';
    document.getElementById('recordId').value = '';
    document.getElementById('modalTitle').textContent = 'New Recovery Record';
    document.getElementById('originalDelivery').disabled = false;
    recoveryDateInput.removeAttribute("min");
  }

  // --- Close modal ---
  function closeModal() {
    modalOverlay.style.display = "none";
    location.reload();
  }

  openBtn.addEventListener("click", openModal);
  closeBtn.addEventListener("click", closeModal);
  cancelBtn.addEventListener("click", closeModal);

  // --- Update delivery details, financial impact & min recovery date ---
  function updateDeliveryDetails() {
    const selectedOption = originalDelivery.selectedOptions[0];
    if (!selectedOption || !selectedOption.value) {
      productInput.value = '';
      if (!isEditMode) quantityInput.value = '';
      if (!isEditMode) financialImpactInput.value = '0';
      unitPrice = 0;
      maxQuantity = 1;
      recoveryDateInput.removeAttribute("min");
      return;
    }
    const productName = selectedOption.getAttribute("data-product");
    const rawQuantityAttr = selectedOption.getAttribute("data-quantity");
    const rawUnitPriceAttr = selectedOption.getAttribute("data-unit-price");
    maxQuantity = parseFloat(rawQuantityAttr) || 1;
    unitPrice = parseFloat(rawUnitPriceAttr) || 0;
    productInput.value = productName || '';

    // Set min recovery date based on delivery actual_date
    const minDate = selectedOption.getAttribute("data-delivery-date");
    if (minDate) {
      recoveryDateInput.setAttribute("min", minDate);
      if (!recoveryDateInput.value || recoveryDateInput.value < minDate) {
        recoveryDateInput.value = minDate;
      }
    } else {
      recoveryDateInput.removeAttribute("min");
    }

    if (!isEditMode) {
      quantityInput.value = maxQuantity;
      financialImpactInput.value = (unitPrice * maxQuantity).toFixed(2);
    }
  }

  originalDelivery.addEventListener("change", updateDeliveryDetails);

  // --- Quantity input validation and live calc ---
  quantityInput.addEventListener("input", () => {
    let q = quantityInput.value;
    let qty = parseFloat(q);

    if (q === "-" || qty < 0) {
      quantityInput.value = "";
      financialImpactInput.value = "0.00";
      return;
    }
    if (q === "" || isNaN(qty)) {
      financialImpactInput.value = "0.00";
      return;
    }
    if (qty < 1) {
      financialImpactInput.value = "0.00";
      return;
    }
    if (qty > maxQuantity) {
      qty = maxQuantity;
      quantityInput.value = qty;
    }
    financialImpactInput.value = (qty * unitPrice).toFixed(2);
  });

  quantityInput.addEventListener("blur", () => {
    let q = quantityInput.value;
    let qty = parseFloat(q);
    if (q === "" || isNaN(qty) || qty < 1) {
      quantityInput.value = 1;
      financialImpactInput.value = (1 * unitPrice).toFixed(2);
      return;
    }
    if (qty > maxQuantity) {
      alert(`Recovery quantity cannot exceed original delivery quantity (${maxQuantity}). Setting to max.`);
      quantityInput.value = maxQuantity;
      financialImpactInput.value = (maxQuantity * unitPrice).toFixed(2);
    }
  });

  // --- Form validation (add min date check) ---
  form.addEventListener("submit", (e) => {
    const errors = [];
    if (!recoveryDateInput.value) errors.push("Recovery Date is required.");
    if (!originalDelivery.value) errors.push("Original Delivery must be selected.");
    if (!productInput.value.trim()) errors.push("Product is required.");
    if (parseFloat(quantityInput.value) <= 0) errors.push("Quantity must be greater than 0.");
    if (parseFloat(financialImpactInput.value) < 0) errors.push("Financial Impact must be 0 or more.");

    // Min date validation
    const selectedOption = originalDelivery.selectedOptions[0];
    if (selectedOption && selectedOption.value) {
      const minDate = selectedOption.getAttribute("data-delivery-date");
      if (minDate && recoveryDateInput.value < minDate) {
        errors.push("Recovery date cannot be before delivery date (" + minDate + ").");
      }
    }

    const qty = parseFloat(quantityInput.value);
    const impact = parseFloat(financialImpactInput.value);
    if (qty > 0 && impact === 0 && originalDelivery.value) {
      errors.push("Warning: Financial impact is 0 (check product price in database).");
    }

    if (!reasonInput.value) errors.push("Reason must be selected.");
    if (!itemConditionInput.value) errors.push("Item Condition must be selected.");
    if (!actionTakenInput.value) errors.push("Action Taken must be selected.");

    if (errors.length > 0) {
      e.preventDefault();
      alert("Please fix the following errors:\n- " + errors.join("\n- "));
    }
  });

  // --- Edit function with proper data fetching ---
  window.editRecord = function(id) {
    fetch('get_recovery_record.php?id=' + id)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          populateEditForm(data.record);
        } else {
          alert('Failed to fetch record data: ' + (data.message || 'Unknown error'));
        }
      });
  };

  // --- Populate edit form function with delivery date min logic ---
  function populateEditForm(record) {
    isEditMode = true;
    document.getElementById('formMode').value = 'edit';
    document.getElementById('recordId').value = record.id;
    document.getElementById('modalTitle').textContent = 'Edit Recovery Record';
    document.getElementById('originalDelivery').addEventListener('mousedown', function(e){
      if (isEditMode) e.preventDefault();
    });
    document.getElementById('originalDelivery').addEventListener('keydown', function(e){
      if (isEditMode) e.preventDefault();
    });
    document.getElementById('recoveryRef').value = record.recovery_ref;
    document.getElementById('recoveryDate').value = record.recovery_date;
    document.getElementById('product').value = record.product;

    // Set delivery dropdown correctly
    const deliverySelect = document.getElementById('originalDelivery');
    const wantedValue = (record.original_delivery || "").trim();
    let found = false;
    for (let i = 0; i < deliverySelect.options.length; i++) {
      if (deliverySelect.options[i].value.trim() === wantedValue) {
        deliverySelect.selectedIndex = i;
        found = true;
        break;
      }
    }
    if (!found) {
      if (wantedValue) {
        const labelQty = record.delivery_quantity || record.quantity;
        const labelUnitPrice = parseFloat(record.financial_impact) / parseFloat(record.quantity) || 0;
        const fallbackLabel = `${wantedValue} - ${record.product} (Qty: ${labelQty}, Unit Price: LKR ${labelUnitPrice})`;
        const opt = document.createElement('option');
        opt.value = wantedValue;
        opt.textContent = fallbackLabel;
        opt.selected = true;
        opt.setAttribute("data-product", record.product);
        opt.setAttribute("data-quantity", labelQty);
        opt.setAttribute("data-unit-price", labelUnitPrice);
        opt.setAttribute("data-delivery-date", record.delivery_actual_date || "");
        deliverySelect.appendChild(opt);
      } else {
        deliverySelect.selectedIndex = 0;
      }
    }
    let labelQty = record.delivery_quantity || record.quantity || 1;
    maxQuantity = parseFloat(labelQty) || 1;
    const selectedOption = deliverySelect.selectedOptions[0];
    if (selectedOption && selectedOption.getAttribute("data-quantity")) {
      maxQuantity = parseFloat(selectedOption.getAttribute("data-quantity")) || maxQuantity;
    }
    unitPrice = parseFloat(record.financial_impact) / parseFloat(record.quantity) || 0;
    document.getElementById('quantity').value = record.quantity;
    document.getElementById('financialImpact').value = parseFloat(record.financial_impact).toFixed(2);
    document.getElementById('reason').value = record.reason;
    document.getElementById('itemCondition').value = record.item_condition;
    document.getElementById('actionTaken').value = record.action_taken;
    document.getElementById('notes').value = record.notes || '';
    modalOverlay.style.display = 'flex';

    // Set min recovery date during Edit from option or record
    const minDate = (selectedOption && selectedOption.getAttribute("data-delivery-date")) 
        ? selectedOption.getAttribute("data-delivery-date") 
        : (record.delivery_actual_date || null);
    if (minDate) {
      recoveryDateInput.setAttribute("min", minDate);
      if (recoveryDateInput.value < minDate) {
        recoveryDateInput.value = minDate;
      }
    } else {
      recoveryDateInput.removeAttribute("min");
    }
  }

  // --- Delete functionality ---
  window.deleteRecord = function(id) {
    if (confirm('Are you sure you want to delete this recovery record? This action cannot be undone and may affect stock levels if it was previously returned.')) {
      const deleteForm = document.createElement('form');
      deleteForm.method = 'POST';
      deleteForm.action = 'save_recovery.php';
      deleteForm.style.display = 'none';
      const modeInput = document.createElement('input');
      modeInput.type = 'hidden';
      modeInput.name = 'mode';
      modeInput.value = 'delete';
      deleteForm.appendChild(modeInput);
      const recordIdInput = document.createElement('input');
      recordIdInput.type = 'hidden';
      recordIdInput.name = 'record_id';
      recordIdInput.value = id;
      deleteForm.appendChild(recordIdInput);
      document.body.appendChild(deleteForm);
      deleteForm.submit();
    }
  };

  // --- Monthly Report Overlay Logic (complete, with chart rendering) ---
  const openReportsBtn = document.getElementById('openReportsBtn');
  const reportsOverlay = document.getElementById('reportsOverlay');
  const closeReportsBtn = document.getElementById('closeReportsBtn');
  const reportForm = document.getElementById('reportForm');
  const reportContent = document.getElementById('reportContent');
  const pdfReportContent = document.getElementById('pdfReportContent');
  let reportChart;

  openReportsBtn.onclick = () => {
    reportsOverlay.style.display = "flex";
    pdfReportContent.innerHTML = '<div style="padding:1em;text-align:center;">Select a month and generate!</div>';
  };
  closeReportsBtn.onclick = () => {
    reportsOverlay.style.display = "none";
    if (window.reportCharts) window.reportCharts.forEach((c) => c.destroy());
  };

  reportForm.onsubmit = function (e) {
    e.preventDefault();
    const month = document.getElementById("monthInput").value;
    pdfReportContent.innerHTML = "Loading...";
    fetch(`get_monthly_report.php?month=${month}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          pdfReportContent.innerHTML = "No data for this month.";
          return;
        }
        const totalQty = res.returned_sum + res.disposed_sum;
        const lossPercent =
          totalQty > 0 ? ((res.disposed_sum / totalQty) * 100).toFixed(1) : "0";
        const returnPercent =
          totalQty > 0 ? ((res.returned_sum / totalQty) * 100).toFixed(1) : "0";
        pdfReportContent.innerHTML = `
          <h3 style="margin-bottom:0.3em; color:#204289;">Monthly Financial Impact Trend</h3>
          <canvas id="monthlyTrendBar" width="600" height="200"></canvas>
          <div style="display:flex; gap:28px; flex-wrap:wrap; margin-bottom:26px">
            <div style="background:#f7fafb; border-radius:10px; min-width:175px; padding:1em;">
              <h2 style="margin:0;color:#4CAF50;">${res.returned_sum || 0}</h2>
              <div style="color:#7e7;font-weight:bold;">Returned to Stock</div>
              <div class="subkpi">LKR ${parseFloat(res.returned_impact).toLocaleString(undefined,{minimumFractionDigits:2})}</div>
            </div>
            <div style="background:#fff3f3; border-radius:10px; min-width:175px; padding:1em;">
              <h2 style="margin:0;color:#ef5350;">${res.disposed_sum || 0}</h2>
              <div style="color:#e57373;font-weight:bold;">Disposed (Written Off)</div>
              <div class="subkpi">LKR ${parseFloat(res.disposed_impact).toLocaleString(undefined,{minimumFractionDigits:2})}</div>
            </div>
            <div style="background:#faf7fa; border-radius:10px; min-width:175px; padding:1em;">
              <div style="color:#333;">Return Ratio</div>
              <h2 style="margin:0;color:#4682b4;">${returnPercent}%</h2>
              <div style="color:#aaa;font-size: 0.92em">of all recovered stock</div>
            </div>
            <div style="background:#fff4f2; border-radius:10px; min-width:175px; padding:1em;">
              <div style="color:#333;">Loss Ratio</div>
              <h2 style="margin:0;color:#e57373;">${lossPercent}%</h2>
              <div style="color:#aaa;font-size: 0.92em">of all recovered stock</div>
            </div>
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 32px;">
            <div><canvas id="actionPie" width="230" height="230"></canvas><div style="text-align:center;font-weight:bold;color:#444">Returned vs Disposed (Qty)</div></div>
            <div><canvas id="impactBar" height="230" width="230"></canvas><div style="text-align:center;font-weight:bold;color:#444">Financial Impact by Action</div></div>
            <div><canvas id="prodBar" height="230" width="230"></canvas><div style="text-align:center;font-weight:bold;color:#444">Product Return/Disposal Split</div></div>
          </div>
          <h3 style="margin-top:2em;margin-bottom:.5em; color:#204289;">Detailed Product-wise Flow</h3>
          <table class="data-table" style="width:100%;margin-bottom:1em;">
            <thead>
              <tr>
                <th>Product</th>
                <th style="color:#288943;">Returned Qty</th>
                <th style="color:#c62c2e;">Disposed Qty</th>
                <th style="color:#288943;">Returned Value</th>
                <th style="color:#c62c2e;">Disposed Value</th>
              </tr>
            </thead>
            <tbody>
              ${res.product_breakdown
                .map(
                  (row) => `
                <tr>
                  <td>${row.product}</td>
                  <td style="background:#f6fffa;color:#278a5c;">${row.returned || 0}</td>
                  <td style="background:#fff6f7;color:#e94040;">${row.disposed || 0}</td>
                  <td style="background:#f6fffa;color:#278a5c;">LKR ${parseFloat(row.return_impact).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                  })}</td>
                  <td style="background:#fff6f7;color:#c62c2e;">LKR ${parseFloat(row.dispose_impact).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                  })}</td>
                </tr>
              `
                )
                .join("")}
            </tbody>
          </table>
          <div style="color:#888;padding-bottom:10px;text-align:right;"><small>Note: Red numbers = business loss; Green = cost recovery.</small></div>
        `;

        // Chart.js rendering (must use canvases inside pdfReportContent!)
        const monthlyTrendBarCtx = document.getElementById("monthlyTrendBar").getContext("2d");
        let monthlyTrendBar = new Chart(monthlyTrendBarCtx, {
          type: "bar",
          data: {
            labels: res.impact_labels,
            datasets: [
              {
                label: `${res.month_name}`,
                data: res.impact_selected,
                backgroundColor: "rgba(76,175,80,0.55)",
                borderColor: "#2e7d32"
              },
              {
                label: `${res.prev_month_name}`,
                data: res.impact_prev,
                backgroundColor: "rgba(242,84,96,0.62)",
                borderColor: "#f25460"
              }
            ]
          },
          options: {
            plugins: {
              legend: { position: "top" }
            },
            scales: {
              y: { beginAtZero: true, title: { display: true, text: "Financial Impact (LKR)" } },
              x: { title: { display: true, text: "Day of Month" }, stacked: false }
            },
            interaction: { mode: "index", intersect: false }
          }
        });

        const pieCtx = document.getElementById("actionPie").getContext("2d");
        let pieChart = new Chart(pieCtx, {
          type: "pie",
          data: {
            labels: ["Returned", "Disposed"],
            datasets: [
              {
                data: [res.returned_sum, res.disposed_sum],
                backgroundColor: ["#49c793", "#f25460"],
              },
            ],
          },
          options: {
            plugins: {
              legend: { position: "top" },
              title: { display: false },
            },
          },
        });

        const impactCtx = document.getElementById("impactBar").getContext("2d");
        let impactChart = new Chart(impactCtx, {
          type: "bar",
          data: {
            labels: res.action_data.map((r) => r.action_taken),
            datasets: [
              {
                label: "Financial Impact (LKR)",
                data: res.action_data.map((r) => r.total_impact),
                backgroundColor: res.action_data.map((r) =>
                  r.action_taken.toLowerCase() == "disposed"
                    ? "#f25460"
                    : r.action_taken.toLowerCase() == "returned"
                      ? "#49c793"
                      : "#b0a6f6"
                ),
              },
            ],
          },
          options: {
            plugins: {
              legend: { display: false },
              title: { display: false },
            },
            scales: { y: { beginAtZero: true } },
          },
        });

        const prodCtx = document.getElementById("prodBar").getContext("2d");
        let prodChart = new Chart(prodCtx, {
          type: "bar",
          data: {
            labels: res.product_breakdown.map((r) => r.product),
            datasets: [
              {
                label: "Returned",
                data: res.product_breakdown.map((r) => r.returned),
                backgroundColor: "#49c793",
              },
              {
                label: "Disposed",
                data: res.product_breakdown.map((r) => r.disposed),
                backgroundColor: "#f25460",
              },
            ],
          },
          options: {
            plugins: {
              legend: { position: "top" },
              title: { display: false },
            },
            scales: { y: { beginAtZero: true } },
          },
        });

        window.reportCharts = [monthlyTrendBar, pieChart, impactChart, prodChart];
      })
      .catch((err) => {
        pdfReportContent.innerHTML =
          '<span style="color:red">Error loading report.</span>';
      });
  };

  document.getElementById("downloadPdfBtn").onclick = function () {
    // Only the PDF report content (not buttons/forms)
    html2canvas(document.getElementById('pdfReportContent'), {
      backgroundColor: '#fff',
      scale: 2,
      useCORS: true
    }).then(canvas => {
      const imgData = canvas.toDataURL('image/png');
      const pdf = new window.jspdf.jsPDF({
        orientation: 'p',
        unit: 'mm',
        format: 'a4'
      });
      const pageWidth = pdf.internal.pageSize.getWidth();
      const imgProps = pdf.getImageProperties(imgData);
      const pdfWidth = pageWidth - 16;
      const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
      pdf.addImage(imgData, 'PNG', 8, 10, pdfWidth, pdfHeight);
      pdf.save('Monthly_Report.pdf');
    });
  };

  window.updateDeliveryDetails = updateDeliveryDetails;
});