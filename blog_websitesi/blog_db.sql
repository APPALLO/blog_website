-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS blog_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blog_db;

-- Kategoriler tablosu
CREATE TABLE IF NOT EXISTS kategoriler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_adi VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Blog yazıları tablosu
CREATE TABLE IF NOT EXISTS blog_yazilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(255) NOT NULL,
    icerik TEXT NOT NULL,
    kategori_id INT,
    yazar_id INT,
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('taslak', 'yayinda') DEFAULT 'taslak',
    goruntulenme INT DEFAULT 0,
    kapak_resmi VARCHAR(255) DEFAULT NULL,
    one_cikar TINYINT(1) NOT NULL DEFAULT '0',
    FOREIGN KEY (kategori_id) REFERENCES kategoriler(id) ON DELETE SET NULL,
    FOREIGN KEY (yazar_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    ad_soyad VARCHAR(100),
    rol ENUM('admin', 'yazar', 'kullanici') DEFAULT 'kullanici',
    durum TINYINT(1) DEFAULT 1,
    onay_durumu ENUM('bekliyor', 'onaylandi', 'reddedildi') DEFAULT 'bekliyor',
    son_giris TIMESTAMP NULL,
    auth_token VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Yorumlar tablosu
CREATE TABLE IF NOT EXISTS yorumlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    yazi_id INT NOT NULL,
    kullanici_id INT,
    yorum_metni TEXT NOT NULL,
    durum ENUM('onay_bekliyor', 'onaylanmis', 'spam') DEFAULT 'onay_bekliyor',
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (yazi_id) REFERENCES blog_yazilar(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Etiketler tablosu
CREATE TABLE IF NOT EXISTS etiketler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etiket_adi VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Yazı-Etiket ilişki tablosu
CREATE TABLE IF NOT EXISTS yazi_etiketler (
    yazi_id INT NOT NULL,
    etiket_id INT NOT NULL,
    PRIMARY KEY (yazi_id, etiket_id),
    FOREIGN KEY (yazi_id) REFERENCES blog_yazilar(id) ON DELETE CASCADE,
    FOREIGN KEY (etiket_id) REFERENCES etiketler(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- İletişim mesajları tablosu
CREATE TABLE IF NOT EXISTS iletisim_mesajlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    konu VARCHAR(255) NOT NULL,
    mesaj TEXT NOT NULL,
    durum ENUM('okunmamis', 'okunmus', 'yanitlandi') DEFAULT 'okunmamis',
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Giriş denemeleri tablosu
CREATE TABLE IF NOT EXISTS giris_denemeleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_adresi VARCHAR(45) NOT NULL,
    kullanici_adi VARCHAR(50),
    deneme_sayisi INT DEFAULT 1,
    son_deneme TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    engelleme_suresi TIMESTAMP NULL,
    INDEX (ip_adresi),
    INDEX (kullanici_adi)
) ENGINE=InnoDB;

-- Örnek veriler
INSERT INTO kategoriler (kategori_adi, slug) VALUES
('Teknoloji', 'teknoloji'),
('Yaşam', 'yasam'),
('Seyahat', 'seyahat'),
('Spor', 'spor');

-- Admin kullanıcısı oluştur (şifre: admin123)
INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, rol) VALUES
('admin', 'admin@blogsite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Site Yöneticisi', 'admin'); 