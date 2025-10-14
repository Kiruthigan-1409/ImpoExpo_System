
 <?php
        header("Content-Type: application/json");
        include "connect.php";

        if(!isset($_GET['id'])) {   
            echo json_encode(["success" => false, "message" => "No buyer ID provided."]);
            exit;
        }

        $id = intval($_GET['id']);

        $buyername = $_POST['buyername'] ;
        $buyeraddress = $_POST['b_address'];
        $buyercity = $_POST['b_city'];
        $buyeremail = $_POST['b_email'];
        $buyercontact= $_POST['b_contact'];
        $b_productid = $_POST['b_productid'];
        $buyerstatus = $_POST['b_status'];

        $sql = "UPDATE buyer SET 
                    buyername='$buyername', 
                    b_address='$buyeraddress', 
                    b_city='$buyercity', 
                    b_email='$buyeremail', 
                    b_contact='$buyercontact', 
                    b_productid='$b_productid', 
                    b_status='$buyerstatus'
                WHERE buyer_id=$id";

        if($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Buyer updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating buyer: ".$conn->error]);
        }

        $conn->close();
 ?>