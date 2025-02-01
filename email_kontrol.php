<?php
include 'baglan.php';

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    
    $sql = "SELECT id FROM kullanicilar WHERE email = '$email'";
    $result = $conn->query($sql);
    
    echo json_encode(array("mevcut" => $result->num_rows > 0));
} else {
    echo json_encode(array("hata" => "E-posta parametresi eksik"));
}

$conn->close();
?> 