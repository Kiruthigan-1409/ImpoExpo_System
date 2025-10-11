document.addEventListener("DOMContentLoaded", function () {

  if (typeof Chart !== "undefined" && typeof ChartDataLabels !== "undefined") {
  Chart.register(ChartDataLabels);
  }

  // ====== Elements ======
  const tableBody = document.getElementById("paymentsTable");

  // Create Modal
  const modal = document.getElementById("paymentModal");
  const form = document.getElementById("paymentForm");
  const newPaymentBtn = document.getElementById("newPaymentBtn");
  const closeModal = document.getElementById("closeModal");
  const cancelForm = document.getElementById("cancelForm");
  const referenceInput = document.getElementById("payment_reference");
  const methodSelect = document.getElementById("payment_method");
  const bankRefField = document.getElementById("bankRefField");
  const chequeRefField = document.getElementById("chequeRefField");
  const buyerSelect = document.getElementById("buyer_id");

  // Filters
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const methodFilter = document.getElementById("methodFilter");
  const buyerFilter = document.getElementById("buyerFilter");
  const timeFilter = document.getElementById("timeFilter");

  // Stats
  const elTotalRevenue = document.getElementById("totalRevenue");
  const elMonthlyRevenue = document.getElementById("monthlyRevenue");
  const elPendingRevenue = document.getElementById("pendingRevenue");
  const elCompletedCount = document.getElementById("completedCount");
  const elPendingCount = document.getElementById("pendingCount");
  const elFailedCount = document.getElementById("failedCount");

  let ALL_PAYMENTS = [];

  // ====== Utilities ======
  const fmtLKR = (n) => `LKR ${Number(n || 0).toLocaleString()}`;
  const cap = (s) =>
    s ? s.toString().replace(/_/g, " ").replace(/^\w/, (c) => c.toUpperCase()) : "";

  // ====== Fetch next reference ======
  function fetchNextReference() {
    return fetch("backend/fetch_next_reference.php")
      .then((res) => res.json())
      .then((data) => {
        referenceInput.value = data.nextRef || "";
      });
  }

  // ====== Render Table Row ======
  function renderRow(row) {
    return `
      <tr>
        <td>${row.payment_reference}</td>
        <td>${row.buyer_name || "N/A"}</td>
        <td class="amount">${fmtLKR(row.amount)}</td>
        <td><span class="method-badge method-${row.payment_method}">${cap(
      row.payment_method
    )}</span></td>
        <td>${row.payment_date}</td>
        <td>${row.delivery_description || "N/A"}</td>
        <td><span class="status-badge status-${row.status}">${cap(
      row.status
    )}</span></td>
        <td>
          <button class="action-btn edit" data-id="${row.payment_reference}">
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button class="action-btn delete" data-id="${row.payment_reference}">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </td>
      </tr>
    `;
  }

  // ====== Load Stats ======
  function loadStats(payments) {
    const now = new Date();
    const curMonth = now.getMonth();
    const curYear = now.getFullYear();
    let total = 0,
      monthly = 0,
      pendingAmt = 0,
      completed = 0,
      pending = 0,
      failed = 0;

    payments.forEach((p) => {
      const amt = Number(p.amount) || 0;
      total += amt;
      const d = new Date(p.payment_date);
      const inThisMonth = d && d.getMonth() === curMonth && d.getFullYear() === curYear;

      if (p.status === "completed") {
        completed++;
        if (inThisMonth) monthly += amt;
      } else if (p.status === "pending") {
        pending++;
        pendingAmt += amt;
      } else if (p.status === "failed" || p.status === "refunded") {
        failed++;
      }
    });

    elTotalRevenue.textContent = fmtLKR(total);
    elMonthlyRevenue.textContent = fmtLKR(monthly);
    elPendingRevenue.textContent = fmtLKR(pendingAmt);
    elCompletedCount.textContent = completed;
    elPendingCount.textContent = pending;
    elFailedCount.textContent = failed;
  }

  // ====== Apply Filters ======
  function applyFilters() {
    const search = searchInput ? searchInput.value.toLowerCase() : "";
    const status = statusFilter ? statusFilter.value.toLowerCase() : "";
    const method = methodFilter ? methodFilter.value.toLowerCase() : "";
    const buyer = buyerFilter ? buyerFilter.value : "";
    const time = timeFilter ? timeFilter.value : "all";

    let filtered = [...ALL_PAYMENTS];

    // Search (by reference or buyer name)
    if (search) {
      filtered = filtered.filter(
        (p) =>
          p.payment_reference.toLowerCase().includes(search) ||
          (p.buyer_name && p.buyer_name.toLowerCase().includes(search))
      );
    }

    // Status filter
    if (status) {
      filtered = filtered.filter((p) => p.status.toLowerCase() === status);
    }

    // Method filter
    if (method) {
      filtered = filtered.filter((p) => p.payment_method.toLowerCase() === method);
    }

    // Buyer filter
    if (buyer) {
      filtered = filtered.filter((p) => String(p.buyer_id) === String(buyer));
    }

    // Time filter
    if (time !== "all") {
      const now = new Date();
      filtered = filtered.filter((p) => {
        const d = new Date(p.payment_date);
        if (time === "month") {
          return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
        }
        if (time === "year") {
          return d.getFullYear() === now.getFullYear();
        }
        return true;
      });
    }

    paintTable(filtered);
  }

  // ====== Paint Table ======
  function paintTable(data) {
    tableBody.innerHTML = "";
    if (!data.length) {
      tableBody.innerHTML =
        '<tr><td colspan="8" style="text-align:center">No payments found</td></tr>';
      loadStats([]);
      return;
    }
    data.forEach((row) => (tableBody.innerHTML += renderRow(row)));
    loadStats(data);
  }

  // ====== Load Payments ======
  function loadPayments() {
    fetch("backend/fetch_payments.php")
      .then((res) => res.json())
      .then((data) => {
        ALL_PAYMENTS = Array.isArray(data) ? data : [];
        applyFilters();
      })
      .catch((err) => console.error("Error loading payments:", err));
  }

  // ====== Load Buyers ======
  function loadBuyers() {
    fetch("backend/fetch_buyers.php")
      .then((res) => res.json())
      .then((buyers) => {
        if (buyerFilter) {
          buyerFilter.innerHTML = `<option value="">All Buyers</option>`;
          buyers.forEach((b) => {
            const opt = document.createElement("option");
            opt.value = b.buyer_id;
            opt.textContent = b.buyername;
            buyerFilter.appendChild(opt);
          });
        }

        if (buyerSelect) {
          buyerSelect.innerHTML = `<option value="">Select buyer</option>`;
          buyers.forEach((b) => {
            const opt = document.createElement("option");
            opt.value = b.buyer_id;
            opt.textContent = b.buyername;
            buyerSelect.appendChild(opt);
          });
        }
      })
      .catch((e) => console.error("Error loading buyers:", e));
  }

  // ====== Create Modal Handlers ======
  newPaymentBtn.addEventListener("click", () => {
    modal.classList.remove("hidden");
    form.reset();
    form.querySelector('input[name="amount"]').value = "0";
    fetchNextReference();
    loadBuyers();
    bankRefField.classList.add("hidden");
    chequeRefField.classList.add("hidden");
  });
  closeModal.addEventListener("click", () => modal.classList.add("hidden"));
  cancelForm.addEventListener("click", () => modal.classList.add("hidden"));

  methodSelect.addEventListener("change", () => {
    bankRefField.classList.add("hidden");
    chequeRefField.classList.add("hidden");
    if (methodSelect.value === "bank_transfer") bankRefField.classList.remove("hidden");
    if (methodSelect.value === "cheque") chequeRefField.classList.remove("hidden");
  });

  // ====== Submit new payment ======
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = Object.fromEntries(new FormData(form).entries());
    fetch("backend/add_payment.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(formData),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          alert("Payment saved: " + (data.payment_reference || referenceInput.value));
          modal.classList.add("hidden");
          loadPayments();
        } else {
          alert("Error: " + (data.error || "Unknown error"));
        }
      })
      .catch((err) => alert("Network error: " + err.message));
  });

  // ====== Delete Payment ======
  tableBody.addEventListener("click", function (e) {
    const btn = e.target.closest(".delete");
    if (!btn) return;
    const id = btn.dataset.id;
    if (!id) return;
    if (!confirm("Are you sure you want to delete this payment?")) return;

    fetch("backend/delete_payment.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) loadPayments();
        else alert("Delete failed: " + (data.error || "Unknown error"));
      })
      .catch((err) => alert("Network error: " + err.message));
  });

  // ====== Attach Filter Listeners ======
  if (searchInput) searchInput.addEventListener("input", applyFilters);
  if (statusFilter) statusFilter.addEventListener("change", applyFilters);
  if (methodFilter) methodFilter.addEventListener("change", applyFilters);
  if (buyerFilter) buyerFilter.addEventListener("change", applyFilters);
  if (timeFilter) timeFilter.addEventListener("change", applyFilters);

  // ====== Edit Modal ======
  const editModal = document.getElementById("editPaymentModal");
  const editForm = document.getElementById("editPaymentForm");
  const closeEditModalBtn = document.getElementById("closeEditModal");
  const cancelEditBtn = document.getElementById("cancelEditForm");
  const paymentMethodSelect = document.getElementById("edit_payment_method");
  const bankRefFieldEdit = document.getElementById("editBankRefField");
  const chequeRefFieldEdit = document.getElementById("editChequeRefField");

  function toggleExtraFields() {
    const method = paymentMethodSelect.value;
    bankRefFieldEdit.classList.toggle("hidden", method !== "bank_transfer");
    chequeRefFieldEdit.classList.toggle("hidden", method !== "cheque");
  }

  paymentMethodSelect.addEventListener("change", toggleExtraFields);

  tableBody.addEventListener("click", async function (e) {
    const btn = e.target.closest(".action-btn.edit");
    if (!btn) return;
    const paymentRef = btn.dataset.id;

    try {
      const res = await fetch(`backend/get_payment.php?id=${paymentRef}`);
      const data = await res.json();
      if (!data.success) {
        alert(data.error || "Failed to fetch payment details.");
        return;
      }

      document.getElementById("edit_payment_reference").value = data.payment_reference;
      document.getElementById("edit_payment_date").value = data.payment_date;
      document.getElementById("edit_amount").value = data.amount;
      document.getElementById("edit_payment_method").value = data.payment_method;
      document.getElementById("edit_status").value = data.status;
      document.getElementById("edit_notes").value = data.notes || "";
      document.getElementById("edit_bank_reference").value = data.bank_reference || "";
      document.getElementById("edit_cheque_reference").value = data.cheque_reference || "";

      const buyerSelectEdit = document.getElementById("edit_buyer_id");
      buyerSelectEdit.innerHTML = "";
      const buyersRes = await fetch("backend/fetch_buyers.php");
      const buyers = await buyersRes.json();
      buyers.forEach((b) => {
        const option = document.createElement("option");
        option.value = b.buyer_id;
        option.textContent = b.buyername;
        if (b.buyer_id == data.buyer_id) option.selected = true;
        buyerSelectEdit.appendChild(option);
      });

      const deliverySelect = document.getElementById("edit_delivery_id");
      deliverySelect.innerHTML = "";
      if (data.delivery_id) {
        const option = document.createElement("option");
        option.value = data.delivery_id;
        option.textContent = `Delivery ${data.delivery_id}`;
        option.selected = true;
        deliverySelect.appendChild(option);
      }

      toggleExtraFields();
      editModal.classList.remove("hidden");
    } catch (err) {
      console.error(err);
      alert("Error fetching payment data.");
    }
  });

  closeEditModalBtn.addEventListener("click", () => editModal.classList.add("hidden"));
  cancelEditBtn.addEventListener("click", () => editModal.classList.add("hidden"));

  editForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = {
      payment_reference: document.getElementById("edit_payment_reference").value,
      payment_date: document.getElementById("edit_payment_date").value,
      buyer_id: document.getElementById("edit_buyer_id").value,
      delivery_id: document.getElementById("edit_delivery_id").value,
      amount: document.getElementById("edit_amount").value,
      payment_method: document.getElementById("edit_payment_method").value,
      status: document.getElementById("edit_status").value,
      bank_reference: document.getElementById("edit_bank_reference").value,
      cheque_reference: document.getElementById("edit_cheque_reference").value,
      notes: document.getElementById("edit_notes").value,
    };

    try {
      const res = await fetch("backend/update_payment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });
      const result = await res.json();
      if (result.success) {
        alert("Payment updated successfully!");
        editModal.classList.add("hidden");
        loadPayments();
      } else {
        alert(result.error || "Update failed!");
      }
    } catch (err) {
      console.error(err);
      alert("Error updating payment.");
    }
  });

  // ====== Report Modal ======
  const reportModal = document.getElementById("reportModal");
  const reportForm = document.getElementById("reportForm");
  const openReportBtn = document.getElementById("monthlyReportBtn");
  const closeReportModal = document.getElementById("closeReportModal");
  const cancelReportBtn = document.getElementById("cancelReportBtn");
  const generateExcelBtn = document.getElementById("generateExcelBtn");
  const generatePdfBtn = document.getElementById("generatePdfBtn");

  // Open modal
  if (openReportBtn) {
    openReportBtn.addEventListener("click", () => {
      reportModal.classList.remove("hidden");
    });
  }

  // Close modal
  if (closeReportModal) closeReportModal.addEventListener("click", () => reportModal.classList.add("hidden"));
  if (cancelReportBtn) cancelReportBtn.addEventListener("click", () => reportModal.classList.add("hidden"));

  // Collect form data
  function getReportOptions() {
    const formData = new FormData(reportForm);
    return {
      period: formData.get("period"),
      charts: formData.getAll("charts[]"),
    };
  }

  // Generate Excel (unchanged)
  if (generateExcelBtn) {
    generateExcelBtn.addEventListener("click", () => {
      const opts = getReportOptions();
      window.location.href = `backend/generate_excel.php?period=${opts.period}`;
    });
  }

  // Helpers to aggregate data for charts
  function filterPaymentsByPeriod(payments, period) {
    if (period === "month") {
      const now = new Date();
      return payments.filter(p => {
        const d = new Date(p.payment_date);
        return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
      });
    } else if (period === "year") {
      const now = new Date();
      return payments.filter(p => {
        const d = new Date(p.payment_date);
        return d.getFullYear() === now.getFullYear();
      });
    }
    return payments.slice();
  }

  function aggregateByMethod(payments) {
    const map = {};
    payments.forEach(p => {
      const m = (p.payment_method || "unknown").toString();
      map[m] = (map[m] || 0) + (Number(p.amount) || 0);
    });
    const labels = Object.keys(map).map(cap);
    const data = Object.keys(map).map(k => map[k]);
    return { labels, data };
  }

  function aggregateByMonth(payments) {
    const map = {};
    payments.forEach(p => {
      const d = new Date(p.payment_date);
      if (isNaN(d)) return;
      const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`; // YYYY-MM
      map[key] = (map[key] || 0) + (Number(p.amount) || 0);
    });
    const keys = Object.keys(map).sort();
    const labels = keys.map(k => {
      const [y, m] = k.split("-");
      const date = new Date(Number(y), Number(m) - 1, 1);
      return date.toLocaleString(undefined, { month: "short", year: "numeric" });
    });
    const data = keys.map(k => map[k]);
    return { labels, data };
  }

    // Render chart to dataURL (JPEG) — ensures canvas has explicit size and white background
    // === Helper: render chart to DataURL ===
  async function renderChartToDataURL(config, width = 1200, height = 800) {
    if (typeof Chart === "undefined") {
      throw new Error("Chart.js not loaded. Add <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>");
    }

    return new Promise((resolve) => {
      const canvas = document.createElement("canvas");
      canvas.width = width;
      canvas.height = height;

      const ctx = canvas.getContext("2d");
      ctx.fillStyle = "white"; // force white background
      ctx.fillRect(0, 0, width, height);

      // deep clone config
      const cfg = JSON.parse(JSON.stringify(config));
      cfg.options = cfg.options || {};
      cfg.options.responsive = false;
      cfg.options.maintainAspectRatio = false;
      cfg.options.animation = false;

      // force black text
      cfg.options.plugins = cfg.options.plugins || {};
      if (!cfg.options.plugins.legend) cfg.options.plugins.legend = {};
      cfg.options.plugins.legend.labels = { color: "black" };

      if (cfg.options.scales) {
        Object.keys(cfg.options.scales).forEach(axis => {
          cfg.options.scales[axis].ticks = { color: "black" };
        });
      }

      Chart.register(ChartDataLabels);

      // create chart
      new Chart(ctx, cfg);

      setTimeout(() => {
        try {
          resolve(canvas.toDataURL("image/png")); // PNG ensures quality + transparency
        } catch (err) {
          console.error("Canvas export error:", err);
          resolve(null);
        }
      }, 150);
    });
  }

  // === Generate PDF with Charts ===
  if (generatePdfBtn) {
    generatePdfBtn.addEventListener("click", async () => {
      try {
        const opts = getReportOptions();
        const paymentsForCharts = filterPaymentsByPeriod(ALL_PAYMENTS, opts.period);

        let chartImages = {};

        // Pie chart
        if (opts.charts.includes("pie")) {
          const agg = aggregateByMethod(paymentsForCharts);
          if (agg.labels.length > 0) {
            const totalSum = agg.data.reduce((a, b) => a + b, 0);

            const pieConfig = {
              type: "pie",
              data: {
                labels: agg.labels,
                datasets: [{
                  data: agg.data,
                  backgroundColor: ["#FF9999", "#66B2FF", "#99FF99", "#FFD580"], // lighter colors
                  borderColor: "#fff",
                  borderWidth: 2
                }]
              },
              options: {
                plugins: {
                  legend: { position: "bottom", labels: { color: "#000" } },
                  datalabels: {
                    color: "#000",
                    font: { weight: "bold", size: 14 },
                    formatter: (value, ctx) => {
                      let total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                      let percentage = (value / total * 100).toFixed(1);
                      let formattedValue = value.toLocaleString("en-LK", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                      });
                      return `LKR ${formattedValue}\n(${percentage}%)`;
                    }
                  }
                }
              }
            };
            chartImages.pie = await renderChartToDataURL(pieConfig, 1200, 700);
          }
        }



        // Bar chart
        if (opts.charts.includes("bar")) {
          const agg = aggregateByMonth(paymentsForCharts);
          if (agg.labels.length > 0) {
            const barConfig = {
              type: "bar",
              data: {
                labels: agg.labels,
                datasets: [{
                  label: "Revenue",
                  data: agg.data,
                  backgroundColor: "#66B2FF", // light blue
                  borderColor: "#000",
                  borderWidth: 1
                }]
              },
              options: {
                scales: {
                  x: { ticks: { color: "#000" } },
                  y: { beginAtZero: true, ticks: { color: "#000" } }
                },
                plugins: {
                  legend: { display: false },
                  datalabels: {
                    anchor: "end",
                    align: "top",
                    color: "#000",
                    font: { weight: "bold", size: 12 },
                    formatter: (value) =>
                      "LKR " + value.toLocaleString("en-LK", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                      })
                  }
                }
              }
            };
            chartImages.bar = await renderChartToDataURL(barConfig, 1400, 700);
          }
        }

        // Send to backend
        const payload = { period: opts.period, charts: {} };
        Object.keys(chartImages).forEach(k => {
          if (chartImages[k]) payload.charts[k] = chartImages[k];
        });

        const res = await fetch("backend/generate_pdf.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });

        if (!res.ok) {
          const text = await res.text();
          console.error("Server error:", text);
          alert("Server error while generating PDF.");
          return;
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "Report.pdf";
        document.body.appendChild(a);
        a.click();
        a.remove();
      } catch (err) {
        console.error("Error generating PDF:", err);
        alert("Error generating PDF: " + (err.message || err));
      }
    });
  }


  // ====== INIT ======
  loadPayments();
  loadBuyers();
});
