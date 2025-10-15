<?php
require 'db.php';
header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';


function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}


switch ($action) {

    case 'list':
        $res = $conn->query("SELECT * FROM deliveries ORDER BY delivery_id DESC");
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode(['data' => $rows]);
        break;

    case 'get':
        $id = $_REQUEST['delivery_id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM deliveries WHERE delivery_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['data' => $result]);
        break;

    case 'orders':
        $res = $conn->query("SELECT * FROM order_table WHERE delivery_confirmation = 0 ORDER BY order_id DESC");
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode(['data' => $rows]);
        break;

    case 'orderDetails':
        $order_no = $_REQUEST['order_no'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM order_table WHERE order_id = ?");
        $stmt->bind_param('s', $order_no);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['data' => $result]);
        break;

    case 'buyerDetails':
        $buyer_id = $_REQUEST['buyer_id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM buyer WHERE buyer_id = ?");
        $stmt->bind_param('i', $buyer_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['data' => $result]);
        break;

    case 'productDetails':
        $product_id = $_REQUEST['product_id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['data' => $result]);
        break;

    case 'update':
        $id = intval($_POST['delivery_id'] ?? 0);
        $driver = sanitize($_POST['driver'] ?? '');
        $scheduled = sanitize($_POST['scheduledDate'] ?? '');
        $actual = sanitize($_POST['actualDate'] ?? '');
        $status = sanitize($_POST['deliveryStatus'] ?? 'Pending');

        if ($id <= 0 || !$driver || !$scheduled) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing required fields']);
            break;
        }

        $stmt = $conn->prepare("UPDATE deliveries SET driver = ?, scheduled_date = ?, actual_date = ?, delivery_status = ? WHERE delivery_id = ?");
        $stmt->bind_param('ssssi', $driver, $scheduled, $actual, $status, $id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success && $status === 'Delivered') {
            // Rishi neesd to trigger the stock decrease part here

            $stmt = $conn->prepare("UPDATE order_table SET delivery_confirmation = 1 WHERE order_id = (SELECT order_no FROM deliveries WHERE delivery_id = ?)");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Delivery updated successfully' : 'Failed to update delivery'
        ]);
        break;

    case 'delete':
        $id = isset($_REQUEST['delivery_id']) ? (int) $_REQUEST['delivery_id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid delivery ID']);
            break;
        }

        $stmt = $conn->prepare("SELECT order_no FROM deliveries WHERE delivery_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $orderRow = $stmt->get_result()->fetch_assoc();
        $order_no = $orderRow['order_no'] ?? null;
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM deliveries WHERE delivery_id = ?");
        $stmt->bind_param('i', $id);
        $success  = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($success && $affected > 0 && $order_no) {
            $stmt = $conn->prepare("UPDATE order_table SET delivery_confirmation = 0 WHERE order_id = ?");
            $stmt->bind_param('s', $order_no);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode([
            'success' => $success && $affected > 0,
            'message' => ($success && $affected > 0)
                ? 'Delivery deleted successfully'
                : 'No matching record found'
        ]);
        break;

    default:
        $res = $conn->query("SELECT delivery_code FROM deliveries ORDER BY delivery_id DESC LIMIT 1");
        $last = $res->fetch_assoc();
        if ($last && preg_match('/DEL-(\d+)/', $last['delivery_code'], $matches)) {
            $next = str_pad($matches[1]+1, 3, '0', STR_PAD_LEFT);
        } else {
            $next = '001';
        }
        $delivery_code = "DEL-" . $next;

        $order_no = $_POST['orderNo'] ?? '';
        $buyer_name = $_POST['buyerName'] ?? '';
        $city = $_POST['city'] ?? '';
        $address = $_POST['address'] ?? '';
        $product_name = $_POST['productName'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $driver = $_POST['driver'] ?? '';
        $scheduled_date = $_POST['scheduledDate'] ?? '';
        $actual_date = $_POST['actualDate'] ?? '';
        $delivery_status = $_POST['deliveryStatus'] ?? 'Pending';

        $stmt = $conn->prepare("INSERT INTO deliveries 
            (delivery_code, order_no, buyer_name, city, address, product_name, quantity, driver, scheduled_date, actual_date, delivery_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssissss', $delivery_code, $order_no, $buyer_name, $city, $address, $product_name, $quantity, $driver, $scheduled_date, $actual_date, $delivery_status);

        if ($stmt->execute()) {
            echo json_encode(['success'=>true, 'message'=>"Delivery $delivery_code added successfully."]);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Failed to add delivery.']);
        }
        break;
}
