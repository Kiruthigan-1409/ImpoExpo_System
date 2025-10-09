<?php
include 'db.php';

// get import details
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $import_id = intval($_GET['id']);
    $query = $conn->prepare("SELECT * FROM imports WHERE import_id = ?");
    $query->bind_param("i", $import_id);
    $query->execute();
    $result = $query->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'import' => $row]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //delete
    if (isset($_POST['delete_import'])) {
        $import_id = intval($_POST['delete_import']);
        $stmt = $conn->prepare("DELETE FROM imports WHERE import_id = ?");
        $stmt->bind_param("i", $import_id);
        $stmt->execute();
        $stmt->close();
        header("Location: import_management.php?msg=deleted");
        exit();
    }

    //edit
    if (!empty($_POST['import_id'])) {
        $import_id    = intval($_POST['import_id']);
        $supplier_id  = $_POST['supplier_id'];
        $product_id   = $_POST['product_id'];
        $quantity     = $_POST['quantity'];
        $import_date  = $_POST['import_date'];
        $arrival_date = $_POST['arrival_date'];
        $expiry_date  = $_POST['expiry'];
        $remarks      = $_POST['remarks'];

        // Validate fields
        if (empty($supplier_id) || empty($product_id) || empty($quantity) || empty($import_date) || empty($arrival_date) || empty($expiry_date)) {
            die("Error: All required fields must be filled.");
        }
        if ($quantity <= 0) die("Error: Quantity must be greater than 0.");
        if (strtotime($arrival_date) < strtotime($import_date)) die("Error: Arrival date cannot be before import date.");
        if (strtotime($expiry_date) <= strtotime($arrival_date)) die("Error: Expiry date must be after arrival date.");

        // Get supplier name
        $supplierQuery = $conn->prepare("SELECT suppliername FROM supplier WHERE supplier_id = ?");
        $supplierQuery->bind_param("i", $supplier_id);
        $supplierQuery->execute();
        $supplierResult = $supplierQuery->get_result();
        $supplierRow = $supplierResult->fetch_assoc();
        if (!$supplierRow) die("Error: Invalid supplier selected.");
        $supplier_name = $supplierRow['suppliername'];

        // Get product name
        $productQuery = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $productQuery->bind_param("i", $product_id);
        $productQuery->execute();
        $productResult = $productQuery->get_result();
        $productRow = $productResult->fetch_assoc();
        if (!$productRow) die("Error: Invalid product selected.");
        $product_name = $productRow['product_name'];


        // Get stock_id from imports table
        $stockLookup = $conn->prepare("SELECT stock_id FROM imports WHERE import_id = ?");
        $stockLookup->bind_param("i", $import_id);
        $stockLookup->execute();
        $stockResult = $stockLookup->get_result();
        $stockRow = $stockResult->fetch_assoc();
        $stock_id = $stockRow ? $stockRow['stock_id'] : null;
        if (!$stock_id) {
            die("Error: No linked stock record found.");
        }

        // Update imports table
        $updateImport = $conn->prepare("UPDATE imports 
            SET suppliername=?, product_name=?, import_date=?, 
            arrival_date=?, expiry_date=?, remarks=? 
            WHERE import_id=?");
        $updateImport->bind_param("ssssssi",
            $supplier_name, $product_name, $import_date, $arrival_date, 
            $expiry_date, $remarks, $import_id
        );

        // Update quantity and dates in stock table
        $updateStock = $conn->prepare("UPDATE stock 
            SET quantity=?, arrival_date=?, expiry_date=? 
            WHERE stock_id=?");
        $updateStock->bind_param("issi", $quantity, $arrival_date, $expiry_date, $stock_id);

        // Execute both updates
        if ($updateImport->execute() && $updateStock->execute()) {
            header("Location: import_management.php?updated=1");
            exit;
        } else {
            echo "Error updating import: " . $updateImport->error;
        }
        exit;
    }

    //insert
    if (isset($_POST['import_ref'], $_POST['supplier_id'], $_POST['product_id'], $_POST['quantity'], $_POST['import_date'], $_POST['arrival_date'], $_POST['expiry'])) {

        $import_ref   = $_POST['import_ref'];
        $supplier_id  = $_POST['supplier_id'];
        $product_id   = $_POST['product_id'];
        $quantity     = $_POST['quantity'];
        $import_date  = $_POST['import_date'];
        $arrival_date = $_POST['arrival_date'];
        $expiry_date  = $_POST['expiry'];
        $remarks      = $_POST['remarks'];

        // Validations
        if (empty($supplier_id) || empty($product_id) || empty($quantity) || empty($import_date) || empty($arrival_date) || empty($expiry_date)) {
            die("Error: All required fields must be filled.");
        }
        if ($quantity <= 0) die("Error: Quantity must be greater than 0.");
        if (strtotime($arrival_date) < strtotime($import_date)) die("Error: Arrival date cannot be before import date.");
        if (strtotime($expiry_date) <= strtotime($arrival_date)) die("Error: Expiry date must be after arrival date.");

        // Get supplier name
        $supplierQuery = $conn->prepare("SELECT suppliername FROM supplier WHERE supplier_id = ?");
        $supplierQuery->bind_param("i", $supplier_id);
        $supplierQuery->execute();
        $supplierRow = $supplierQuery->get_result()->fetch_assoc();
        if (!$supplierRow) die("Error: Invalid supplier selected.");
        $supplier_name = $supplierRow['suppliername'];

        // Get product name
        $productQuery = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $productQuery->bind_param("i", $product_id);
        $productQuery->execute();
        $productRow = $productQuery->get_result()->fetch_assoc();
        if (!$productRow) die("Error: Invalid product selected.");
        $product_name = $productRow['product_name'];

        //stock handling
        $stockResult = $conn->query("SELECT * FROM stock WHERE product_id = $product_id AND expiry_date = '$expiry_date'");
        if ($stockResult && $stockResult->num_rows > 0) {
            $stock = $stockResult->fetch_assoc();
            $newQty = $stock['quantity'] + $quantity;

            $updateStock = $conn->prepare("UPDATE stock SET quantity = ?, arrival_date = ? WHERE stock_id = ?");
            $updateStock->bind_param("isi", $newQty, $arrival_date, $stock['stock_id']);
            $updateStock->execute();

            $stock_id = $stock['stock_id'];
        } else {
            $insertStock = $conn->prepare("INSERT INTO stock (product_id, quantity, expiry_date, arrival_date) VALUES (?, ?, ?, ?)");
            $insertStock->bind_param("iiss", $product_id, $quantity, $expiry_date, $arrival_date);
            $insertStock->execute();
            $stock_id = $conn->insert_id;
        }

        //insert new import
        $insertImport = $conn->prepare("INSERT INTO imports 
            (import_ref, stock_id, supplier_id, product_id, suppliername, product_name, quantity, import_date, arrival_date, expiry_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertImport->bind_param("siiississss", 
            $import_ref, $stock_id, $supplier_id, $product_id, 
            $supplier_name, $product_name, $quantity, 
            $import_date, $arrival_date, $expiry_date, $remarks
        );

        if ($insertImport->execute()) {
            header("Location: import_management.php?success=1");
            exit;
        } else {
            echo "Error inserting import: " . $insertImport->error;
        }
    }
}
?>
