<?php
include 'baglan.php';

header('Content-Type: application/json');

if (isset($_GET['kullanici_adi'])) {
    $kullanici_adi = mysqli_real_escape_string($conn, $_GET['kullanici_adi']);
    
    $sql = "SELECT id FROM kullanicilar WHERE kullanici_adi = '$kullanici_adi'";
    $result = $conn->query($sql);
    
    echo json_encode(array("mevcut" => $result->num_rows > 0));
} else {
    echo json_encode(array("hata" => "Kullanıcı adı parametresi eksik"));
}

$conn->close();
?> 