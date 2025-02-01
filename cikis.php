<?php
    session_start();

    // Oturumu temizle
    session_unset();
    session_destroy();

    // Çerezleri temizle
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }

    // Ana sayfaya yönlendir
    header("Location: index.php");
    exit();
?>