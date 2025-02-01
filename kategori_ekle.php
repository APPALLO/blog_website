<?php
session_start();
require_once('baglan.php');

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    $_SESSION['hata'] = "Bu işlemi gerçekleştirmek için giriş yapmalısınız.";
    header("Location: kategoriler.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori_adi = trim(htmlspecialchars($_POST['kategori_adi']));
    $aciklama = trim(htmlspecialchars($_POST['aciklama']));
    $ikon = trim(htmlspecialchars($_POST['ikon']));
    $olusturan_id = $_SESSION['kullanici_id'];

    // Kategori adı boş mu kontrol et
    if (empty($kategori_adi)) {
        $_SESSION['hata'] = "Kategori adı boş olamaz.";
        header("Location: kategoriler.php");
        exit();
    }

    try {
        // Kategori adının benzersiz olup olmadığını kontrol et
        $kontrol_sql = "SELECT id FROM kategoriler WHERE kategori_adi = ?";
        $stmt = $conn->prepare($kontrol_sql);
        $stmt->bind_param("s", $kategori_adi);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['hata'] = "Bu kategori adı zaten kullanılıyor.";
            header("Location: kategoriler.php");
            exit();
        }

        // SEO URL oluştur
        function seo_url($str) {
            $str = mb_strtolower($str, 'UTF-8');
            $str = str_replace(
                ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', ' ', '_'],
                ['i', 'g', 'u', 's', 'o', 'c', '-', '-'],
                $str
            );
            $str = preg_replace('/[^a-z0-9\-]/', '', $str);
            $str = preg_replace('/-+/', '-', $str);
            return trim($str, '-');
        }

        $seo_url = seo_url($kategori_adi);

        // En yüksek sıra numarasını bul
        $sira_sql = "SELECT MAX(sira) as max_sira FROM kategoriler";
        $sira_result = $conn->query($sira_sql);
        $max_sira = $sira_result->fetch_assoc()['max_sira'];
        $yeni_sira = $max_sira ? $max_sira + 1 : 1;

        // Kategoriyi ekle
        $ekle_sql = "INSERT INTO kategoriler (kategori_adi, seo_url, aciklama, ikon, olusturan_id, sira, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($ekle_sql);
        $stmt->bind_param("ssssii", $kategori_adi, $seo_url, $aciklama, $ikon, $olusturan_id, $yeni_sira);
        
        if ($stmt->execute()) {
            $_SESSION['basari'] = "Kategori başarıyla eklendi.";
        } else {
            throw new Exception("Veritabanı hatası: " . $stmt->error);
        }

    } catch (Exception $e) {
        error_log("Kategori Ekleme Hatası: " . $e->getMessage());
        $_SESSION['hata'] = "Kategori eklenirken bir hata oluştu: " . $e->getMessage();
    }
}

header("Location: kategoriler.php");
exit(); 