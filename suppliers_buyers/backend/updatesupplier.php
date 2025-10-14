

 <?php
        header("Content-Type: application/json");
        include "connect.php";

        if(!isset($_GET['id'])) {
            echo json_encode(["success" => false, "message" => "No supplier ID provided."]);
            exit;
        }

        $id = intval($_GET['id']);

        $suppliername = $_POST['suppliername'];
        $s_company = $_POST['s_company'];
        $s_country = $_POST['s_country'];
        $s_city = $_POST['s_city'];
        $s_email = $_POST['s_email'];
        $s_country_code = $_POST['s_country_code'];
        $s_contact = $_POST['s_contact'];
        $s_productid = $_POST['s_productid'];
        $s_status = $_POST['s_status'];

        $sql = "UPDATE supplier SET 
                    suppliername='$suppliername', 
                    s_company='$s_company', 
                    s_country='$s_country', 
                    s_city='$s_city', 
                    s_email='$s_email', 
                    s_country_code='$s_country_code', 
                    s_contact='$s_contact', 
                    s_productid='$s_productid', 
                    s_status='$s_status' 
                WHERE supplier_id=$id";

        if($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Supplier updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating supplier: ".$conn->error]);
        }

        $conn->close();
 ?>

