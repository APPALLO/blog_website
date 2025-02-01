<?php
/**
 * Blog sitesi için yardımcı fonksiyonlar
 */

// XSS koruması için metin temizleme
function guvenli_metin($metin) {
    return htmlspecialchars(trim($metin), ENT_QUOTES, 'UTF-8');
}

// SEO dostu URL oluşturma
function seo_url($string) {
    $string = str_replace(array('ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'), 
                         array('i', 'i', 'g', 'g', 'u', 'u', 's', 's', 'o', 'o', 'c', 'c'), $string);
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Tarih formatını düzenleme
function tarih_formatla($tarih) {
    return date('d.m.Y', strtotime($tarih));
}

// Metin kısaltma
function metin_kisalt($metin, $uzunluk = 150) {
    $metin = strip_tags($metin);
    if (strlen($metin) > $uzunluk) {
        $metin = substr($metin, 0, $uzunluk);
        $metin = substr($metin, 0, strrpos($metin, ' ')) . '...';
    }
    return $metin;
}

// Dosya boyutunu formatla
function dosya_boyutu_formatla($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Kullanıcı giriş kontrolü
function giris_yapildi_mi() {
    return isset($_SESSION['kullanici_id']);
}

// Admin kontrolü
function admin_mi() {
    return isset($_SESSION['kullanici_rol']) && $_SESSION['kullanici_rol'] === 'admin';
}

// Yazar kontrolü
function yazar_mi() {
    return isset($_SESSION['kullanici_rol']) && ($_SESSION['kullanici_rol'] === 'yazar' || $_SESSION['kullanici_rol'] === 'admin');
}

// Hata mesajı oluştur
function hata_mesaji($mesaj) {
    return '<div class="alert alert-danger" role="alert">' . $mesaj . '</div>';
}

// Başarı mesajı oluştur
function basari_mesaji($mesaj) {
    return '<div class="alert alert-success" role="alert">' . $mesaj . '</div>';
}

// Bilgi mesajı oluştur
function bilgi_mesaji($mesaj) {
    return '<div class="alert alert-info" role="alert">' . $mesaj . '</div>';
}

// Uyarı mesajı oluştur
function uyari_mesaji($mesaj) {
    return '<div class="alert alert-warning" role="alert">' . $mesaj . '</div>';
}

// Resim yükleme fonksiyonu
function resim_yukle($dosya, $hedef_klasor = 'uploads/') {
    $izin_verilen_uzantilar = array('jpg', 'jpeg', 'png', 'gif');
    $max_boyut = 5 * 1024 * 1024; // 5MB
    
    // Klasör kontrolü
    if (!file_exists($hedef_klasor)) {
        mkdir($hedef_klasor, 0777, true);
    }
    
    $dosya_adi = $dosya['name'];
    $dosya_boyutu = $dosya['size'];
    $gecici_ad = $dosya['tmp_name'];
    $hata = $dosya['error'];
    
    // Hata kontrolü
    if ($hata !== 0) {
        return array('hata' => 'Dosya yüklenirken bir hata oluştu.');
    }
    
    // Boyut kontrolü
    if ($dosya_boyutu > $max_boyut) {
        return array('hata' => 'Dosya boyutu çok büyük. Maksimum boyut: ' . dosya_boyutu_formatla($max_boyut));
    }
    
    // Uzantı kontrolü
    $uzanti = strtolower(pathinfo($dosya_adi, PATHINFO_EXTENSION));
    if (!in_array($uzanti, $izin_verilen_uzantilar)) {
        return array('hata' => 'Geçersiz dosya uzantısı. İzin verilen uzantılar: ' . implode(', ', $izin_verilen_uzantilar));
    }
    
    // Benzersiz dosya adı oluştur
    $yeni_ad = uniqid() . '.' . $uzanti;
    $hedef_yol = $hedef_klasor . $yeni_ad;
    
    // Dosyayı taşı
    if (move_uploaded_file($gecici_ad, $hedef_yol)) {
        return array('basari' => true, 'dosya_yolu' => $hedef_yol);
    } else {
        return array('hata' => 'Dosya yüklenemedi.');
    }
}

// Oturum kontrolü
function oturum_kontrolu() {
    if (!giris_yapildi_mi()) {
        header('Location: giris.php');
        exit();
    }
}

// Admin oturum kontrolü
function admin_kontrolu() {
    if (!admin_mi()) {
        header('Location: index.php');
        exit();
    }
}

// Yazar oturum kontrolü
function yazar_kontrolu() {
    if (!yazar_mi()) {
        header('Location: index.php');
        exit();
    }
}

// Token oluşturma
function token_olustur() {
    if (!isset($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token'];
}

// Token kontrolü
function token_kontrol($token) {
    return isset($_SESSION['token']) && hash_equals($_SESSION['token'], $token);
}

// Sayfalama için toplam sayfa sayısını hesapla
function sayfa_sayisi_hesapla($toplam_kayit, $kayit_sayisi = 10) {
    return ceil($toplam_kayit / $kayit_sayisi);
}

// Aktif sayfa numarasını al
function aktif_sayfa() {
    return isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
}

// LIMIT için offset hesapla
function offset_hesapla($sayfa, $kayit_sayisi = 10) {
    return ($sayfa - 1) * $kayit_sayisi;
}
?> 