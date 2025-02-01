<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Yetkisiz erişim');
}

// Resim yükleme işlemi
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $dosya_uzantisi = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
        // Benzersiz dosya adı oluştur
        $yeni_isim = uniqid('img_') . '.' . $dosya_uzantisi;
        $hedef_klasor = '../uploads/editor/';
        
        // Klasör yoksa oluştur
        if (!file_exists($hedef_klasor)) {
            mkdir($hedef_klasor, 0777, true);
        }
        
        $hedef_dosya = $hedef_klasor . $yeni_isim;
        
        // Resmi yükle
        if (move_uploaded_file($_FILES['image']['tmp_name'], $hedef_dosya)) {
            // Başarılı
            echo '../uploads/editor/' . $yeni_isim;
        } else {
            // Hata
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Resim yüklenirken bir hata oluştu.';
        }
    } else {
        // Geçersiz dosya formatı
        header('HTTP/1.1 400 Bad Request');
        echo 'Geçersiz dosya formatı. Sadece JPG, JPEG, PNG, GIF ve WEBP dosyaları yüklenebilir.';
    }
} else {
    // Dosya yok veya hatalı
    header('HTTP/1.1 400 Bad Request');
    echo 'Lütfen geçerli bir resim dosyası seçin.';
} 