

<?php
    header("Content-Type: application/json");

    include "connect.php";


    // Collect POST data
    $suppliername = $_POST['suppliername'];
    $s_company = $_POST['s_company'];
    $s_country = $_POST['s_country'];
    $s_city = $_POST['s_city'];
    $s_email = $_POST['s_email'];
    $s_country_code = $_POST['s_country_code'];
    $s_contact = $_POST['s_contact'];
    $s_productid = $_POST['s_productid'];
    $s_status = $_POST['s_status'];

    // Insert query
    $stmt = $conn->prepare("INSERT INTO supplier 
        (suppliername, s_company, s_country, s_city, s_email, s_country_code, s_contact, s_productid, s_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssssis",
        $suppliername, $s_company, $s_country, $s_city, $s_email, $s_country_code,
        $s_contact, $s_productid, $s_status
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Supplier added successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Insert failed"]);
    }

    $stmt->close();
    $conn->close();
?>



