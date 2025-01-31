<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'baglan.php';

// Form verilerini al ve temizle
$ad_soyad = trim(htmlspecialchars($_POST['ad_soyad']));
$kullanici_adi = trim(htmlspecialchars($_POST['kullanici_adi']));
$email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
$sifre = $_POST['sifre'];
$sifre_tekrar = $_POST['sifre_tekrar'];

// Temel doğrulamalar
if (empty($ad_soyad) || empty($kullanici_adi) || empty($email) || empty($sifre)) {
    $_SESSION['hata'] = "Tüm alanları doldurun.";
    header("Location: kayit.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['hata'] = "Geçerli bir e-posta adresi girin.";
    header("Location: kayit.php");
    exit();
}

if ($sifre !== $sifre_tekrar) {
    $_SESSION['hata'] = "Şifreler eşleşmiyor.";
    header("Location: kayit.php");
    exit();
}

if (strlen($sifre) < 6) {
    $_SESSION['hata'] = "Şifre en az 6 karakter olmalıdır.";
    header("Location: kayit.php");
    exit();
}

// Kullanıcı adı ve email kontrolü
$sql = "SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $kullanici_adi, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['hata'] = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
    header("Location: kayit.php");
    exit();
}

// Şifreyi hashle
$sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

// Kullanıcıyı kaydet
$sql = "INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, rol, durum, onay_durumu, created_at) 
        VALUES (?, ?, ?, ?, 'kullanici', 1, 'bekliyor', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $kullanici_adi, $email, $sifre_hash, $ad_soyad);

if ($stmt->execute()) {
    $_SESSION['basari'] = "Kayıt başarıyla tamamlandı. Hesabınız admin onayı bekliyor. Onaylandıktan sonra giriş yapabilirsiniz.";
    header("Location: giris.php");
} else {
    $_SESSION['hata'] = "Kayıt sırasında bir hata oluştu: " . $conn->error;
    header("Location: kayit.php");
}

$stmt->close();
$conn->close();
?> 