<?php
include 'baglan.php';

// Kullanıcı bilgilerini al
$sql = "SELECT * FROM kullanicilar WHERE kullanici_adi = 'admin'";
$result = $conn->query($sql);
$kullanici = $result->fetch_assoc();

if ($kullanici) {
    echo "Admin kullanıcısı bulundu:<br>";
    echo "ID: " . $kullanici['id'] . "<br>";
    echo "Kullanıcı Adı: " . $kullanici['kullanici_adi'] . "<br>";
    echo "E-posta: " . $kullanici['email'] . "<br>";
    echo "Rol: " . $kullanici['rol'] . "<br>";
    echo "Hash: " . $kullanici['sifre'] . "<br>";
    
    // Şifre kontrolü
    $test_sifre = 'admin123';
    if (password_verify($test_sifre, $kullanici['sifre'])) {
        echo "<br>Şifre doğrulaması BAŞARILI!";
    } else {
        echo "<br>Şifre doğrulaması BAŞARISIZ!";
    }
} else {
    echo "Admin kullanıcısı bulunamadı!";
}
?> 