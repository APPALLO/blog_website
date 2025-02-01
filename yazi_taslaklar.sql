CREATE TABLE IF NOT EXISTS yazi_taslaklar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    baslik VARCHAR(255),
    ozet TEXT,
    icerik LONGTEXT,
    kategori_id INT,
    etiketler TEXT,
    olusturma_tarihi DATETIME,
    guncelleme_tarihi DATETIME,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES kategoriler(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 