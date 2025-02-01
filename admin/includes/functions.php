<?php
// ... existing code ...

/**
 * Kullanıcı aktivitelerini kaydeder
 * @param int $kullanici_id Kullanıcı ID (null ise misafir)
 * @param string $aktivite_tipi Aktivite türü (örn: giris, yazi_ekleme, yorum)
 * @param string $detay Aktivite detayı
 * @param string $modul İlgili modül (örn: yazilar, yorumlar, kullanicilar)
 * @return bool İşlem başarılı/başarısız
 */
function aktivite_kaydet($kullanici_id = null, $aktivite_tipi, $detay, $modul = null) {
    global $db;
    
    try {
        $ip_adresi = $_SERVER['REMOTE_ADDR'];
        $tarayici = $_SERVER['HTTP_USER_AGENT'];
        
        $sql = "INSERT INTO aktiviteler (kullanici_id, aktivite_tipi, detay, modul, ip_adresi, tarayici, tarih) 
                VALUES (:kullanici_id, :aktivite_tipi, :detay, :modul, :ip_adresi, :tarayici, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'kullanici_id' => $kullanici_id,
            'aktivite_tipi' => $aktivite_tipi,
            'detay' => $detay,
            'modul' => $modul,
            'ip_adresi' => $ip_adresi,
            'tarayici' => $tarayici
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Aktivite kaydetme hatası: " . $e->getMessage());
        return false;
    }
}

// Aktivite tiplerini sabit olarak tanımla
define('AKTIVITE_GIRIS', 'giris');
define('AKTIVITE_CIKIS', 'cikis');
define('AKTIVITE_YAZI_EKLEME', 'yazi_ekleme');
define('AKTIVITE_YAZI_DUZENLEME', 'yazi_duzenleme');
define('AKTIVITE_YAZI_SILME', 'yazi_silme');
define('AKTIVITE_YORUM_EKLEME', 'yorum_ekleme');
define('AKTIVITE_YORUM_ONAYLAMA', 'yorum_onaylama');
define('AKTIVITE_YORUM_SILME', 'yorum_silme');
define('AKTIVITE_KATEGORI_EKLEME', 'kategori_ekleme');
define('AKTIVITE_KATEGORI_DUZENLEME', 'kategori_duzenleme');
define('AKTIVITE_KATEGORI_SILME', 'kategori_silme');
define('AKTIVITE_KULLANICI_EKLEME', 'kullanici_ekleme');
define('AKTIVITE_KULLANICI_DUZENLEME', 'kullanici_duzenleme');
define('AKTIVITE_KULLANICI_SILME', 'kullanici_silme');
define('AKTIVITE_AYAR_GUNCELLEME', 'ayar_guncelleme');

// ... existing code ... 