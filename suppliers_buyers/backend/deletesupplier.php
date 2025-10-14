
<?php

        header("Content-Type: application/json");
        include "connect.php";

        if (!isset($_GET['id'])) {
            echo json_encode(["success" => false, "message" => "No supplier ID provided"]);
            exit;
        }

        $id = intval($_GET['id']);

        $sql = "DELETE FROM supplier WHERE supplier_id = $id";

        if ($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Supplier deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error deleting supplier: " . $conn->error]);
        }

        $conn->close();
?>

