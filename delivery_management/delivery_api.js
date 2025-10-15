document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalBackdrop');
  const newBtn = document.getElementById('newDelivery');
  const closeBtn = document.getElementById('closeModal');
  const form = document.getElementById('deliveryForm');
  const saveBtn = document.getElementById('saveDelivery');
  const toast = document.getElementById('toast');
  const tableBody = document.getElementById('deliveryTableBody');
  const statsGrid = document.getElementById('statsGrid');
  const orderSelect = document.getElementById('orderNo');

  newBtn.onclick = () => {
    document.getElementById('modalTitle').textContent = 'New Delivery';
    form.reset();
    document.getElementById('editIndex').value = -1;
    enableAllFormFields();
    modal.classList.add('show');
  };
  closeBtn.onclick = () => {
    modal.classList.remove('show');
    document.getElementById('editIndex').value = -1;
    enableAllFormFields();
  };

  function showToast(msg) {
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2000);
  }

  function disableNonEditableFields() {
    const editable = ['driver', 'scheduledDate', 'actualDate'];
    const els = form.querySelectorAll('input, select, textarea');
    els.forEach(el => {
      if (editable.includes(el.id)) {
        el.removeAttribute('disabled');
      } else {
        el.setAttribute('disabled', 'true');
      }
    });
  }
  
  function enableAllFormFields() {
    const els = form.querySelectorAll('input, select, textarea');
    els.forEach(el => el.removeAttribute('disabled'));
  }

  function populateDriverFilter() {
  const driverFilter = document.getElementById('driverFilter');
  const rows = document.querySelectorAll('#deliveryTableBody tr');
  const drivers = new Set();
  rows.forEach(row => {
    const driverName = row.querySelectorAll('td')[6]?.textContent.trim();
    if (driverName) drivers.add(driverName);
  });
  driverFilter.innerHTML = '<option value="">All</option>';
  drivers.forEach(driver => {
    const option = document.createElement('option');
    option.value = driver;
    option.textContent = driver;
    driverFilter.appendChild(option);
  });
}

  async function loadDeliveries() {
    try {
      const res = await fetch('delivery_api.php?action=list');
      const deliveries = (await res.json()).data || [];
      tableBody.innerHTML = '';
      let stats = { Pending: 0, 'In-transit': 0, Delivered: 0, Returned: 0, Cancelled: 0 };

      deliveries.forEach(d => {
        if (stats[d.delivery_status] !== undefined) stats[d.delivery_status]++;

        // safe status class (replace whitespace chars with hyphen)
        let statusClass = (d.delivery_status || '').toLowerCase().replace(/\s+/g, '-');

        tableBody.innerHTML += `
          <tr data-id="${d.delivery_id}">
            <td>${d.delivery_code || d.order_no || ''}</td>
            <td>${escapeHtml(d.buyer_name)}</td>
            <td>${escapeHtml(d.product_name)}</td>
            <td>${d.quantity || ''}</td>
            <td>${d.scheduled_date || ''}</td>
            <td>${d.actual_date || ''}</td>
            <td>${escapeHtml(d.driver) || ''}</td>
            <td>${escapeHtml(d.address) || ''}</td>
            <td><span class="badge b-${statusClass}">${d.delivery_status}</span></td>
            <td class="actions">
              <button class="action-btn edit"><i class="fas fa-pen"></i></button>
              <button class="action-btn delete"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        `;
      });

      statsGrid.innerHTML = `
        <div class="stat-card"><div class="stat-content"><h3>${deliveries.length}</h3><p>Total</p></div></div>
        <div class="stat-card"><div class="stat-content"><h3>${stats['Pending']}</h3><p>Pending</p></div></div>
        <div class="stat-card"><div class="stat-content"><h3>${stats['In-transit']}</h3><p>In Transit</p></div></div>
        <div class="stat-card"><div class="stat-content"><h3>${stats['Delivered']}</h3><p>Delivered</p></div></div>
        <div class="stat-card"><div class="stat-content"><h3>${stats['Returned']}</h3><p>Returned</p></div></div>
        <div class="stat-card"><div class="stat-content"><h3>${stats['Cancelled']}</h3><p>Cancelled</p></div></div>
      `;
      
      populateDriverFilter();

    } catch (err) {
      console.error('Failed loading deliveries', err);
      showToast('Failed to load deliveries');
    }
  }

  async function loadOrders() {
    try {
      const res = await fetch('delivery_api.php?action=orders');
      const orders = (await res.json()).data || [];
      orderSelect.innerHTML = '<option value="">Select Order</option>';
      orders.forEach(o => {
        orderSelect.innerHTML += `<option value="${o.order_id}">${o.order_id}</option>`;
      });
    } catch (err) {
      console.error('Failed loading orders', err);
    }
  }

  orderSelect.addEventListener('change', async function () {
    const orderNo = this.value;
    if (!orderNo) {
      form.reset();
      return;
    }

    try {
      const resOrder = await fetch(`delivery_api.php?action=orderDetails&order_no=${encodeURIComponent(orderNo)}`);
      if (!resOrder.ok) throw new Error('Network response was not ok');
      const orderData = (await resOrder.json()).data;
      if (!orderData) return;

      document.getElementById('address').value = orderData.order_address || '';
      document.getElementById('quantity').value = orderData.quantity || '';

      const resBuyer = await fetch(`delivery_api.php?action=buyerDetails&buyer_id=${orderData.buyer_id}`);
      if (!resBuyer.ok) throw new Error('Network response was not ok');
      const buyerData = (await resBuyer.json()).data;
      if (buyerData) {
        document.getElementById('buyerName').value = buyerData.buyername || '';
        document.getElementById('city').value = buyerData.b_city || '';
      }

      const resProduct = await fetch(`delivery_api.php?action=productDetails&product_id=${orderData.product_id}`);
      if (!resProduct.ok) throw new Error('Network response was not ok');
      const productData = (await resProduct.json()).data;
      if (productData) {
        document.getElementById('productName').value = productData.product_name || '';
      }
    } catch (err) {
      console.error('Error autofilling order info:', err);
      showToast('Failed to load order details', true);
    }
  });
  
  function escapeHtml(text = '') {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function containsNumber(str) {
    return /\d/.test(str);
  }

  saveBtn.addEventListener('click', async (e) => {
    e.preventDefault();

    const editId = parseInt(document.getElementById('editIndex').value, 10);

    // === UPDATE MODE ===
    if (editId > 0) {
      const driver = document.getElementById('driver').value.trim();
      const scheduled = document.getElementById('scheduledDate').value;
      const actual = document.getElementById('actualDate').value;
      const status = document.getElementById('deliveryStatus').value;

      if (!driver) return showToast('Driver name is required', true);
      if (!scheduled) return showToast('Scheduled date is required', true);
      if (actual && new Date(actual) > new Date(scheduled)) {
        return showToast('Actual date cannot be after scheduled date', true);
      }

      if (containsNumber(driver)) return showToast('Driver name cannot contain numbers', true);

      const formData = new FormData();
      formData.append('action', 'update');
      formData.append('delivery_id', editId);
      formData.append('driver', driver);
      formData.append('scheduledDate', scheduled);
      formData.append('actualDate', actual);
      formData.append('deliveryStatus', status);

      try {
        const res = await fetch('delivery_api.php', { method: 'POST', body: formData });
        if (!res.ok) throw new Error('Network response was not ok');
        const result = await res.json();
        if (result.success) {
          showToast(result.message || 'Updated successfully');
          modal.classList.remove('show');
          form.reset();
          document.getElementById('editIndex').value = 0;
          enableAllFormFields();
          loadDeliveries();
          loadOrders();
        } else {
          showToast(result.message || 'Update failed', true);
        }
      } catch (err) {
        console.error('Update failed', err);
        showToast('Update failed (network error)', true);
      }

      return;
    }

    // === CREATE MODE ===
    const buyer = document.getElementById('buyerName').value.trim();
    const product = document.getElementById('productName').value.trim();
    const qty = parseInt(document.getElementById('quantity').value.trim(), 10);
    const address = document.getElementById('address').value.trim();
    const scheduled = document.getElementById('scheduledDate').value;
    const actual = document.getElementById('actualDate').value;
    const driver = document.getElementById('driver').value.trim();
    const status = document.getElementById('deliveryStatus').value;

    if (!buyer) return showToast('Buyer name is required', true);
    if (!product) return showToast('Product name is required', true);
    if (isNaN(qty) || qty <= 0) return showToast('Quantity must be a positive number', true);
    if (!address) return showToast('Address is required', true);
    if (!scheduled) return showToast('Scheduled date is required', true);
    if (!driver) return showToast('Driver name is required', true);
    if (actual && new Date(actual) > new Date(scheduled)) {
      return showToast('Actual date cannot be after the scheduled date', true);
    }
    if (containsNumber(driver)) return showToast('Driver name cannot contain numbers', true);
    

    const formData = new FormData(form);
    formData.set("deliveryStatus", status);

    try {
      const res = await fetch('delivery_api.php', { method: 'POST', body: formData });
      if (!res.ok) throw new Error('Network response was not ok');
      const result = await res.json();
      if (result.success) {
        showToast(result.message || 'Saved successfully');
        modal.classList.remove('show');
        form.reset();
        document.getElementById('editIndex').value = 0;
        loadDeliveries();
        loadOrders();
      } else {
        showToast(result.message || 'Failed to save delivery', true);
      }
    } catch (err) {
      console.error('Save failed', err);
      showToast('Failed to save (network error)', true);
    }
  });


  // Reset button
  document.getElementById('resetForm').addEventListener('click', () => {
    form.reset();
    document.getElementById('editIndex').value = -1;
    enableAllFormFields();
  });

  tableBody.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('.action-btn.edit');
    const delBtn = e.target.closest('.action-btn.delete');
    if (!editBtn && !delBtn) return;

    const tr = e.target.closest('tr');
    if (!tr) return;
    const id = tr.dataset.id;

    // EDIT
    if (editBtn) {
      try {
        const res = await fetch(`delivery_api.php?action=get&delivery_id=${id}`);
        if (!res.ok) throw new Error('Network response was not ok');
        const delivery = (await res.json()).data;
        if (!delivery) return showToast('Delivery not found', true);

        document.getElementById('modalTitle').textContent = 'Edit Delivery';
        document.getElementById('editIndex').value = id;
        document.getElementById('orderNo').value = delivery.order_no || '';
        document.getElementById('buyerName').value = delivery.buyer_name || '';
        document.getElementById('city').value = delivery.city || '';
        document.getElementById('address').value = delivery.address || '';
        document.getElementById('productName').value = delivery.product_name || '';
        document.getElementById('quantity').value = delivery.quantity || '';
        document.getElementById('driver').value = delivery.driver || '';
        document.getElementById('scheduledDate').value = delivery.scheduled_date || '';
        document.getElementById('actualDate').value = delivery.actual_date || '';
        document.getElementById('deliveryStatus').value = delivery.delivery_status || 'Pending';

        disableNonEditableFields();
        modal.classList.add('show');
      } catch (err) {
        console.error('Failed to load delivery details', err);
        showToast('Failed to load delivery details', true);
      }
      return;
    }

    // DELETE
    if (delBtn) {
      if (!confirm('Are you sure you want to delete this delivery?')) return;

      try {
        const res = await fetch(`delivery_api.php?action=delete&delivery_id=${encodeURIComponent(id)}`);
        if (!res.ok) throw new Error('Network response was not ok');

        const result = await res.json();
        if (result.success) {
          showToast(result.message || 'Deleted successfully');
          tr.remove();
          loadDeliveries();
          loadOrders();
        } else {
          showToast(result.message || 'Failed to delete', true);
        }
      } catch (err) {
        console.error('Delete failed', err);
        showToast('Delete failed (network error)', true);
      }
    }

  });


  loadDeliveries();
  loadOrders();
});