<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Yazı ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: yazilar.php");
    exit();
}

$yazi_id = (int)$_GET['id'];

// Yazı bilgilerini al
$sql = "SELECT kapak_resmi FROM blog_yazilar WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $yazi_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $yazi = $result->fetch_assoc();
    
    // Kapak resmini sil
    if (!empty($yazi['kapak_resmi']) && file_exists('../' . $yazi['kapak_resmi'])) {
        unlink('../' . $yazi['kapak_resmi']);
    }
    
    // İçerikteki resimleri sil
    $sql = "SELECT icerik FROM blog_yazilar WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $yazi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $yazi = $result->fetch_assoc();
    
    // İçerikteki resimleri bul ve sil
    preg_match_all('/<img[^>]+src="([^">]+)"/', $yazi['icerik'], $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $resim) {
            if (strpos($resim, 'uploads/editor/') !== false) {
                $resim_yolu = '../' . $resim;
                if (file_exists($resim_yolu)) {
                    unlink($resim_yolu);
                }
            }
        }
    }
    
    // Yazıyı sil
    $sql = "DELETE FROM blog_yazilar WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $yazi_id);
    
    if ($stmt->execute()) {
        // Yazıya ait yorumları sil
        $sql = "DELETE FROM yorumlar WHERE yazi_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $yazi_id);
        $stmt->execute();
        
        header("Location: yazilar.php?mesaj=silindi");
    } else {
        header("Location: yazilar.php?mesaj=hata");
    }
} else {
    header("Location: yazilar.php?mesaj=bulunamadi");
}

exit(); 