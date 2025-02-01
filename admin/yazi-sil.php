<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

header('Content-Type: application/json');

// CSRF Token kontrolü
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF token hatası: " . json_encode($_POST));
    echo json_encode(['status' => 'error', 'message' => 'Güvenlik doğrulaması başarısız.']);
    exit();
}

// Kullanıcı doğrulama
if (!isset($_SESSION['admin']['id'])) {
    error_log("Yetkisiz erişim: Kullanıcı giriş yapmamış.");
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit();
}

// POST verilerini kontrol et
if (!isset($_POST['yazi_id'])) {
    error_log("Yazı ID eksik");
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit();
}

$yazi_id = filter_input(INPUT_POST, 'yazi_id', FILTER_VALIDATE_INT);
if (!$yazi_id) {
    error_log("Geçersiz yazı ID: " . $_POST['yazi_id']);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz yazı ID.']);
    exit();
}

try {
    // Yazının var olduğunu ve ID'sini kontrol et
    $kontrol_sql = "SELECT y.id, y.yazar_id, u.kullanici_adi 
                   FROM blog_yazilar y 
                   LEFT JOIN kullanicilar u ON y.yazar_id = u.id 
                   WHERE y.id = ?";
    $kontrol_stmt = $conn->prepare($kontrol_sql);
    
    if (!$kontrol_stmt) {
        throw new Exception("Veritabanı sorgusu hazırlanamadı");
    }
    
    $kontrol_stmt->bind_param("i", $yazi_id);
    if (!$kontrol_stmt->execute()) {
        throw new Exception("Kontrol sorgusu çalıştırılamadı");
    }
    
    $yazi = $kontrol_stmt->get_result()->fetch_assoc();
    error_log("Kontrol edilen yazı: " . print_r($yazi, true));
    
    if (!$yazi) {
        echo json_encode(['status' => 'error', 'message' => 'Yazı bulunamadı.']);
        exit();
    }

    // Admin veya yazının sahibi mi kontrol et
    if ($_SESSION['admin']['rol'] !== 'admin' && $yazi['yazar_id'] != $_SESSION['admin']['id']) {
        error_log("Yetkisiz silme denemesi - kullanıcı_id: {$_SESSION['admin']['id']}, yazi_id: {$yazi['id']}");
        echo json_encode(['status' => 'error', 'message' => 'Bu yazıyı silme yetkiniz yok.']);
        exit();
    }
    
    $conn->begin_transaction();
    error_log("Transaction başlatıldı - Yazı ID: " . $yazi['id']);
    
    // Yazıyı tamamen sil
    $sql = "DELETE FROM blog_yazilar WHERE id = ?";
    error_log("Silme SQL: " . $sql . " [ID: " . $yazi['id'] . "]");
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Silme sorgusu hazırlanamadı");
    }
    
    $stmt->bind_param("i", $yazi['id']);
    if (!$stmt->execute()) {
        throw new Exception("Silme sorgusu çalıştırılamadı: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Yazı silinemedi - Etkilenen satır sayısı: 0");
    }
    
    // Silme işlemini kontrol et
    $kontrol_sql = "SELECT id FROM blog_yazilar WHERE id = ?";
    $kontrol_stmt = $conn->prepare($kontrol_sql);
    $kontrol_stmt->bind_param("i", $yazi['id']);
    $kontrol_stmt->execute();
    $silinen_yazi = $kontrol_stmt->get_result()->fetch_assoc();
    
    if ($silinen_yazi) {
        throw new Exception("Yazı veritabanından silinemedi");
    }
    
    error_log("Yazı başarıyla silindi - ID: " . $yazi['id'] . ", Etkilenen satır: " . $stmt->affected_rows);
    
    $conn->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => 'Yazı başarıyla silindi.',
        'yazi_id' => $yazi['id']
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Yazı silme hatası: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Silme işlemi başarısız: ' . $e->getMessage()]);
} 