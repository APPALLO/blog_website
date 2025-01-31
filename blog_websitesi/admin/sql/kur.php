<?php
require_once('../../baglan.php');

// SQL dosyasını oku
$sql = file_get_contents('site_ayarlari.sql');

// SQL sorgularını çalıştır
if ($conn->multi_query($sql)) {
    echo "Site ayarları tablosu başarıyla oluşturuldu!";
} else {
    echo "Hata: " . $conn->error;
}

$conn->close();
?> 