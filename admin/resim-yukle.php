<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

// Yanıt formatı
header('Content-Type: application/json');

// Resim kontrolü
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
    echo json_encode(['success' => false, 'error' => 'Resim yüklenemedi.']);
    exit();
}

// İzin verilen dosya tipleri
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($_FILES['image']['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz dosya formatı.']);
    exit();
}

// Dosya boyutu kontrolü (5MB)
if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Dosya boyutu çok büyük. Maksimum 5MB olmalıdır.']);
    exit();
}

// Hedef klasör
$upload_dir = '../uploads/editor/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Benzersiz dosya adı oluştur
$file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$file_name = uniqid() . '.' . $file_extension;
$target_file = $upload_dir . $file_name;

// Resmi yükle
if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
    echo json_encode([
        'success' => true,
        'url' => 'uploads/editor/' . $file_name
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Resim kaydedilirken bir hata oluştu.'
    ]);
} 