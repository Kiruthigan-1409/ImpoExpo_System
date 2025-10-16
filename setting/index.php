<?php include '../authentication/auth.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Connect DB
include 'db.php';

// 1. Prepare locked stock ids (stock_id with qty=0 AND referenced in imports)
$locked_stock_ids = [];
$zero_stock_res = $conn->query("SELECT stock_id FROM stock WHERE quantity=0");
while ($row = $zero_stock_res->fetch_assoc()) {
    $sid = intval($row['stock_id']);
    $imp_res = $conn->query("SELECT 1 FROM imports WHERE stock_id=$sid LIMIT 1");
    if ($imp_res->num_rows > 0) {
        $locked_stock_ids[] = $sid;
    }
}

// 2. Handle form submits for add/update/delete product (safe deletes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'update_threshold') {
        $threshold = intval($_POST['low_stock_threshold']);
        $_SESSION['low_stock_threshold'] = $threshold; // <-- Set in session
        $low_stock_threshold = $threshold;
        $success_message = "Low stock threshold updated to $threshold!";
    }

    if ($_POST['action'] === 'add_product') {
        $name = $_POST['product_name'];
        $price = floatval($_POST['price_per_kg']);
        $conn->query("INSERT INTO products (product_name, price_per_kg) VALUES ('$name', $price)");
    }
    if ($_POST['action'] === 'delete_product') {
        $id = intval($_POST['product_id']);
        // Check if referenced in supplier
        $check = $conn->query("SELECT 1 FROM supplier WHERE s_productid=$id LIMIT 1");
        if ($check->num_rows > 0) {
            $success_message = "This product is linked to a supplier and cannot be deleted!";
        } else {
            $conn->query("DELETE FROM products WHERE product_id=$id");
            $conn->query("DELETE FROM stock WHERE product_id=$id");
            $success_message = "Product deleted successfully.";
        }
    }
    if ($_POST['action'] === 'update_price') {
        $id = intval($_POST['product_id']);
        $price = floatval($_POST['price_per_kg']);
        $conn->query("UPDATE products SET price_per_kg=$price WHERE product_id=$id");
    }
    if ($_POST['action'] === 'remove_zero_stock') {
        $res = $conn->query("SELECT stock_id FROM stock WHERE quantity=0");
        $toDelete = [];
        while ($row = $res->fetch_assoc()) {
            $sid = intval($row['stock_id']);
            $imp_check = $conn->query("SELECT 1 FROM imports WHERE stock_id=$sid LIMIT 1");
            if ($imp_check->num_rows == 0) {
                $toDelete[] = $sid;
            }
        }
        if (count($toDelete)) {
            $id_list = implode(',', $toDelete);
            $conn->query("DELETE FROM stock WHERE stock_id IN ($id_list)");
            $success_message = count($toDelete) . " zero-qty unlocked rows removed. Locked rows are kept!";
        } else {
            $success_message = "No unlocked zero-qty stocks to remove.";
        }
    }
    if ($_POST['action'] === 'update_threshold') {
        $threshold = intval($_POST['low_stock_threshold']);
        $low_stock_threshold = $threshold;
        $success_message = "Low stock threshold updated to $threshold!";
    }
}
// 3. Always set from session or default
$low_stock_threshold = isset($_SESSION['low_stock_threshold']) ? intval($_SESSION['low_stock_threshold']) : 25;

// Get products list
$products = $conn->query("SELECT * FROM products ORDER BY product_name ASC")->fetch_all(MYSQLI_ASSOC);

// Get stock by product (sum quantities)
$stocks = [];
$res = $conn->query("SELECT product_id, SUM(quantity) as qty FROM stock GROUP BY product_id");
while ($row = $res->fetch_assoc()) {
    $stocks[$row['product_id']] = $row['qty'];
}

$low_stock_threshold = $low_stock_threshold ?? 25;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .locked-badge {
            background: #fde68a;
            color: #b45309;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 11px;
            margin-left: 3px;
        }
        .stock-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .stock-badge.low-stock {
            background: #fee2e2;
            color: #dc2626;
        }
        .stock-badge.normal-stock {
            background: #dcfce7;
            color: #16a34a;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../layout/sidebar.php'; ?>
    <div class="settings-main">
        <header class="header">
            <div class="header-content">
                <h1><i class="fas fa-cogs"></i> System Settings & Inventory Management</h1>
                <p>Manage products, prices, and system configuration</p>
            </div>
        </header>
        <?php if (isset($success_message)): ?>
            <div style="background: #d1fae5; color: #22c55e; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?= $success_message ?>
            </div>
        <?php endif; ?>

        <!-- Add product form -->
        <div class="settings-sect">
            <h3><i class="fas fa-plus-circle"></i> Add Product</h3>
            <form method="post" class="settings-form">
                <input type="hidden" name="action" value="add_product" />
                <input type="text" name="product_name" placeholder="Product Name" required />
                <input type="number" name="price_per_kg" step="0.01" placeholder="Price per kg/unit" required />
                <button type="submit"><i class="fas fa-plus"></i> Add Product</button>
            </form>
        </div>

        <!-- System settings -->
        <div class="settings-sect">
            <h3><i class="fas fa-tools"></i> System Options</h3>
            <form method="post" class="settings-form">
                <input type="number" name="low_stock_threshold" value="<?=$low_stock_threshold?>" min="1" placeholder="Threshold" />
                <button type="submit" name="action" value="update_threshold">
                    <i class="fas fa-bell"></i> Set Low Stock Alert
                </button>
            </form>
            
            <!-- Remove zero stock button -->
            <form method="post" class="settings-form" onsubmit="return confirm('⚠️ This will permanently remove all stock rows with zero quantity. Continue?');">
                <button type="submit" name="action" value="remove_zero_stock" style="background:#ef4444;">
                    <i class="fas fa-trash-alt"></i> Remove Zero Stock Rows
                </button>
            </form>
        </div>

        <!-- Products table -->
        <div class="settings-sect">
            <h3><i class="fas fa-boxes"></i> Product Price Management</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-box"></i> Product</th>
                            <th><i class="fas fa-rupee-sign"></i> Price per Unit (LKR)</th>
                            <th><i class="fas fa-warehouse"></i> Stock Available</th>
                            <th><i class="fas fa-edit"></i> Edit Price</th>
                            <th><i class="fas fa-trash"></i> Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['product_name']) ?></td>
                            <td><?= number_format($p['price_per_kg'],2) ?></td>
                            <td>
                                <?php
                                $qty = $stocks[$p['product_id']] ?? 0;
                                $locked = false;
                                $check_res = $conn->query("SELECT stock_id FROM stock WHERE product_id={$p['product_id']} AND quantity=0");
                                while ($r = $check_res->fetch_assoc()) {
                                    if (in_array($r['stock_id'], $locked_stock_ids)) {
                                        $locked = true; break;
                                    }
                                }
                                ?>
                                <span class="stock-badge <?= $qty <= $low_stock_threshold ? 'low-stock' : 'normal-stock' ?>">
                                    <?= $qty ?>
                                </span>
                                <?php if ($qty == 0 && $locked): ?>
                                    <span class="locked-badge"><i class="fas fa-lock"></i> Locked (Imports)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" class="update-form">
                                    <input type="hidden" name="action" value="update_price" />
                                    <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                    <input type="number" name="price_per_kg" step="0.01" value="<?= $p['price_per_kg'] ?>" style="width:80px;">
                                    <button type="submit"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                            <td>
                                <?php
                                $is_prod_locked = $conn->query("SELECT 1 FROM supplier WHERE s_productid={$p['product_id']} LIMIT 1")->num_rows > 0;
                                ?>
                                <?php if ($is_prod_locked): ?>
                                    <button class="del-btn" disabled style="background: #fcd34d; color: #a16207;">
                                        <i class="fas fa-lock"></i> Linked
                                    </button>
                                <?php elseif ($qty == 0 && $locked): ?>
                                    <button class="del-btn" disabled style="background: #fcd34d; color: #a16207;">
                                        <i class="fas fa-lock"></i> Locked
                                    </button>
                                <?php else: ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="delete_product" />
                                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                        <button type="submit" class="del-btn" onclick="return confirm('Delete <?= htmlspecialchars($p['product_name']) ?>?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="script.js"></script>
</body>
</html>
