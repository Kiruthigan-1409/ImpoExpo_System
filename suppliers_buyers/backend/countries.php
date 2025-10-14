

<?php
include "connect.php"; 

$sql = "SELECT c_name, phone_code FROM countries ORDER BY c_name ASC";
$result = $conn->query($sql);

$countries = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $countries[] = $row; // each row has c_name and c_code
    }
}

echo json_encode($countries);
?>

