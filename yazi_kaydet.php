<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

include 'baglan.php';

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['hata'] = "Geçersiz istek yöntemi.";
    header("Location: yazi_ekle.php");
    exit();
}

// Form verilerini al ve temizle
$baslik = trim(htmlspecialchars($_POST['baslik']));
$ozet = trim(htmlspecialchars($_POST['ozet']));
$icerik = trim($_POST['icerik']);
$kategori_id = (int)$_POST['kategori_id'];
$durum = $_POST['durum'];
$seo_url = trim(htmlspecialchars($_POST['seo_url']));

// SEO URL boşsa başlıktan oluştur
if (empty($seo_url)) {
    $seo_url = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $baslik), '-'));
}

// Validasyon
if (empty($baslik) || empty($icerik) || empty($kategori_id)) {
    $_SESSION['hata'] = "Lütfen tüm zorunlu alanları doldurun.";
    $_SESSION['form_data'] = $_POST; // Form verilerini sakla
    header("Location: yazi_ekle.php");
    exit();
}

try {
    // Kapak resmini yükle
    $kapak_resmi = '';
    if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['kapak_resmi']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            throw new Exception("Geçersiz dosya formatı. Sadece JPG, JPEG, PNG ve WEBP dosyaları yüklenebilir.");
        }

        $yeni_isim = 'kapak_' . time() . '_' . uniqid() . '.' . $dosya_uzantisi;
        $hedef_dizin = 'uploads/kapak/';
        
        if (!file_exists($hedef_dizin)) {
            mkdir($hedef_dizin, 0777, true);
        }
        
        if (!move_uploaded_file($_FILES['kapak_resmi']['tmp_name'], $hedef_dizin . $yeni_isim)) {
            throw new Exception("Dosya yüklenirken bir hata oluştu.");
        }
        
        $kapak_resmi = $hedef_dizin . $yeni_isim;
    }

    $db->beginTransaction();

    // Yazıyı ekle
    $sql = "INSERT INTO blog_yazilar (baslik, ozet, icerik, kategori_id, yazar_id, kapak_resmi, seo_url, durum, tarih) 
            VALUES (:baslik, :ozet, :icerik, :kategori_id, :yazar_id, :kapak_resmi, :seo_url, :durum, NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':baslik' => $baslik,
        ':ozet' => $ozet,
        ':icerik' => $icerik,
        ':kategori_id' => $kategori_id,
        ':yazar_id' => $_SESSION['kullanici_id'],
        ':kapak_resmi' => $kapak_resmi,
        ':seo_url' => $seo_url,
        ':durum' => $durum
    ]);
    
    $yazi_id = $db->lastInsertId();

    // Etiketleri ekle
    if (!empty($_POST['etiketler'])) {
        foreach ($_POST['etiketler'] as $etiket) {
            if (is_numeric($etiket)) {
                // Mevcut etiket
                $etiket_id = $etiket;
            } else {
                // Yeni etiket ekle
                $etiket_seo = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $etiket), '-'));
                
                $stmt = $db->prepare("INSERT INTO etiketler (etiket_adi, seo_url) VALUES (:etiket, :seo_url)");
                $stmt->execute([':etiket' => $etiket, ':seo_url' => $etiket_seo]);
                $etiket_id = $db->lastInsertId();
            }
            
            // Yazı-etiket ilişkisini ekle
            $stmt = $db->prepare("INSERT IGNORE INTO yazi_etiketler (yazi_id, etiket_id) VALUES (:yazi_id, :etiket_id)");
            $stmt->execute([':yazi_id' => $yazi_id, ':etiket_id' => $etiket_id]);
        }
    }

    // Taslağı sil
    $stmt = $db->prepare("DELETE FROM yazi_taslaklar WHERE kullanici_id = :kullanici_id");
    $stmt->execute([':kullanici_id' => $_SESSION['kullanici_id']]);

    $db->commit();
    
    $_SESSION['basari'] = "Yazı başarıyla eklendi.";
    header("Location: yazilarim.php");
    exit();

} catch (Exception $e) {
    $db->rollBack();
    
    // Yüklenen resmi sil
    if (!empty($kapak_resmi) && file_exists($kapak_resmi)) {
        unlink($kapak_resmi);
    }
    
    error_log("Yazı ekleme hatası: " . $e->getMessage());
    $_SESSION['hata'] = "Yazı eklenirken bir hata oluştu: " . $e->getMessage();
    $_SESSION['form_data'] = $_POST; // Form verilerini sakla
    header("Location: yazi_ekle.php");
    exit();
} 