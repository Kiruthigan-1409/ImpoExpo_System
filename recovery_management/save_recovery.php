<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mode = $_POST['mode'] ?? 'add';
    $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;

    $conn->begin_transaction();

    try {
        // ====== DELETE LOGIC ======
        if ($mode === 'delete') {
            if ($recordId <= 0) throw new Exception("Invalid record ID for deletion.");

            // Get recovery record details with stock_id
            $stmt = $conn->prepare("SELECT product, quantity, action_taken, stock_id FROM recovery_records WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$record = $result->fetch_assoc()) throw new Exception("Record not found.");
            $stmt->close();

            // Reduce stock if action_taken was Returned
            if (strtolower($record['action_taken']) === 'returned' && (int)$record['quantity'] > 0 && $record['stock_id']) {
                $updateStock = $conn->prepare("UPDATE stock SET quantity = GREATEST(0, quantity - ?) WHERE stock_id = ?");
                $updateStock->bind_param("ii", $record['quantity'], $record['stock_id']);
                $updateStock->execute();
                $updateStock->close();
            }

            // Delete the recovery record
            $delStmt = $conn->prepare("DELETE FROM recovery_records WHERE id = ? LIMIT 1");
            $delStmt->bind_param("i", $recordId);
            if (!$delStmt->execute()) throw new Exception("Failed to delete record.");
            $delStmt->close();

            $conn->commit();
            echo "<script>alert('Recovery record deleted successfully.'); window.location.href='index.php';</script>";
            exit;
        }

        // ========== ADD / EDIT LOGIC ============
        $recoveryRef = trim($_POST['recoveryRef'] ?? '');
        $recoveryDate = trim($_POST['recoveryDate'] ?? '');
        $product = trim($_POST['product'] ?? '');
        $originalDelivery = trim($_POST['originalDelivery'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $itemCondition = trim($_POST['itemCondition'] ?? '');
        $actionTaken = trim($_POST['actionTaken'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $recoveryQty = (int)($_POST['quantity'] ?? 0);

        // --- Validation ---
        $errors = [];
        if (empty($recoveryRef)) $errors[] = "Recovery reference is required.";
        if (empty($recoveryDate)) $errors[] = "Recovery date is required.";
        if (empty($originalDelivery)) $errors[] = "Original delivery must be selected.";
        if (empty($product)) $errors[] = "Product is required.";
        if ($recoveryQty <= 0) $errors[] = "Quantity must be greater than 0.";
        if (empty($reason)) $errors[] = "Reason must be selected.";
        if (empty($itemCondition)) $errors[] = "Item condition must be selected.";
        if (empty($actionTaken)) $errors[] = "Action taken must be selected.";
        if (!empty($notes) && strlen($notes) > 500) {
            $notes = substr($notes, 0, 500);
            $errors[] = "Notes truncated to 500 characters.";
        }

        if (count($errors) > 0) {
            echo "<script>alert('Errors:\\n- " . implode("\\n- ", $errors) . "'); window.history.back();</script>";
            exit;
        }

        // Get delivery details & unit price & product_id
        $delStmt = $conn->prepare("SELECT d.quantity AS delivery_quantity, p.price_per_kg, p.product_id
                                   FROM deliveries d
                                   LEFT JOIN order_table o ON d.order_no = o.order_id
                                   LEFT JOIN products p ON o.product_id = p.product_id
                                   WHERE d.delivery_code = ? LIMIT 1");
        $delStmt->bind_param("s", $originalDelivery);
        $delStmt->execute();
        $delResult = $delStmt->get_result();
        if (!$deliveryRow = $delResult->fetch_assoc()) throw new Exception("Original delivery not found.");
        $deliveryQuantity = (int)$deliveryRow['delivery_quantity'];
        $unitPrice = (float)($deliveryRow['price_per_kg'] ?? 0);
        $productId = (int)($deliveryRow['product_id'] ?? 0);
        $delStmt->close();

        if ($recoveryQty > $deliveryQuantity) throw new Exception("Recovery quantity ($recoveryQty) cannot exceed delivery quantity ($deliveryQuantity).");

        $financialImpact = $recoveryQty * $unitPrice;

        // ========== ADD LOGIC ==========
        if ($mode === 'add') {
            $stockId = null;
            if (strtolower($actionTaken) === 'returned' && $recoveryQty > 0 && $productId > 0) {
                // Find nearest expiry batch
                $stockStmt = $conn->prepare("SELECT stock_id FROM stock WHERE product_id = ? ORDER BY expiry_date ASC LIMIT 1");
                $stockStmt->bind_param("i", $productId);
                $stockStmt->execute();
                $stockResult = $stockStmt->get_result();
                if ($stockRow = $stockResult->fetch_assoc()) {
                    $stockId = $stockRow['stock_id'];
                    $updateStock = $conn->prepare("UPDATE stock SET quantity = quantity + ? WHERE stock_id = ?");
                    $updateStock->bind_param("ii", $recoveryQty, $stockId);
                    $updateStock->execute();
                    $updateStock->close();
                } else {
                    $insertStockStmt = $conn->prepare("INSERT INTO stock (product_id, quantity, arrival_date) VALUES (?, ?, CURDATE())");
                    $insertStockStmt->bind_param("ii", $productId, $recoveryQty);
                    $insertStockStmt->execute();
                    $stockId = $conn->insert_id;
                    $insertStockStmt->close();
                }
                $stockStmt->close();
            }

            // Add recovery with stock_id
            $stmt = $conn->prepare("INSERT INTO recovery_records 
                (recovery_ref, recovery_date, product, original_delivery, quantity, reason, item_condition, action_taken, financial_impact, notes, stock_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisssdsi", $recoveryRef, $recoveryDate, $product, $originalDelivery, $recoveryQty, $reason, $itemCondition, $actionTaken, $financialImpact, $notes, $stockId);
            if (!$stmt->execute()) throw new Exception("Failed to insert recovery record: " . $stmt->error);
            $stmt->close();

        // ========== EDIT LOGIC ==========
        } elseif ($mode === 'edit' && $recordId > 0) {
            // 1. Get the original record
            $origStmt = $conn->prepare("SELECT product, quantity, action_taken, stock_id FROM recovery_records WHERE id = ? LIMIT 1");
            $origStmt->bind_param("i", $recordId);
            $origStmt->execute();
            $origResult = $origStmt->get_result();
            if (!$originalRecord = $origResult->fetch_assoc()) throw new Exception("Original record not found.");
            $origStmt->close();

            // 2. Reverse old stock if old action was 'Returned'
            $oldStockId = $originalRecord['stock_id'];
            if (strtolower($originalRecord['action_taken']) === 'returned' && $oldStockId && (int)$originalRecord['quantity'] > 0) {
                $updateStock = $conn->prepare("UPDATE stock SET quantity = GREATEST(0, quantity - ?) WHERE stock_id = ?");
                $updateStock->bind_param("ii", $originalRecord['quantity'], $oldStockId);
                $updateStock->execute();
                $updateStock->close();
            }

            // 3. Find new batch to apply
            $newStockId = null;
            if (strtolower($actionTaken) === 'returned' && $recoveryQty > 0 && $productId > 0) {
                $stockStmt = $conn->prepare("SELECT stock_id FROM stock WHERE product_id = ? ORDER BY expiry_date ASC LIMIT 1");
                $stockStmt->bind_param("i", $productId);
                $stockStmt->execute();
                $stockResult = $stockStmt->get_result();
                if ($stockRow = $stockResult->fetch_assoc()) {
                    $newStockId = $stockRow['stock_id'];
                    $updateStock = $conn->prepare("UPDATE stock SET quantity = quantity + ? WHERE stock_id = ?");
                    $updateStock->bind_param("ii", $recoveryQty, $newStockId);
                    $updateStock->execute();
                    $updateStock->close();
                } else {
                    $insertStockStmt = $conn->prepare("INSERT INTO stock (product_id, quantity, arrival_date) VALUES (?, ?, CURDATE())");
                    $insertStockStmt->bind_param("ii", $productId, $recoveryQty);
                    $insertStockStmt->execute();
                    $newStockId = $conn->insert_id;
                    $insertStockStmt->close();
                }
                $stockStmt->close();
            }

            // 4. Update recovery with new stock_id
            $stmt = $conn->prepare("UPDATE recovery_records SET 
                recovery_ref = ?, recovery_date = ?, product = ?, original_delivery = ?, quantity = ?, 
                reason = ?, item_condition = ?, action_taken = ?, financial_impact = ?, notes = ?, stock_id = ? 
                WHERE id = ?");
            $stmt->bind_param("ssssisssdsii", $recoveryRef, $recoveryDate, $product, $originalDelivery, $recoveryQty, $reason, $itemCondition, $actionTaken, $financialImpact, $notes, $newStockId, $recordId);
            if (!$stmt->execute()) throw new Exception("Failed to update recovery record: " . $stmt->error);
            $stmt->close();

        } else {
            throw new Exception("Invalid mode or record ID.");
        }

        // --- Update delivery status: always 'Returned' ---
        $updDelStmt = $conn->prepare("UPDATE deliveries SET delivery_status = 'Returned' WHERE delivery_code = ? LIMIT 1");
        $updDelStmt->bind_param("s", $originalDelivery);
        $updDelStmt->execute();
        $updDelStmt->close();

        $conn->commit();
        echo "<script>alert('Recovery record processed successfully! Financial impact: LKR " . number_format($financialImpact, 2) . ".'); window.location.href='index.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }

    $conn->close();
} else {
    header("Location: index.php");
    exit;
}
?>
