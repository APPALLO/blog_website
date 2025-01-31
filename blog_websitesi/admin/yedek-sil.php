<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Dosya adı kontrolü
if (!isset($_GET['dosya'])) {
    header("Location: yedekle.php");
    exit();
}

$dosya = $_GET['dosya'];
$dosya_yolu = 'yedekler/' . $dosya;

// Güvenlik kontrolü
if (strpos($dosya, '..') !== false || !file_exists($dosya_yolu)) {
    header("Location: yedekle.php?hata=gecersiz_dosya");
    exit();
}

// Dosyayı sil
if (unlink($dosya_yolu)) {
    header("Location: yedekle.php?mesaj=silindi");
} else {
    header("Location: yedekle.php?hata=silinemedi");
}
exit(); 