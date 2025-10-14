<?php
include 'db.php';

//delete import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_import'])) {
    $import_id = intval($_POST['delete_import']);
    $stmt = $conn->prepare("DELETE FROM imports WHERE import_id = ?");
    $stmt->bind_param("i", $import_id);
    $stmt->execute();
    $stmt->close();
    header("Location: import_management.php?msg=deleted");
    exit();
}

//insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['id'])) {
    $import_ref   = $_POST['import_ref'];
    $supplier_id  = $_POST['supplier_id'];
    $product_id   = $_POST['product_id'];
    $quantity     = $_POST['quantity'];
    $import_date  = $_POST['import_date'];
    $arrival_date = $_POST['arrival_date'];
    $expiry_date  = $_POST['expiry'];
    $remarks      = $_POST['remarks'];

    // Validation
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
    $supplier_name = $supplierQuery->get_result()->fetch_assoc()['suppliername'] ?? '';
    $supplierQuery->close();

    // Get product name
    $productQuery = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
    $productQuery->bind_param("i", $product_id);
    $productQuery->execute();
    $product_name = $productQuery->get_result()->fetch_assoc()['product_name'] ?? '';
    $productQuery->close();

    // Check stock
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

    //generate unique import_ref
    do {
        $lastImport = $conn->query("SELECT import_ref FROM imports ORDER BY import_id DESC LIMIT 1")->fetch_assoc();
        $num = $lastImport ? intval(str_replace('IMP-', '', $lastImport['import_ref'])) + 1 : 1;
        $import_ref = 'IMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);

        $check = $conn->query("SELECT import_ref FROM imports WHERE import_ref = '$import_ref'");
    } while($check->num_rows > 0);


    // Insert import
    $insertImport = $conn->prepare("INSERT INTO imports 
        (import_ref, stock_id, supplier_id, product_id, suppliername, product_name, import_date, arrival_date, expiry_date, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertImport->bind_param("siiissssss", 
        $import_ref, $stock_id, $supplier_id, $product_id,
        $supplier_name, $product_name,
        $import_date, $arrival_date, $expiry_date, $remarks
    );

    if ($insertImport->execute()) {
        header("Location: import_management.php?success=1");
        exit;
    } else {
        echo "Error inserting import: " . $insertImport->error;
    }
}

//updat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !empty($_POST['id'])) {
    $import_id   = $_POST['id'];
    $supplier_id = $_POST['supplier_id'];
    $product_id  = $_POST['product_id'];
    $quantity    = $_POST['quantity'];
    $arrival_date = $_POST['arrival_date'];
    $expiry_date = $_POST['expiry'];
    $remarks     = $_POST['remarks'];
    $import_date = $_POST['import_date'];

    //validations
    if (empty($supplier_id) || empty($product_id) || empty($quantity) || empty($import_date) || empty($arrival_date) || empty($expiry_date)) {
        echo "<script>alert('All fields are required!'); window.location='import_management.php';</script>";
        exit;
    }
    if (!is_numeric($quantity) || $quantity <= 0) {
        echo "<script>alert('Quantity must be a positive number!'); window.location='import_management.php';</script>";
        exit;
    }
    if (strtotime($arrival_date) < strtotime($import_date)) {
        echo "<script>alert('Arrival date cannot be before import date!'); window.location='import_management.php';</script>";
        exit;
    }
    if (strtotime($expiry_date) <= strtotime($arrival_date)) {
        echo "<script>alert('Expiry date must be after arrival date!'); window.location='import_management.php';</script>";
        exit;
    }

    //fetch supplier name and product
    $supRes = $conn->prepare("SELECT suppliername FROM supplier WHERE supplier_id = ?");
    $supRes->bind_param("i", $supplier_id);
    $supRes->execute();
    $supplier_name = $supRes->get_result()->fetch_assoc()['suppliername'] ?? '';
    $supRes->close();

    $prodRes = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
    $prodRes->bind_param("i", $product_id);
    $prodRes->execute();
    $product_name = $prodRes->get_result()->fetch_assoc()['product_name'] ?? '';
    $prodRes->close();

    //get stock id
    $stockQ = $conn->prepare("SELECT stock_id FROM imports WHERE import_id = ?");
    $stockQ->bind_param("i", $import_id);
    $stockQ->execute();
    $stock_id = $stockQ->get_result()->fetch_assoc()['stock_id'] ?? null;
    $stockQ->close();

    if (!$stock_id) {
        echo "<script>alert('Error: Stock record not found!'); window.location='import_management.php';</script>";
        exit;
    }

    // update stock
    $stmt_stock = $conn->prepare("UPDATE stock 
        SET quantity = ?, expiry_date = ?, arrival_date = ? 
        WHERE stock_id = ?");
    $stmt_stock->bind_param("issi", $quantity, $expiry_date, $arrival_date, $stock_id);
    $stmt_stock->execute();
    $stmt_stock->close();

    //update import
    $stmt_imports = $conn->prepare("UPDATE imports 
        SET supplier_id = ?, suppliername = ?, product_id = ?, product_name = ?, import_date = ?, arrival_date = ?, expiry_date = ?, remarks = ?
        WHERE import_id = ?");
    $stmt_imports->bind_param("isisssssi", $supplier_id, $supplier_name, $product_id, $product_name, $import_date, $arrival_date, $expiry_date, $remarks, $import_id);

    if ($stmt_imports->execute()) {
        echo "<script>alert('Record updated successfully!'); window.location='import_management.php';</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "'); window.location='import_management.php';</script>";
    }
    $stmt_imports->close();
    exit;
}

//get import details for edit function
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $import_id = intval($_GET['id']);
    
    $query = $conn->prepare("
        SELECT i.*, s.quantity, s.expiry_date AS stock_expiry, s.arrival_date AS stock_arrival
        FROM imports i
        JOIN stock s ON i.stock_id = s.stock_id
        WHERE i.import_id = ?
    ");
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

?>
