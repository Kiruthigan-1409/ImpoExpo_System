document.addEventListener('DOMContentLoaded', () => {
  const supplierSelect = document.getElementById('supplierSelect');
  const productSelect = document.getElementById('productSelect');
  const productNameInput = document.getElementById('productName');
  const productPriceInput = document.getElementById('productPrice');
  const addImportForm = document.getElementById('addImportForm');

  //Modal handling
  const importModal = document.getElementById('importModal');
  const openBtn = document.getElementById('openModal');
  const closeBtn = document.querySelector('.close-btn');
  const cancelBtn = document.getElementById('cancelBtn');

  const editModal = document.getElementById('editImportModal');
  const closeEditBtn = editModal.querySelector('.close-btn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');

  openBtn.addEventListener('click', () => {
  importModal.style.display = 'flex'; 
  importModal.classList.add('show');
  });
  closeBtn.addEventListener('click', () => {
    importModal.style.display = 'none'; 
    importModal.classList.remove('show');
  });
  cancelBtn.addEventListener('click', () => {
    importModal.style.display = 'none'; 
    importModal.classList.remove('show');
  });

  window.addEventListener('click', e => {
    if (e.target === importModal) importModal.style.display = 'none';
    if (e.target === editModal) editModal.style.display = 'none';
  });

  //Filter products by supplier
  supplierSelect.addEventListener('change', () => {
    const supplierId = supplierSelect.value;

    for (let i = 0; i < productSelect.options.length; i++) {
      const option = productSelect.options[i];
      if (option.value === "") continue; 
      option.style.display = (option.dataset.supplier === supplierId) ? '' : 'none';
    }

    //Reset product selection and inputs
    productSelect.value = '';
    productNameInput.value = '';
    productPriceInput.value = '';
  });

  //Auto-fill product info
  productSelect.addEventListener('change', () => {
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    productNameInput.value = selectedOption.dataset.name || '';
    productPriceInput.value = selectedOption.dataset.price || '';
  });

  //Form validation
  addImportForm.addEventListener('submit',(e) =>{
    const quantity = addImportForm.quantity.value.trim();
    const importDate = addImportForm.import_date.value;
    const arrivalDate = addImportForm.arrival_date.value;
    const expiryDate = addImportForm.expiry.value;

    if (!supplierSelect.value) {
      alert("Please select a supplier.");
      e.preventDefault();
      return;
    }

    if (!productSelect.value) {
      alert("Please select a product.");
      e.preventDefault();
      return;
    }

    if (!quantity || isNaN(quantity) || quantity <= 0) {
      alert("Quantity must be greater than 0.");
      e.preventDefault();
      return;
    }

    if (new Date(arrivalDate) < new Date(importDate)) {
      alert("Arrival date cannot be before import date.");
      e.preventDefault();
      return;
    }

    if (new Date(expiryDate) <= new Date(arrivalDate)) {
      alert("Expiry date must be after arrival date.");
      e.preventDefault();
      return;
    }
  });
  // Edit Import modal opening/filling logic
  document.querySelectorAll('.action-btn.edit').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.preventDefault();
      const form = btn.closest('form');
      const importId = form.querySelector("input[name='edit']").value;
      const response = await fetch('save_import.php?action=get&id=' + importId);
      const data = await response.json();
      if (data.success) {
        const imp = data.import;
        document.getElementById('edit_update_import').value = imp.import_id;
        document.getElementById('edit_import_ref').value = imp.import_ref;
        document.getElementById('edit_supplierSelect').value = imp.supplier_id;
        document.getElementById('edit_productSelect').value = imp.product_id;
        document.getElementById('edit_quantity').value = imp.quantity; // from stock table
        document.getElementById('edit_import_date').value = imp.import_date;
        document.getElementById('edit_arrival_date').value = imp.stock_arrival; // from joined alias
        document.getElementById('edit_expiry').value = imp.stock_expiry; // from joined alias
        document.getElementById('edit_remarks').value = imp.remarks;
        document.getElementById('editImportModal').style.display = 'block';
      } else {
        alert('Error fetching import for edit.');
      }
    });
  });

  // --- Close / Cancel Edit Import Modal ---
  closeEditBtn.addEventListener('click', () => {
    editModal.style.display = 'none';
  });
  cancelEditBtn.addEventListener('click', () => {
    editModal.style.display = 'none';
  });

  // --- Live Search & Filters ---
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
  // Clear all filter inputs
  searchInput.value = '';
  productFilter.value = '';
  supplierFilter.value = '';
  arrivalFrom.value = '';
  arrivalTo.value = '';

  // Show all table rows
  Array.from(dataTable.rows).forEach(row => {
    row.style.display = '';
  });

  // Remove "No matching data" row if present
  const noDataRow = document.getElementById('noDataRow');
  if (noDataRow) noDataRow.remove();
});


  // Attach events
  searchInput.addEventListener('input', filterTable);
  productFilter.addEventListener('change', filterTable);
  supplierFilter.addEventListener('change', filterTable);
  arrivalFrom.addEventListener('change', filterTable);
  arrivalTo.addEventListener('change', filterTable);
  
});
