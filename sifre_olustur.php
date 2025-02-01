<?php
$sifre = "admin123";
$hash = password_hash($sifre, PASSWORD_DEFAULT);
echo "Şifre: " . $sifre . "\n";
echo "Hash: " . $hash . "\n";

// Doğrulama testi
if (password_verify($sifre, $hash)) {
    echo "Şifre doğrulama başarılı!\n";
} else {
    echo "Şifre doğrulama başarısız!\n";
}
?> 