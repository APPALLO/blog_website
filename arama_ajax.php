<?php
include 'baglan.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode(['success' => false, 'message' => 'Arama terimi gerekli']);
    exit;
}

$aranan = $conn->real_escape_string($_GET['q']);
$kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

// Temel sorgu
$sql = "SELECT b.*, k.kategori_adi, u.ad_soyad as yazar_adi,
        (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
        FROM blog_yazilar b
        LEFT JOIN kategoriler k ON b.kategori_id = k.id
        LEFT JOIN kullanicilar u ON b.yazar_id = u.id
        WHERE b.durum = 'yayinda' AND 
        (b.baslik LIKE '%$aranan%' OR b.icerik LIKE '%$aranan%' OR k.kategori_adi LIKE '%$aranan%')";

// Kategori filtresi
if ($kategori > 0) {
    $sql .= " AND b.kategori_id = $kategori";
}

$sql .= " LIMIT 5";

$result = $conn->query($sql);
$sonuclar = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Özet metni oluştur
        $ozet = strip_tags($row['icerik']);
        $ozet = mb_substr($ozet, 0, 100) . '...';
        
        // Aranan kelimeyi vurgula
        $baslik = preg_replace('/(' . preg_quote($aranan, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($row['baslik']));
        $ozet = preg_replace('/(' . preg_quote($aranan, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($ozet));
        
        $sonuclar[] = [
            'id' => $row['id'],
            'baslik' => $baslik,
            'ozet' => $ozet,
            'resim' => $row['kapak_resmi'] ?? 'default-post.jpg',
            'kategori' => $row['kategori_adi'],
            'yazar' => $row['yazar_adi'],
            'tarih' => date('d.m.Y', strtotime($row['tarih'])),
            'goruntulenme' => $row['goruntulenme'],
            'yorum_sayisi' => $row['yorum_sayisi']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $sonuclar
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sonuç bulunamadı'
    ]);
} 