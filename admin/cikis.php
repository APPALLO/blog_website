<?php
session_start();
require_once('../baglan.php');
require_once('includes/functions.php');

if (isset($_SESSION['admin_id'])) {
    // Aktivite kaydı
    aktivite_kaydet(
        $_SESSION['admin_id'],
        AKTIVITE_CIKIS,
        $_SESSION['admin']['kullanici_adi'] . " kullanıcısı çıkış yaptı",
        'kullanicilar'
    );
}

// Oturumu sonlandır
session_destroy();

// Ana sayfaya yönlendir
header("Location: ../index.php");
exit();
?>