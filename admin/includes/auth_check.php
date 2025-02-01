<?php
// Oturum kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: giris.php");
    exit();
}

// Admin rolü kontrolü
if (!isset($_SESSION['admin']['rol']) || $_SESSION['admin']['rol'] !== 'admin') {
    header("Location: giris.php?hata=yetki");
    exit();
}

// Admin bilgilerini al
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM kullanicilar WHERE id = ? AND rol = 'admin' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Admin yetkisi kontrolü
if (!$admin) {
    session_destroy();
    header("Location: ../admin/index.php?hata=yetkisiz");
    exit();
}

// Ad ve soyad bilgilerini parçala
$ad_soyad_parcalari = explode(' ', $admin['ad_soyad']);
$ad = $ad_soyad_parcalari[0];
$soyad = isset($ad_soyad_parcalari[1]) ? implode(' ', array_slice($ad_soyad_parcalari, 1)) : '';

// Admin bilgilerini session'a kaydet
$_SESSION['admin'] = [
    'id' => $admin['id'],
    'kullanici_adi' => $admin['kullanici_adi'],
    'ad_soyad' => $admin['ad_soyad'],
    'ad' => $ad,
    'soyad' => $soyad,
    'email' => $admin['email'],
    'rol' => $admin['rol'],
    'unvan' => $admin['unvan'] ?? 'Admin',
    'son_giris' => $admin['son_giris'],
    'profil_resmi' => $admin['profil_resmi'] ?? null
];

// CSRF token kontrolü
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Oturum süresini kontrol et ve yenile
if (isset($_SESSION['son_aktivite']) && (time() - $_SESSION['son_aktivite'] > 1800)) {
    // 30 dakika boyunca işlem yapılmadıysa oturumu sonlandır
    session_destroy();
    header("Location: ../admin/index.php?hata=timeout");
    exit();
}
$_SESSION['son_aktivite'] = time();
?> 