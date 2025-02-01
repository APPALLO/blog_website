<?php
// Session başlatma kodu kaldırıldı çünkü ana dosyalarda başlatılıyor

include 'baglan.php';

// Veritabanı yapısını kontrol et ve güncelle
$sql = "SHOW COLUMNS FROM kullanicilar LIKE 'son_sayfa'";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    // son_sayfa sütunu yoksa ekle
    $sql = "ALTER TABLE kullanicilar 
            ADD COLUMN son_sayfa VARCHAR(255) DEFAULT NULL AFTER son_aktivite";
    $conn->query($sql);
}

// Kullanıcı giriş yapmışsa son aktivite zamanını güncelle
if (isset($_SESSION['kullanici_id'])) {
    $kullanici_id = $_SESSION['kullanici_id'];
    $sql = "UPDATE kullanicilar SET son_aktivite = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
}

// Kullanıcının son aktivite detaylarını güncelle
function aktivite_guncelle($kullanici_id, $sayfa = null) {
    global $conn;
    $sql = "UPDATE kullanicilar SET 
            son_aktivite = NOW(),
            cevrimici_durum = 1,
            son_sayfa = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $sayfa, $kullanici_id);
    $stmt->execute();
}

// Çevrimiçi durumu kontrol et (1 dakika içinde aktivite varsa çevrimiçi sayılır)
function cevrimici_kontrol() {
    global $conn;
    // Önce çevrimiçi durumları sıfırla
    $sql = "UPDATE kullanicilar SET cevrimici_durum = 0";
    $conn->query($sql);
    
    // Son 1 dakika içinde aktif olanları çevrimiçi yap
    $sql = "UPDATE kullanicilar 
            SET cevrimici_durum = 1 
            WHERE son_aktivite >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
    $conn->query($sql);
}

// Çevrimiçi kullanıcıları detaylı bilgilerle al
function cevrimici_kullanicilar_detayli() {
    global $conn;
    $sql = "SELECT 
            k.id, 
            k.kullanici_adi, 
            k.ad_soyad, 
            k.rol, 
            k.son_aktivite,
            k.son_sayfa,
            k.profil_resmi,
            TIMESTAMPDIFF(SECOND, k.son_aktivite, NOW()) as son_aktivite_sure
            FROM kullanicilar k
            WHERE k.cevrimici_durum = 1 
            ORDER BY k.son_aktivite DESC";
    return $conn->query($sql);
}

// Kullanıcının çevrimiçi durumunu JSON olarak döndür
function kullanici_durum_getir($kullanici_id) {
    global $conn;
    $sql = "SELECT 
            cevrimici_durum,
            TIMESTAMPDIFF(SECOND, son_aktivite, NOW()) as son_aktivite_sure,
            son_sayfa
            FROM kullanicilar 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// AJAX istekleri için durum kontrolü
if(isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch($_POST['action']) {
        case 'durum_guncelle':
            if(isset($_SESSION['kullanici_id'])) {
                $sayfa = isset($_POST['sayfa']) ? $_POST['sayfa'] : '';
                aktivite_guncelle($_SESSION['kullanici_id'], $sayfa);
                cevrimici_kontrol(); // Her güncelleme sonrası kontrol et
                echo json_encode(['success' => true]);
            }
            break;
            
        case 'cevrimici_kullanicilar':
            cevrimici_kontrol(); // Liste alınmadan önce kontrol et
            $kullanicilar = cevrimici_kullanicilar_detayli();
            $liste = [];
            while($k = $kullanicilar->fetch_assoc()) {
                $liste[] = [
                    'id' => $k['id'],
                    'kullanici_adi' => $k['kullanici_adi'],
                    'ad_soyad' => $k['ad_soyad'],
                    'rol' => $k['rol'],
                    'son_aktivite_sure' => $k['son_aktivite_sure'],
                    'son_sayfa' => $k['son_sayfa'],
                    'profil_resmi' => $k['profil_resmi']
                ];
            }
            echo json_encode($liste);
            break;
    }
    exit;
}

// Tüm kullanıcıların çevrimiçi durumunu kontrol et
cevrimici_kontrol();

// Çevrimiçi kullanıcı sayısını al
function cevrimici_kullanici_sayisi() {
    global $conn;
    $sql = "SELECT COUNT(*) as sayi FROM kullanicilar WHERE cevrimici_durum = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['sayi'];
}

// Çevrimiçi kullanıcıları al
function cevrimici_kullanicilar() {
    global $conn;
    $sql = "SELECT id, kullanici_adi, ad_soyad, rol, son_aktivite 
            FROM kullanicilar 
            WHERE cevrimici_durum = 1 
            ORDER BY son_aktivite DESC";
    return $conn->query($sql);
}
?> 