
<?php

        header("Content-Type: application/json");
        include "connect.php";

        if (!isset($_GET['id'])) {
            echo json_encode(["success" => false, "message" => "No buyer ID provided"]);
            exit;
        }

        $id = intval($_GET['id']);

        $sql = "DELETE FROM buyer WHERE buyer_id = $id";

        if ($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "buyer deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error deleting buyer: " . $conn->error]);
        }

        $conn->close();
?>
