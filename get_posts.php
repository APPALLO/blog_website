<?php
header('Content-Type: application/json');
require_once 'baglan.php';

// XSS koruma fonksiyonu
function guvenli_cikti($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Arama parametresi
$arama = isset($_GET['arama']) ? trim($_GET['arama']) : '';
$where = "b.durum = 'yayinda'";
$params = [];

if ($arama) {
    $where .= " AND (b.baslik LIKE ? OR b.icerik LIKE ?)";
    $params[] = "%{$arama}%";
    $params[] = "%{$arama}%";
}

// Blog yazılarını getir
$sql = "SELECT b.id, b.baslik, b.icerik, b.kapak_resmi, b.tarih, b.goruntulenme, b.yazar_id,
        k.kategori_adi, u.ad_soyad,
        COUNT(y.id) as yorum_sayisi
        FROM blog_yazilar b
        LEFT JOIN kategoriler k ON b.kategori_id = k.id
        LEFT JOIN kullanicilar u ON b.yazar_id = u.id
        LEFT JOIN yorumlar y ON y.yazi_id = b.id AND y.durum = 'onaylanmis'
        WHERE $where
        GROUP BY b.id, b.baslik, b.icerik, b.kapak_resmi, b.tarih, b.goruntulenme, b.yazar_id, k.kategori_adi, u.ad_soyad
        ORDER BY b.tarih DESC 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);

$types = str_repeat('s', count($params) + 2);
$bind_params = array_merge($params, [$offset, $limit]);
$stmt->bind_param($types, ...$bind_params);

$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = [
        'id' => (int)$row['id'],
        'baslik' => guvenli_cikti($row['baslik']),
        'ozet' => guvenli_cikti(mb_substr(strip_tags($row['icerik']), 0, 150)) . '...',
        'kapak_resmi' => $row['kapak_resmi'],
        'tarih' => date('d.m.Y', strtotime($row['tarih'])),
        'kategori_adi' => guvenli_cikti($row['kategori_adi']),
        'ad_soyad' => guvenli_cikti($row['ad_soyad']),
        'goruntulenme' => number_format($row['goruntulenme']),
        'yorum_sayisi' => (int)$row['yorum_sayisi']
    ];
}

echo json_encode(['posts' => $posts]); 