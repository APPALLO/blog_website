<?php
include 'baglan.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = mysqli_real_escape_string($conn, $_POST['ad']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $konu = mysqli_real_escape_string($conn, $_POST['konu']);
    $mesaj = mysqli_real_escape_string($conn, $_POST['mesaj']);
    $tarih = date('Y-m-d H:i:s');

    $sql = "INSERT INTO iletisim_mesajlari (ad, email, konu, mesaj, tarih) 
            VALUES ('$ad', '$email', '$konu', '$mesaj', '$tarih')";

    if ($conn->query($sql) === TRUE) {
        // E-posta gönderimi
        $to = "info@blogsite.com";
        $subject = "Yeni İletişim Formu Mesajı: " . $konu;
        $message = "Ad Soyad: " . $ad . "\n";
        $message .= "E-posta: " . $email . "\n";
        $message .= "Konu: " . $konu . "\n\n";
        $message .= "Mesaj:\n" . $mesaj;
        $headers = "From: " . $email;

        mail($to, $subject, $message, $headers);

        echo json_encode(array("success" => true, "message" => "Mesajınız başarıyla gönderildi."));
    } else {
        echo json_encode(array("success" => false, "message" => "Bir hata oluştu: " . $conn->error));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Geçersiz istek metodu."));
}

$conn->close();
?> 