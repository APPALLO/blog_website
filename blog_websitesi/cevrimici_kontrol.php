<?php
// Session başlatma kodu kaldırıldı çünkü ana dosyalarda başlatılıyor

include 'baglan.php';

// Kullanıcı giriş yapmışsa son aktivite zamanını güncelle
if (isset($_SESSION['kullanici_id'])) {
    $kullanici_id = $_SESSION['kullanici_id'];
    $sql = "UPDATE kullanicilar SET son_aktivite = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
}

// Kullanıcının son aktivite zamanını güncelle
function aktivite_guncelle($kullanici_id) {
    global $conn;
    $sql = "UPDATE kullanicilar SET 
            son_aktivite = NOW(),
            cevrimici_durum = 1 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
}

// Çevrimiçi durumu kontrol et (5 dakika içinde aktivite varsa çevrimiçi sayılır)
function cevrimici_kontrol() {
    global $conn;
    $sql = "UPDATE kullanicilar 
            SET cevrimici_durum = 0 
            WHERE son_aktivite < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $conn->query($sql);
}

// Tüm kullanıcıların çevrimiçi durumunu kontrol et
cevrimici_kontrol();

// Çevrimiçi kullanıcı sayısını al
function cevrimici_kullanici_sayisi() {
    global $conn;
    $sql = "SELECT COUNT(*) as sayi FROM kullanicilar WHERE cevrimici_durum = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['sayi'];
}

// Çevrimiçi kullanıcıları al
function cevrimici_kullanicilar() {
    global $conn;
    $sql = "SELECT id, kullanici_adi, ad_soyad, rol, son_aktivite 
            FROM kullanicilar 
            WHERE cevrimici_durum = 1 
            ORDER BY son_aktivite DESC";
    return $conn->query($sql);
}
?> 