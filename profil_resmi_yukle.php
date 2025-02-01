<?php
session_start();
require_once 'baglan.php';

header('Content-Type: application/json');

if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum açmanız gerekiyor!']);
    exit;
}

if (!isset($_FILES['profil_resmi']) || $_FILES['profil_resmi']['error'] !== 0) {
    echo json_encode(['success' => false, 'error' => 'Dosya yüklenirken bir hata oluştu!']);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$file = $_FILES['profil_resmi'];
$rotation = isset($_POST['rotation']) ? intval($_POST['rotation']) : 0;

// Dosya kontrolü
$izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'gif'];
$izin_verilen_tipler = ['image/jpeg', 'image/png', 'image/gif'];
$max_boyut = 5 * 1024 * 1024; // 5MB

$dosya_uzantisi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$dosya_tipi = $file['type'];
$dosya_boyutu = $file['size'];

// Hata kontrolleri
if (!in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz dosya formatı!']);
    exit;
}

if (!in_array($dosya_tipi, $izin_verilen_tipler)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz dosya türü!']);
    exit;
}

if ($dosya_boyutu > $max_boyut) {
    echo json_encode(['success' => false, 'error' => 'Dosya boyutu çok büyük!']);
    exit;
}

// Hedef dizin kontrolü
$hedef_dizin = 'uploads/profil/';
if (!file_exists($hedef_dizin)) {
    mkdir($hedef_dizin, 0777, true);
}

// Benzersiz dosya adı oluştur
$yeni_isim = 'profil_' . $kullanici_id . '_' . time() . '.' . $dosya_uzantisi;
$hedef_dosya = $hedef_dizin . $yeni_isim;

// Görüntü işleme
try {
    // Resmi yükle
    if (!move_uploaded_file($file['tmp_name'], $hedef_dosya)) {
        throw new Exception('Dosya yüklenirken bir hata oluştu!');
    }

    // GD kütüphanesi ile resmi işle
    $kaynak = null;
    switch ($dosya_tipi) {
        case 'image/jpeg':
            $kaynak = imagecreatefromjpeg($hedef_dosya);
            break;
        case 'image/png':
            $kaynak = imagecreatefrompng($hedef_dosya);
            break;
        case 'image/gif':
            $kaynak = imagecreatefromgif($hedef_dosya);
            break;
    }

    if (!$kaynak) {
        throw new Exception('Resim işlenirken bir hata oluştu!');
    }

    // Resmi döndür
    if ($rotation !== 0) {
        $kaynak = imagerotate($kaynak, -$rotation, 0);
    }

    // Resmi yeniden boyutlandır
    $max_boyut = 400;
    $genislik = imagesx($kaynak);
    $yukseklik = imagesy($kaynak);

    if ($genislik > $max_boyut || $yukseklik > $max_boyut) {
        if ($genislik > $yukseklik) {
            $yeni_genislik = $max_boyut;
            $yeni_yukseklik = intval($yukseklik * ($max_boyut / $genislik));
        } else {
            $yeni_yukseklik = $max_boyut;
            $yeni_genislik = intval($genislik * ($max_boyut / $yukseklik));
        }

        $yeni_resim = imagecreatetruecolor($yeni_genislik, $yeni_yukseklik);
        
        // PNG ve GIF için şeffaflığı koru
        if ($dosya_tipi === 'image/png' || $dosya_tipi === 'image/gif') {
            imagealphablending($yeni_resim, false);
            imagesavealpha($yeni_resim, true);
        }

        imagecopyresampled(
            $yeni_resim, $kaynak,
            0, 0, 0, 0,
            $yeni_genislik, $yeni_yukseklik,
            $genislik, $yukseklik
        );

        $kaynak = $yeni_resim;
    }

    // Resmi kaydet
    switch ($dosya_tipi) {
        case 'image/jpeg':
            imagejpeg($kaynak, $hedef_dosya, 90);
            break;
        case 'image/png':
            imagepng($kaynak, $hedef_dosya, 9);
            break;
        case 'image/gif':
            imagegif($kaynak, $hedef_dosya);
            break;
    }

    // Kaynakları temizle
    imagedestroy($kaynak);

    // Eski profil resmini sil
    $sql = "SELECT profil_resmi FROM kullanicilar WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $eski_resim = $result->fetch_assoc()['profil_resmi'];

    if ($eski_resim && file_exists($eski_resim) && $eski_resim !== $hedef_dosya) {
        unlink($eski_resim);
    }

    // Veritabanını güncelle
    $sql = "UPDATE kullanicilar SET profil_resmi = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hedef_dosya, $kullanici_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Veritabanı güncellenirken bir hata oluştu!');
    }

    // Session'ı güncelle
    $_SESSION['profil_resmi'] = $hedef_dosya;

    echo json_encode([
        'success' => true,
        'message' => 'Profil resmi başarıyla güncellendi!',
        'yeni_resim' => $hedef_dosya
    ]);

} catch (Exception $e) {
    // Hata durumunda yüklenen dosyayı sil
    if (file_exists($hedef_dosya)) {
        unlink($hedef_dosya);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 