<?php
require_once('../baglan.php');

// Karakter seti ayarı
mysqli_set_charset($conn, "utf8mb4");

// sistem_loglari tablosunu oluştur
$sql = "CREATE TABLE IF NOT EXISTS `sistem_loglari` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `islem_tipi` varchar(50) NOT NULL,
    `islem_detay` text NOT NULL,
    `kullanici_id` int(11) NOT NULL,
    `tarih` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `kullanici_id` (`kullanici_id`),
    CONSTRAINT `sistem_loglari_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    if ($conn->query($sql)) {
        echo "sistem_loglari tablosu başarıyla oluşturuldu.";
    } else {
        echo "Tablo oluşturulurken bir hata oluştu: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Bir hata oluştu: " . $e->getMessage();
}
?> 