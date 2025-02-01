<?php
header('Content-Type: application/json');
require_once 'baglan.php';

// XSS koruma fonksiyonu
function guvenli_cikti($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Arama sorgusu
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if (strlen($q) >= 2) {
    $sql = "SELECT b.id, b.baslik, b.kapak_resmi, k.kategori_adi
            FROM blog_yazilar b
            LEFT JOIN kategoriler k ON b.kategori_id = k.id
            WHERE b.durum = 'yayinda' 
            AND (b.baslik LIKE ? OR b.icerik LIKE ?)
            ORDER BY b.goruntulenme DESC
            LIMIT 5";
            
    $stmt = $conn->prepare($sql);
    $param = "%{$q}%";
    $param2 = $param;
    $stmt->bind_param('ss', $param, $param2);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'id' => (int)$row['id'],
            'baslik' => guvenli_cikti($row['baslik']),
            'kapak_resmi' => $row['kapak_resmi'],
            'kategori_adi' => guvenli_cikti($row['kategori_adi'])
        ];
    }
}

echo json_encode($results); 