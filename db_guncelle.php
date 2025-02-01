<?php
require_once 'baglan.php';

// Yeni sütun ekle
$sql = "ALTER TABLE kullanicilar 
        ADD COLUMN IF NOT EXISTS son_sayfa VARCHAR(255) DEFAULT NULL AFTER son_aktivite";

if ($conn->query($sql)) {
    echo "Veritabanı başarıyla güncellendi!";
} else {
    echo "Hata: " . $conn->error;
}
?> 