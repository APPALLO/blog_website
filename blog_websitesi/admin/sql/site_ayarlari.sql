-- Site ayarları tablosu
CREATE TABLE IF NOT EXISTS `site_ayarlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_baslik` varchar(255) NOT NULL,
  `site_aciklama` text DEFAULT NULL,
  `site_anahtar_kelimeler` text DEFAULT NULL,
  `site_logo` varchar(255) DEFAULT NULL,
  `site_favicon` varchar(255) DEFAULT NULL,
  `site_email` varchar(255) DEFAULT NULL,
  `site_telefon` varchar(50) DEFAULT NULL,
  `site_adres` text DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Varsayılan ayarları ekle
INSERT INTO `site_ayarlari` (`id`, `site_baslik`, `site_aciklama`, `site_anahtar_kelimeler`) 
VALUES (1, 'Blog Sitesi', 'Blog sitesi açıklaması', 'blog, yazılar, kategoriler')
ON DUPLICATE KEY UPDATE `id` = 1; 