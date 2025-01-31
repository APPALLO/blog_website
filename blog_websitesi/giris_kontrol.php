<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'baglan.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = trim($_POST['sifre']);

    if (empty($kullanici_adi) || empty($sifre)) {
        $_SESSION['hata'] = "Kullanıcı adı ve şifre gereklidir.";
        header("Location: giris.php");
        exit();
    }

    // Hata ayıklama için log dosyasına yazalım
    error_log("Giriş denemesi - Kullanıcı adı: " . $kullanici_adi);

    // SQL sorgusunu düzeltelim ve hata ayıklama ekleyelim
    $sql = "SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND durum = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kullanici_adi);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $kullanici = $result->fetch_assoc();
        
        // Şifreyi kontrol et
        if (password_verify($sifre, $kullanici['sifre'])) {
            // Onay durumunu kontrol et
            if ($kullanici['onay_durumu'] !== 'onaylandi' && $kullanici['rol'] !== 'admin') {
                if ($kullanici['onay_durumu'] === 'bekliyor') {
                    $_SESSION['hata'] = "Hesabınız henüz onaylanmamış. Lütfen admin onayını bekleyin.";
                } else if ($kullanici['onay_durumu'] === 'reddedildi') {
                    $_SESSION['hata'] = "Hesabınız reddedildi. Lütfen site yöneticisi ile iletişime geçin.";
                }
                header("Location: giris.php");
                exit();
            }
            
            // Giriş başarılı
            $_SESSION['kullanici_id'] = $kullanici['id'];
            $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
            $_SESSION['ad_soyad'] = $kullanici['ad_soyad'];
            $_SESSION['rol'] = $kullanici['rol'];

            error_log("Giriş başarılı - Kullanıcı ID: " . $kullanici['id']);

            // Son giriş tarihini güncelle
            $sql = "UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $kullanici['id']);
            $stmt->execute();

            // Beni hatırla kontrolü
            if (isset($_POST['beni_hatirla'])) {
                try {
                    $token = bin2hex(random_bytes(32));
                    setcookie('hatirla_token', $token, time() + (86400 * 30), "/"); // 30 gün
                    
                    // Token'ı veritabanına kaydet
                    $sql = "UPDATE kullanicilar SET hatirla_token = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $token, $kullanici['id']);
                    $stmt->execute();
                } catch (Exception $e) {
                    error_log("Token oluşturma hatası: " . $e->getMessage());
                }
            }

            // Admin ise admin paneline yönlendir
            if ($kullanici['rol'] == 'admin') {
                $_SESSION['admin_id'] = $kullanici['id'];
                header("Location: admin/panel.php");
            } else {
                header("Location: index.php");
            }

            exit();
        } else {
            error_log("Şifre doğrulama başarısız - Kullanıcı adı: " . $kullanici_adi);
            $_SESSION['hata'] = "Kullanıcı adı veya şifre hatalı!";
        }
    } else {
        error_log("Kullanıcı bulunamadı - Kullanıcı adı: " . $kullanici_adi);
        $_SESSION['hata'] = "Kullanıcı adı veya şifre hatalı!";
    }

    header("Location: giris.php");
    exit();
} else {
    $_SESSION['hata'] = "Geçersiz istek!";
    error_log("Geçersiz istek metodu: " . $_SERVER['REQUEST_METHOD']);
}

header("Location: giris.php");
exit();
?>