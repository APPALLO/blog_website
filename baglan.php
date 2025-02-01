<?php
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'blog_db');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

try {
    $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Geriye dönük uyumluluk için $conn değişkenini de oluştur
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception("mysqli bağlantısı başarısız oldu: " . $conn->connect_error);
    }

    error_log("Veritabanı bağlantısı başarılı.");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Veritabanına bağlanılamadı. Lütfen daha sonra tekrar deneyin.");
}
?> 