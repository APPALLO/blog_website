<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Oturum bulunamadı']);
    exit();
}

include 'baglan.php';

// En son taslağı getir
$sql = "SELECT * FROM yazi_taslaklar 
        WHERE kullanici_id = ? 
        ORDER BY guncelleme_tarihi DESC 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$result = $stmt->get_result();
$taslak = $result->fetch_assoc();

if ($taslak) {
    // Etiketleri JSON'dan diziye dönüştür
    $taslak['etiketler'] = json_decode($taslak['etiketler'], true);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'taslak' => [
            'baslik' => $taslak['baslik'],
            'ozet' => $taslak['ozet'],
            'icerik' => $taslak['icerik'],
            'kategori_id' => $taslak['kategori_id'],
            'etiketler' => $taslak['etiketler']
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Taslak bulunamadı']);
} 