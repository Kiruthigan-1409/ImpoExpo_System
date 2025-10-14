document.addEventListener('DOMContentLoaded', () => {
  // add imports
  const supplierSelect = document.getElementById('supplierSelect');
  const productSelect = document.getElementById('productSelect');
  const productNameInput = document.getElementById('productName');
  const productPriceInput = document.getElementById('productPrice');
  const addImportForm = document.getElementById('addImportForm');

  // Modal Handling
  const importModal = document.getElementById('importModal');
  const openBtn = document.getElementById('openModal');
  const closeBtn = document.querySelector('.close-btn');
  const cancelBtn = document.getElementById('cancelBtn');

  const editModal = document.getElementById('editImportModal');
  const closeEditBtn = editModal.querySelector('.close-btn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');

  openBtn.addEventListener('click', () => {
    importModal.style.display = 'flex'; 
  });
  closeBtn.addEventListener('click', () => importModal.style.display = 'none');
  cancelBtn.addEventListener('click', () => importModal.style.display = 'none');

  closeEditBtn.addEventListener('click', () => editModal.style.display = 'none');
  cancelEditBtn.addEventListener('click', () => editModal.style.display = 'none');

  window.addEventListener('click', e => {
    if (e.target === importModal) importModal.style.display = 'none';
    if (e.target === editModal) editModal.style.display = 'none';
  });

  // Filter Products by Supplier (Add Modal)
  supplierSelect.addEventListener('change', () => {
    const supplierId = supplierSelect.value;

    Array.from(productSelect.options).forEach(option => {
      if (!option.value) return; // skip placeholder
      option.style.display = option.dataset.supplier === supplierId ? '' : 'none';
    });

    productSelect.value = '';
    productNameInput.value = '';
    productPriceInput.value = '';
  });

  // Auto-fill Product Info (Add Modal)
  productSelect.addEventListener('change', () => {
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    productNameInput.value = selectedOption.dataset.name || '';
    productPriceInput.value = selectedOption.dataset.price || '';
  });

  // Add Import Form Validation
  addImportForm.addEventListener('submit', (e) => {
    const quantity = addImportForm.quantity.value.trim();
    const importDate = addImportForm.import_date.value;
    const arrivalDate = addImportForm.arrival_date.value;
    const expiryDate = addImportForm.expiry.value;

    if (!supplierSelect.value) { alert("Please select a supplier."); e.preventDefault(); return; }
    if (!productSelect.value) { alert("Please select a product."); e.preventDefault(); return; }
    if (!quantity || isNaN(quantity) || quantity <= 0) { alert("Quantity must be greater than 0."); e.preventDefault(); return; }
    if (new Date(arrivalDate) < new Date(importDate)) { alert("Arrival date cannot be before import date."); e.preventDefault(); return; }
    if (new Date(expiryDate) <= new Date(arrivalDate)) { alert("Expiry date must be after arrival date."); e.preventDefault(); return; }
  });

  // Edit Import Modal Opening / Filling
  document.querySelectorAll('.action-btn.edit').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const form = btn.closest('form');
      const importId = form.querySelector("input[name='edit']").value;

      const response = await fetch('save_import.php?action=get&id=' + importId);
      const data = await response.json();

      if (!data.success) { alert('Error fetching import for edit.'); return; }

      const imp = data.import;
      const editSupplierSelect = document.getElementById('edit_supplier_id');
      const editProductSelect = document.getElementById('edit_product_id');

      document.getElementById('edit_update_import').value = imp.import_id;
      document.getElementById('edit_import_ref').value = imp.import_ref;
      editSupplierSelect.value = imp.supplier_id;

      // Trigger change to filter products based on supplier
      editSupplierSelect.dispatchEvent(new Event('change'));

      // Pre-select product after filtering
      editProductSelect.value = imp.product_id;

      document.getElementById('edit_quantity').value = imp.quantity;
      document.getElementById('edit_import_date').value = imp.import_date;
      document.getElementById('edit_arrival_date').value = imp.stock_arrival;
      document.getElementById('edit_expiry').value = imp.stock_expiry;
      document.getElementById('edit_remarks').value = imp.remarks;

      editModal.style.display = 'flex';
    });
  });

  // Filter Products by Supplier (Edit Modal)
  const editSupplierSelect = document.getElementById('edit_supplier_id');
  const editProductSelect = document.getElementById('edit_product_id');

  editSupplierSelect.addEventListener('change', () => {
    const supplierId = editSupplierSelect.value;

    Array.from(editProductSelect.options).forEach(option => {
      if (!option.value) return;
      option.style.display = option.dataset.supplier === supplierId ? '' : 'none';
    });

    editProductSelect.value = '';
  });

  //search and filters
  const searchInput = document.getElementById('searchInput');
  const productFilter = document.getElementById('productFilter');
  const supplierFilter = document.getElementById('supplierFilter');
  const arrivalFrom = document.getElementById('arrivalDateFrom');
  const arrivalTo = document.getElementById('arrivalDateTo');
  const dataTable = document.querySelector('.data-table tbody');

  function filterTable() {
    const searchText = searchInput.value.toLowerCase();
    const productText = productFilter.value.toLowerCase();
    const supplierText = supplierFilter.value.toLowerCase();
    const fromDate = arrivalFrom.value ? new Date(arrivalFrom.value) : null;
    const toDate = arrivalTo.value ? new Date(arrivalTo.value) : null;

    Array.from(dataTable.rows).forEach(row => {
      const reference = row.cells[0].textContent.toLowerCase();
      const supplier = row.cells[1].textContent.toLowerCase();
      const product = row.cells[2].textContent.toLowerCase();
      const arrivalDate = new Date(row.cells[5].textContent);

      const matchesSearch = reference.includes(searchText);
      const matchesProduct = !productText || product.includes(productText);
      const matchesSupplier = !supplierText || supplier.includes(supplierText);
      const matchesDate = (!fromDate || arrivalDate >= fromDate) && (!toDate || arrivalDate <= toDate);

      row.style.display = (matchesSearch && matchesProduct && matchesSupplier && matchesDate) ? '' : 'none';
    });
  }

  const refreshBtn = document.getElementById('refreshBtn');
  refreshBtn.addEventListener('click', () => {
    searchInput.value = '';
    productFilter.value = '';
    supplierFilter.value = '';
    arrivalFrom.value = '';
    arrivalTo.value = '';

    Array.from(dataTable.rows).forEach(row => row.style.display = '');
    const noDataRow = document.getElementById('noDataRow');
    if (noDataRow) noDataRow.remove();
  });

  searchInput.addEventListener('input', filterTable);
  productFilter.addEventListener('change', filterTable);
  supplierFilter.addEventListener('change', filterTable);
  arrivalFrom.addEventListener('change', filterTable);
  arrivalTo.addEventListener('change', filterTable);

  // Report Modal
  const reportModal = document.getElementById('reportModal');
  const reportBtn = document.querySelector('.btn-secondary'); // Assuming the first Reports button
  const closeReportBtn = reportModal.querySelector('.close-btn');

  reportBtn.addEventListener('click', () => {
    reportModal.style.display = 'flex';
  });

  closeReportBtn.addEventListener('click', () => {
    reportModal.style.display = 'none';
  });

  window.addEventListener('click', e => {
    if(e.target === reportModal) reportModal.style.display = 'none';
  });


  document.getElementById('generateReportBtn').addEventListener('click', () => {
    const start = document.getElementById('reportFrom').value;
    const end   = document.getElementById('reportTo').value;

    if(!start || !end) return alert("Select both dates!");

    fetch('report_api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `start_date=${start}&end_date=${end}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            document.getElementById('reportSection').style.display = 'grid';

            // Example: show total orders
            document.getElementById('totalOrders').textContent = data.rows.length;

            // TODO: Render charts dynamically
            renderPieChart('productChart', data.productCount);
            renderBarChart('revenueChart', data.revenueByProduct);
            renderPieChart('statusChart', data.statusCount);
        }
    });
});


});
