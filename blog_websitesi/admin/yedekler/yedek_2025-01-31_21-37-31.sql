DROP TABLE IF EXISTS adminler;

CREATE TABLE `adminler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_adi` varchar(50) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `ad_soyad` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `son_giris` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO adminler VALUES("1","admin","/T8tzAoHsVuEUQBtJfNLqhx.Aqm","Admin Yönetici","admin@admin.com","");



DROP TABLE IF EXISTS blog_yazilar;

CREATE TABLE `blog_yazilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `baslik` varchar(255) NOT NULL,
  `seo_url` varchar(255) DEFAULT NULL,
  `icerik` text NOT NULL,
  `meta_aciklama` text DEFAULT NULL,
  `ozet` text DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `yazar_id` int(11) DEFAULT NULL,
  `resim_url` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `goruntulenme` int(11) DEFAULT 0,
  `durum` enum('taslak','yayinda') DEFAULT 'taslak',
  `tarih` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `kapak_resmi` varchar(255) DEFAULT NULL,
  `one_cikar` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_url` (`seo_url`),
  KEY `kategori_id` (`kategori_id`),
  CONSTRAINT `blog_yazilar_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO blog_yazilar VALUES("3","DeepSeek-v3","deepseek-v3","<p>DeepSeek-V3, Çin merkezli yapay zekâ araştırma laboratuvarı DeepSeek tarafından Aralık 2024\'te tanıtılan, Mixture-of-Experts (MoE) mimarisine sahip, 671 milyar parametreli bir dil modelidir. Bu model, her bir token için 37 milyar parametreyi etkinleştirerek çalışır.</p><p>&nbsp;</p><p><strong>Eğitim Süreci ve Maliyetler:</strong></p><p><strong>Veri Seti:</strong> Model, çoğunluğu İngilizce ve Çince olan 14,8 trilyon tokenlık çok dilli bir veri seti üzerinde ön eğitim aldı. Bu veri seti, matematik ve programlama verilerini önceki sürümlere göre daha yüksek oranda içerir.</p><p><strong>Bağlam Uzunluğu:</strong> YaRN yöntemi kullanılarak bağlam uzunluğu önce 4K\'dan 32K\'ya, ardından 128K\'ya uzatıldı.</p><p><strong>İnce Ayar:</strong> Model, 1,5 milyon örnekten oluşan bir veri seti üzerinde iki dönem boyunca ince ayarlandı. Bu veri seti, matematik, programlama ve mantık gibi alanlarda \"uzman modeller\" tarafından üretilen verileri içerir.</p><p><strong>Maliyet:</strong> Modelin eğitimi için toplamda 2.788 milyon H800 GPU saati ve yaklaşık 5,6 milyon dolar harcandı. Bu, benzer modellerle karşılaştırıldığında oldukça maliyet etkin bir süreçtir.</p><p>&nbsp;</p><p><strong>Performans ve Erişilebilirlik:</strong></p><p>DeepSeek-V3, popüler yapay zekâ kıyaslama testlerinde üstün sonuçlar elde ederek, hem açık kaynaklı hem de kapalı kaynaklı modelleri geride bırakmıştır. Model, GitHub ve HuggingFace platformları üzerinden indirilebilir durumdadır. Ayrıca, kullanım maliyetleri de oldukça düşüktür; giriş maliyeti milyon token başına 0,27 dolar, çıkış maliyeti ise 1,10 dolar olarak belirlenmiştir. Bu fiyatlandırma, diğer büyük yapay zekâ şirketlerinin modellerine kıyasla neredeyse onda bir oranında daha ucuzdur.</p><p>&nbsp;</p><p><strong>Tepkiler ve Piyasa Etkileri:</strong></p><p>DeepSeek-V3\'ün tanıtımı, teknoloji dünyasında büyük yankı uyandırdı. Modelin, OpenAI ve Meta gibi şirketlerin ürünlerini geride bırakması, teknoloji hisselerinde büyük düşüşlere neden oldu. Özellikle Nvidia, piyasa değerinde önemli kayıplar yaşadı. Ancak, bazı uzmanlar bu tepkilerin abartılı olduğunu ve DeepSeek\'in ilerlemelerinin beklenen maliyet düşüşü eğrisine uygun olduğunu belirtti.</p><p>&nbsp;</p><p>DeepSeek-V3, yüksek performansı ve maliyet etkinliği ile yapay zekâ alanında önemli bir yenilik olarak kabul edilmektedir. Açık kaynaklı yapısı sayesinde, araştırmacılar ve geliştiriciler için geniş bir erişim imkânı sunmaktadır.</p>","","","1","1","","","11","yayinda","2025-01-31 15:09:25","2025-01-31 20:12:11","","0");



DROP TABLE IF EXISTS etiketler;

CREATE TABLE `etiketler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `etiket_adi` varchar(50) NOT NULL,
  `seo_url` varchar(255) NOT NULL,
  `kullanim_sayisi` int(11) DEFAULT 0,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `etiket_adi` (`etiket_adi`),
  UNIQUE KEY `seo_url` (`seo_url`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO etiketler VALUES("1","teknoloji","teknoloji","1","2025-01-31 18:06:16");
INSERT INTO etiketler VALUES("2","yapayzeka","yapayzeka","1","2025-01-31 18:06:16");



DROP TABLE IF EXISTS giris_denemeleri;

CREATE TABLE `giris_denemeleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_adresi` varchar(45) NOT NULL,
  `kullanici_adi` varchar(50) DEFAULT NULL,
  `deneme_sayisi` int(11) DEFAULT 1,
  `son_deneme` timestamp NOT NULL DEFAULT current_timestamp(),
  `engelleme_suresi` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_adresi` (`ip_adresi`),
  KEY `kullanici_adi` (`kullanici_adi`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO giris_denemeleri VALUES("4","::1","admin","5","2025-01-31 21:27:20","");
INSERT INTO giris_denemeleri VALUES("5","::1","admin","1","2025-01-31 21:28:21","");
INSERT INTO giris_denemeleri VALUES("6","::1","admin","1","2025-01-31 21:28:24","");



DROP TABLE IF EXISTS iletisim_mesajlari;

CREATE TABLE `iletisim_mesajlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `konu` varchar(255) NOT NULL,
  `mesaj` text NOT NULL,
  `durum` enum('okunmamis','okunmus','yanitlandi') DEFAULT 'okunmamis',
  `tarih` timestamp NOT NULL DEFAULT current_timestamp(),
  `yanit_tarihi` datetime DEFAULT NULL,
  `yanit_mesaji` text DEFAULT NULL,
  `yanit_gonderen_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS kategoriler;

CREATE TABLE `kategoriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sira` int(11) DEFAULT 0,
  `kategori_adi` varchar(100) NOT NULL,
  `seo_url` varchar(255) DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `aciklama` text DEFAULT NULL,
  `ikon` varchar(50) DEFAULT NULL,
  `olusturan_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_url` (`seo_url`),
  KEY `olusturan_id` (`olusturan_id`),
  CONSTRAINT `kategoriler_ibfk_1` FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kategoriler VALUES("1","1","Teknoloji","teknoloji","teknoloji","2025-01-30 22:44:02","","","");
INSERT INTO kategoriler VALUES("2","2","Yaşam","yasam","yasam","2025-01-30 22:44:02","","","");
INSERT INTO kategoriler VALUES("3","3","Seyahat","seyahat","seyahat","2025-01-30 22:44:02","","","");
INSERT INTO kategoriler VALUES("4","4","Spor","spor","spor","2025-01-30 22:44:02","","","");



DROP TABLE IF EXISTS kullanici_durum;

CREATE TABLE `kullanici_durum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) DEFAULT NULL,
  `son_aktivite` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `durum` enum('online','offline') DEFAULT 'offline',
  `bot_kontrolu` enum('insan','bot','belirsiz') DEFAULT 'belirsiz',
  PRIMARY KEY (`id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `kullanici_durum_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS kullanici_mesajlari;

CREATE TABLE `kullanici_mesajlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `mesaj` text NOT NULL,
  `mesaj_tarihi` datetime DEFAULT current_timestamp(),
  `gonderen_tip` enum('kullanici','admin') NOT NULL,
  `okundu` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `kullanici_mesajlari_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS kullanicilar;

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_adi` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `ad_soyad` varchar(100) DEFAULT NULL,
  `rol` enum('admin','yazar','kullanici') DEFAULT 'kullanici',
  `durum` tinyint(1) DEFAULT 1,
  `onay_durumu` enum('bekliyor','onaylandi','reddedildi') DEFAULT 'bekliyor',
  `itiraz_mesaji` text DEFAULT NULL,
  `itiraz_tarihi` timestamp NULL DEFAULT NULL,
  `son_giris` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `auth_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hatirla_token` varchar(64) DEFAULT NULL,
  `son_aktivite` timestamp NULL DEFAULT NULL,
  `cevrimici_durum` tinyint(1) NOT NULL DEFAULT 0,
  `kayit_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `profil_resmi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kullanicilar VALUES("22","admin","emirhansakar2@gmail.com","$2y$10$rbXQFfV6Fc1td0yhyvfileYeW99VZYftEygqZ2BxQqBtHESi3hGCe","emirhan Şakar","admin","1","onaylandi","","","2025-01-31 21:55:52","","2025-01-31 21:31:27","","2025-01-31 21:36:12","0","2025-01-31 21:31:27","");



DROP TABLE IF EXISTS sifre_sifirlama;

CREATE TABLE `sifre_sifirlama` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `son_gecerlilik` datetime NOT NULL,
  `kullanildi` tinyint(1) DEFAULT 0,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `sifre_sifirlama_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




DROP TABLE IF EXISTS site_ayarlari;

CREATE TABLE `site_ayarlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_baslik` varchar(255) NOT NULL,
  `site_aciklama` text DEFAULT NULL,
  `site_anahtar_kelimeler` text DEFAULT NULL,
  `site_logo` varchar(255) DEFAULT NULL,
  `site_favicon` varchar(255) DEFAULT NULL,
  `iletisim_email` varchar(255) DEFAULT NULL,
  `iletisim_telefon` varchar(50) DEFAULT NULL,
  `iletisim_adres` text DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `footer_yazisi` varchar(255) DEFAULT NULL,
  `analytics_kodu` text DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` varchar(10) DEFAULT NULL,
  `smtp_email` varchar(255) DEFAULT NULL,
  `smtp_sifre` varchar(255) DEFAULT NULL,
  `bakim_modu` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_ayarlari VALUES("1","Blog Sitesi","","","","","","","","","","","","","","","","","0");



DROP TABLE IF EXISTS yazi_etiketler;

CREATE TABLE `yazi_etiketler` (
  `yazi_id` int(11) NOT NULL,
  `etiket_id` int(11) NOT NULL,
  PRIMARY KEY (`yazi_id`,`etiket_id`),
  KEY `etiket_id` (`etiket_id`),
  CONSTRAINT `yazi_etiketler_ibfk_1` FOREIGN KEY (`yazi_id`) REFERENCES `blog_yazilar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `yazi_etiketler_ibfk_2` FOREIGN KEY (`etiket_id`) REFERENCES `etiketler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO yazi_etiketler VALUES("3","1");
INSERT INTO yazi_etiketler VALUES("3","2");



DROP TABLE IF EXISTS yorumlar;

CREATE TABLE `yorumlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yazi_id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `yorum_metni` text NOT NULL,
  `durum` enum('onay_bekliyor','onaylanmis','spam') DEFAULT 'onay_bekliyor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tarih` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `yazi_id` (`yazi_id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `yorumlar_ibfk_1` FOREIGN KEY (`yazi_id`) REFERENCES `blog_yazilar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `yorumlar_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




