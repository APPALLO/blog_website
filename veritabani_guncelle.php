<?php
require_once 'baglan.php';

try {
    // Kullanıcılar tablosuna gerekli sütunları ekle
    $sql = "ALTER TABLE kullanicilar 
            ADD COLUMN IF NOT EXISTS kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN IF NOT EXISTS son_giris TIMESTAMP NULL,
            ADD COLUMN IF NOT EXISTS son_aktivite TIMESTAMP NULL,
            ADD COLUMN IF NOT EXISTS cevrimici_durum TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN IF NOT EXISTS durum TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN IF NOT EXISTS profil_resmi VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS hatirla_token VARCHAR(64) NULL";

    $db->exec($sql);
    echo "Veritabanı başarıyla güncellendi!<br>";

    // Mevcut tabloyu kontrol et
    $result = $db->query("SHOW COLUMNS FROM kullanicilar");
    echo "<br>Güncel tablo yapısı:<br>";
    while ($row = $result->fetch()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }

    // Site ayarları tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS site_ayarlari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_baslik VARCHAR(255) NOT NULL,
        site_aciklama TEXT,
        site_anahtar_kelimeler TEXT,
        site_logo VARCHAR(255),
        site_favicon VARCHAR(255),
        iletisim_email VARCHAR(255),
        iletisim_telefon VARCHAR(50),
        iletisim_adres TEXT,
        facebook_url VARCHAR(255),
        twitter_url VARCHAR(255),
        instagram_url VARCHAR(255),
        footer_yazisi VARCHAR(255),
        analytics_kodu TEXT,
        smtp_host VARCHAR(255),
        smtp_port VARCHAR(10),
        smtp_email VARCHAR(255),
        smtp_sifre VARCHAR(255),
        bakim_modu TINYINT(1) NOT NULL DEFAULT 0
    )";

    $db->exec($sql);
    
    // Varsayılan site ayarlarını ekle
    $sql = "INSERT IGNORE INTO site_ayarlari (id, site_baslik) VALUES (1, 'Blog Sitesi')";
    $db->exec($sql);
    echo "Site ayarları tablosu oluşturuldu!<br>";

    // İletişim mesajları tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS iletisim_mesajlari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        konu VARCHAR(255) NOT NULL,
        mesaj TEXT NOT NULL,
        tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        durum ENUM('okunmamis', 'okunmus', 'yanitlandi') NOT NULL DEFAULT 'okunmamis'
    )";

    $db->exec($sql);
    echo "İletişim mesajları tablosu oluşturuldu!<br>";

    // Aktiviteler tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS aktiviteler (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT,
        aktivite TEXT NOT NULL,
        tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_adresi VARCHAR(45),
        FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
    )";

    $db->exec($sql);
    echo "Aktiviteler tablosu başarıyla oluşturuldu veya zaten mevcut.<br>";

} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage() . "<br>";
}

?> 