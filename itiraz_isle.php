<?php
session_start();
require_once('baglan.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kullanici_id']) && isset($_POST['itiraz_mesaji'])) {
    $kullanici_id = intval($_POST['kullanici_id']);
    $itiraz_mesaji = trim(htmlspecialchars($_POST['itiraz_mesaji']));
    
    // Kullanıcının durumunu kontrol et
    $stmt = $conn->prepare("SELECT onay_durumu FROM kullanicilar WHERE id = ?");
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $kullanici = $result->fetch_assoc();
    
    if ($kullanici && $kullanici['onay_durumu'] === 'reddedildi') {
        // İtirazı kaydet
        $sql = "UPDATE kullanicilar SET itiraz_mesaji = ?, itiraz_tarihi = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $itiraz_mesaji, $kullanici_id);
        
        if ($stmt->execute()) {
            $_SESSION['basari'] = "İtirazınız başarıyla kaydedildi. En kısa sürede incelenecektir.";
        } else {
            $_SESSION['hata'] = "İtiraz kaydedilirken bir hata oluştu.";
        }
    } else {
        $_SESSION['hata'] = "İtiraz hakkınız bulunmamaktadır.";
    }
} else {
    $_SESSION['hata'] = "Geçersiz istek!";
}

header("Location: giris.php");
exit(); 