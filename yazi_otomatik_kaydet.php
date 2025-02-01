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

// JSON verisini al
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Geçersiz veri']);
    exit();
}

// Taslak ID'sini kontrol et veya yeni oluştur
$sql = "SELECT id FROM yazi_taslaklar WHERE kullanici_id = ? ORDER BY guncelleme_tarihi DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$result = $stmt->get_result();
$taslak = $result->fetch_assoc();

if ($taslak) {
    // Mevcut taslağı güncelle
    $sql = "UPDATE yazi_taslaklar SET 
            baslik = ?, 
            ozet = ?, 
            icerik = ?, 
            kategori_id = ?, 
            etiketler = ?, 
            guncelleme_tarihi = NOW() 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $etiketler_json = json_encode($data['etiketler']);
    $stmt->bind_param("sssssi", 
        $data['baslik'], 
        $data['ozet'], 
        $data['icerik'], 
        $data['kategori_id'], 
        $etiketler_json,
        $taslak['id']
    );
} else {
    // Yeni taslak oluştur
    $sql = "INSERT INTO yazi_taslaklar (kullanici_id, baslik, ozet, icerik, kategori_id, etiketler, olusturma_tarihi, guncelleme_tarihi) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $etiketler_json = json_encode($data['etiketler']);
    $stmt->bind_param("isssss", 
        $_SESSION['kullanici_id'], 
        $data['baslik'], 
        $data['ozet'], 
        $data['icerik'], 
        $data['kategori_id'], 
        $etiketler_json
    );
}

$success = $stmt->execute();

header('Content-Type: application/json');
echo json_encode(['success' => $success]); 