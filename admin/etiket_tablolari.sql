-- Etiketler tablosu
CREATE TABLE IF NOT EXISTS etiketler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etiket_adi VARCHAR(50) NOT NULL UNIQUE,
    seo_url VARCHAR(50) NOT NULL UNIQUE,
    kullanim_sayisi INT DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yazı-Etiket ilişki tablosu
CREATE TABLE IF NOT EXISTS yazi_etiketler (
    yazi_id INT NOT NULL,
    etiket_id INT NOT NULL,
    PRIMARY KEY (yazi_id, etiket_id),
    FOREIGN KEY (yazi_id) REFERENCES blog_yazilar(id) ON DELETE CASCADE,
    FOREIGN KEY (etiket_id) REFERENCES etiketler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 