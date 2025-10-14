
<?php
header("Content-Type: application/json");
include "connect.php";

// Count active suppliers
$sql = "SELECT COUNT(*) AS Activecount FROM supplier WHERE s_status='Active'";
$result = $conn->query($sql);

$count = 0;
if ($result && $row = $result->fetch_assoc()) {
    $count = $row['Activecount'];
}



//count import countries
$sql2 = "SELECT COUNT(distinct s_country) as countrycount from supplier ";
$result2 = $conn->query($sql2);
$count2 = 0;
if ($result2 && $row = $result2->fetch_assoc()) {
    $count2 = $row["countrycount"];
}

//count active buyers
$sql3 = "SELECT COUNT(*) AS Activebuyercount FROM buyer WHERE b_status='Active'";
$result3 = $conn->query($sql3);
$count3=0;
if( $result3 && $row = $result3->fetch_assoc()){
    $count3 = $row["Activebuyercount"];
}

//count cities
$sql4 = "SELECT COUNT(distinct b_city) AS citycount FROM buyer ";
$result4 = $conn->query($sql4);
$count4=0;
if( $result4 && $row = $result4->fetch_assoc()){
    $count4 = $row["citycount"];
}


echo json_encode(['activesupplier' => $count,"countries"=> $count2 ,"activebuyer"=>$count3,"cities"=> $count4]);
$conn->close();


?>