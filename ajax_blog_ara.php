<?php
require_once 'baglan.php';
header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$aranan = trim($_GET['q']);
$aranan = filter_var($aranan, FILTER_SANITIZE_STRING);

if (strlen($aranan) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT 
                b.id,
                b.baslik,
                b.url,
                b.kapak_foto,
                b.tarih,
                k.ad as kategori_adi
            FROM 
                blog_yazilari b
                LEFT JOIN kategoriler k ON b.kategori_id = k.id
            WHERE 
                b.baslik LIKE :aranan 
                OR b.icerik LIKE :aranan 
                OR k.ad LIKE :aranan
            ORDER BY 
                b.tarih DESC 
            LIMIT 5";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['aranan' => '%' . $aranan . '%']);
    $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($sonuclar);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı hatası']);
} 