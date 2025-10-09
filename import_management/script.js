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
    if(e.target === importModal) importModal.style.display = 'none'; 
  });

  // Handle Edit button
document.querySelectorAll('.action-btn.edit').forEach(button => {
  button.addEventListener('click', async () => {
    const importId = button.getAttribute('data-id');

    const response = await fetch(`save_import.php?action=get&id=${importId}`);
    const data = await response.json();

    if (data.success) {
      document.getElementById('import_id').value = data.import.import_id;
      document.getElementById('import_ref').value = data.import.import_ref;
      document.getElementById('supplierSelect').value = data.import.supplier_id;

      // Trigger product filtering
      supplierSelect.dispatchEvent(new Event('change'));

      document.getElementById('productSelect').value = data.import.product_id;
      document.getElementById('quantity').value = data.import.quantity;
      document.getElementById('import_date').value = data.import.import_date;
      document.getElementById('arrival_date').value = data.import.arrival_date;
      document.getElementById('expiry').value = data.import.expiry_date;
      document.getElementById('remarks').value = data.import.remarks;

      importModal.style.display = 'flex';
      importModal.classList.add('show');
    } else {
      alert('Error loading import details.');
    }
  });
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
});
