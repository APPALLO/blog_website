<?php
// Veritabanı bağlantı sabitleri
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'blog_db');

try {
    // Veritabanı bağlantısı
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Bağlantı kontrolü
    if ($conn->connect_error) {
        throw new Exception("Veritabanı bağlantı hatası: " . $conn->connect_error);
    }
    
    // Türkçe karakter desteği
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die($e->getMessage());
}
?> 