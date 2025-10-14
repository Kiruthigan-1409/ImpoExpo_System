
<?php

header("Content-Type:application/json");

include "connect.php";

$buyername = $_POST['buyername'] ;
$buyeraddress = $_POST['b_address'];
$buyercity = $_POST['b_city'];
$buyeremail = $_POST['b_email'];
$buyercontact= $_POST['b_contact'];
$b_productid = $_POST['b_productid'];
$buyerstatus = $_POST['b_status'];


$stmt =$conn->prepare("INSERT into buyer(buyername,b_address,b_city,b_email,b_contact,b_productid,b_status)VALUES (?,?,?,?,?,?,?) ");

$stmt ->bind_param("sssssis",$buyername,$buyeraddress,$buyercity,$buyeremail,$buyercontact,$b_productid,$buyerstatus);

if($stmt -> execute()){
     echo json_encode(["success"=>true,"message"=>"Buyer added successfullly"]);
}
else{
    echo json_encode(["success"=>false,"message"=>"Insert failed"]);
}

$stmt->close();
$conn->close();




?>
