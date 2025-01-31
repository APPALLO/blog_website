<?php
session_start();
include 'baglan.php';

header('Content-Type: application/json');

if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Yorum yapabilmek için giriş yapmalısınız.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $yazi_id = isset($_POST['yazi_id']) ? (int)$_POST['yazi_id'] : 0;
    $yorum = isset($_POST['yorum']) ? trim($_POST['yorum']) : '';
    $kullanici_id = $_SESSION['kullanici_id'];
    
    // Temel doğrulamalar
    if (empty($yorum)) {
        echo json_encode(['success' => false, 'message' => 'Yorum metni boş olamaz.']);
        exit;
    }
    
    if (strlen($yorum) < 10) {
        echo json_encode(['success' => false, 'message' => 'Yorum en az 10 karakter olmalıdır.']);
        exit;
    }
    
    if (strlen($yorum) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Yorum en fazla 1000 karakter olabilir.']);
        exit;
    }
    
    // Son 5 dakika içinde yapılan yorum kontrolü (spam önleme)
    $sql = "SELECT COUNT(*) as yorum_sayisi FROM yorumlar 
            WHERE kullanici_id = ? AND tarih > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['yorum_sayisi'] >= 3) {
        echo json_encode(['success' => false, 'message' => 'Çok fazla yorum yaptınız. Lütfen birkaç dakika bekleyin.']);
        exit;
    }
    
    // Yazının var olup olmadığını kontrol et
    $sql = "SELECT id FROM blog_yazilar WHERE id = ? AND durum = 'yayinda'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $yazi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz yazı ID\'si.']);
        exit;
    }
    
    // Yorumu veritabanına ekle
    $sql = "INSERT INTO yorumlar (yazi_id, kullanici_id, yorum_metni, durum, tarih) 
            VALUES (?, ?, ?, 'onaylanmis', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $yazi_id, $kullanici_id, $yorum);
    
    if ($stmt->execute()) {
        // Yorum başarıyla eklendiyse, yeni yorumu döndür
        $yeni_yorum_id = $stmt->insert_id;
        
        $sql = "SELECT y.*, u.kullanici_adi, u.ad_soyad 
                FROM yorumlar y 
                LEFT JOIN kullanicilar u ON y.kullanici_id = u.id 
                WHERE y.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $yeni_yorum_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $yeni_yorum = $result->fetch_assoc();
        
        $yorum_html = '<div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="card-subtitle mb-2 text-muted">' . htmlspecialchars($yeni_yorum['ad_soyad'] ?: $yeni_yorum['kullanici_adi']) . '</h6>
                    <small class="text-muted">' . date('d.m.Y H:i', strtotime($yeni_yorum['tarih'])) . '</small>
                </div>
                <p class="card-text">' . nl2br(htmlspecialchars($yeni_yorum['yorum_metni'])) . '</p>
            </div>
        </div>';
        
        echo json_encode([
            'success' => true, 
            'message' => 'Yorumunuz başarıyla eklendi.',
            'yorum_html' => $yorum_html
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Yorum eklenirken bir hata oluştu.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
}

$conn->close();
?> 