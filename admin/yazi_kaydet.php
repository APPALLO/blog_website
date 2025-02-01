<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim($_POST['baslik']);
    $icerik = $_POST['icerik'];
    $kategori_id = (int)$_POST['kategori_id'];
    $etiketler = trim($_POST['etiketler']);
    $ozet = trim($_POST['ozet']);
    $durum = $_POST['durum'];
    $yazar_id = $_SESSION['admin']['id'];
    
    // Kapak resmi yükleme işlemi
    $kapak_resmi = null;
    if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] === UPLOAD_ERR_OK) {
        $gecici_dosya = $_FILES['kapak_resmi']['tmp_name'];
        $dosya_adi = $_FILES['kapak_resmi']['name'];
        $dosya_uzantisi = strtolower(pathinfo($dosya_adi, PATHINFO_EXTENSION));
        
        // İzin verilen dosya türleri
        $izin_verilen_turler = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($dosya_uzantisi, $izin_verilen_turler)) {
            // Benzersiz dosya adı oluştur
            $yeni_dosya_adi = uniqid('kapak_') . '.' . $dosya_uzantisi;
            $hedef_yol = '../uploads/kapak_resimleri/' . $yeni_dosya_adi;
            
            // Resmi optimize et
            if ($dosya_uzantisi === 'jpg' || $dosya_uzantisi === 'jpeg') {
                $resim = imagecreatefromjpeg($gecici_dosya);
            } elseif ($dosya_uzantisi === 'png') {
                $resim = imagecreatefrompng($gecici_dosya);
            } elseif ($dosya_uzantisi === 'webp') {
                $resim = imagecreatefromwebp($gecici_dosya);
            }
            
            // Resmi yeniden boyutlandır (max 1200x800)
            $kaynak_genislik = imagesx($resim);
            $kaynak_yukseklik = imagesy($resim);
            $max_genislik = 1200;
            $max_yukseklik = 800;
            
            if ($kaynak_genislik > $max_genislik || $kaynak_yukseklik > $max_yukseklik) {
                $oran = min($max_genislik / $kaynak_genislik, $max_yukseklik / $kaynak_yukseklik);
                $yeni_genislik = round($kaynak_genislik * $oran);
                $yeni_yukseklik = round($kaynak_yukseklik * $oran);
                
                $yeni_resim = imagecreatetruecolor($yeni_genislik, $yeni_yukseklik);
                
                // PNG ve WebP için şeffaflığı koru
                if ($dosya_uzantisi === 'png' || $dosya_uzantisi === 'webp') {
                    imagealphablending($yeni_resim, false);
                    imagesavealpha($yeni_resim, true);
                }
                
                imagecopyresampled(
                    $yeni_resim, $resim,
                    0, 0, 0, 0,
                    $yeni_genislik, $yeni_yukseklik,
                    $kaynak_genislik, $kaynak_yukseklik
                );
                
                // Optimize edilmiş resmi kaydet
                if ($dosya_uzantisi === 'jpg' || $dosya_uzantisi === 'jpeg') {
                    imagejpeg($yeni_resim, $hedef_yol, 85); // 85% kalite
                } elseif ($dosya_uzantisi === 'png') {
                    imagepng($yeni_resim, $hedef_yol, 8); // 8/9 sıkıştırma
                } elseif ($dosya_uzantisi === 'webp') {
                    imagewebp($yeni_resim, $hedef_yol, 85); // 85% kalite
                }
                
                imagedestroy($yeni_resim);
            } else {
                // Boyut uygunsa direkt kaydet
                move_uploaded_file($gecici_dosya, $hedef_yol);
            }
            
            imagedestroy($resim);
            $kapak_resmi = 'uploads/kapak_resimleri/' . $yeni_dosya_adi;
        }
    }
    
    // Yazıyı veritabanına kaydet
    $sql = "INSERT INTO blog_yazilar (baslik, icerik, kategori_id, etiketler, ozet, kapak_resmi, durum, yazar_id, tarih) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssissssi", $baslik, $icerik, $kategori_id, $etiketler, $ozet, $kapak_resmi, $durum, $yazar_id);
    
    if ($stmt->execute()) {
        $yazi_id = $stmt->insert_id;
        $_SESSION['basari'] = "Yazı başarıyla kaydedildi.";
        header("Location: yazilar.php");
    } else {
        $_SESSION['hata'] = "Yazı kaydedilirken bir hata oluştu.";
        header("Location: yazi_ekle.php");
    }
    exit();
}

if ($stmt->execute()) {
    $yazi_id = $db->lastInsertId();
    
    // Aktivite kaydı
    $detay = "\"" . substr($baslik, 0, 50) . "\" başlıklı yazı eklendi";
    aktivite_kaydet(
        $_SESSION['admin_id'],
        AKTIVITE_YAZI_EKLEME,
        $detay,
        'yazilar'
    );
    
    header("Location: yazilar.php?mesaj=eklendi");
    exit();
} 