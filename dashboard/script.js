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

    // Open modal (Add Mode)
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
    }
    function closeModal() {
        modalOverlay.style.display = "none";
        location.reload();
    }
    openBtn.addEventListener("click", openModal);
    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);

    // Delivery details & financial impact update
    function updateDeliveryDetails() {
        const selectedOption = originalDelivery.selectedOptions[0];
        if (!selectedOption || !selectedOption.value) {
            productInput.value = '';
            if (!isEditMode) quantityInput.value = '';
            if (!isEditMode) financialImpactInput.value = '0';
            unitPrice = 0;
            maxQuantity = 1;
            return;
        }
        const productName = selectedOption.getAttribute("data-product");
        const rawQuantityAttr = selectedOption.getAttribute("data-quantity");
        const rawUnitPriceAttr = selectedOption.getAttribute("data-unit-price");
        maxQuantity = parseFloat(rawQuantityAttr) || 1;
        unitPrice = parseFloat(rawUnitPriceAttr) || 0;
        productInput.value = productName || '';
        if (!isEditMode) {
            quantityInput.value = maxQuantity;
            financialImpactInput.value = (unitPrice * maxQuantity).toFixed(2);
        }
    }
    originalDelivery.addEventListener("change", updateDeliveryDetails);

    // Quantity validation & calculation
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

    // Form validation before submit
    form.addEventListener("submit", (e) => {
        const errors = [];
        if (!recoveryDateInput.value) errors.push("Recovery Date is required.");
        if (!originalDelivery.value) errors.push("Original Delivery must be selected.");
        if (!productInput.value.trim()) errors.push("Product is required.");
        if (parseFloat(quantityInput.value) <= 0) errors.push("Quantity must be greater than 0.");
        if (parseFloat(financialImpactInput.value) < 0) errors.push("Financial Impact must be 0 or more.");
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

    // Edit record function
    window.editRecord = function(id) {
        fetch('get_recovery_record.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate edit form (see your backend structure)
                } else {
                    alert('Failed to fetch record data: ' + (data.message || 'Unknown error'));
                }
            });
    };

    // Delete record function
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
            recordIdInput.name = 'recordid';
            recordIdInput.value = id;
            deleteForm.appendChild(recordIdInput);
            document.body.appendChild(deleteForm);
            deleteForm.submit();
        }
    };
});
