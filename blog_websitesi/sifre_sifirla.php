<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'baglan.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['hata'] = "Geçersiz e-posta adresi!";
        header("Location: sifremi_unuttum.php");
        exit;
    }

    // E-posta adresinin veritabanında olup olmadığını kontrol et
    $sql = "SELECT id, ad_soyad FROM kullanicilar WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['hata'] = "Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı!";
        header("Location: sifremi_unuttum.php");
        exit;
    }

    $user = $result->fetch_assoc();
    
    // Benzersiz token oluştur
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Token'ı veritabanına kaydet
    $sql = "INSERT INTO sifre_sifirlama (kullanici_id, token, son_gecerlilik) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user['id'], $token, $expires);
    
    if ($stmt->execute()) {
        // PHPMailer ile e-posta gönderimi
        require 'PHPMailer/PHPMailer.php';
        require 'PHPMailer/SMTP.php';
        require 'PHPMailer/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->SMTPDebug = 2; // Detaylı hata ayıklama

        try {
            // SMTP ayarları
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465; // SSL için 465 portu
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL kullan
            
            // Gmail hesap bilgilerinizi güncelleyin
            $mail->Username = 'GMAIL_ADRESINIZ@gmail.com'; // Gmail adresinizi yazın
            $mail->Password = 'UYGULAMA_SIFRENIZ'; // 16 karakterlik uygulama şifresini yazın
            
            // SSL/TLS ayarları
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->CharSet = 'UTF-8';

            // Gönderici ve alıcı ayarları
            $mail->setFrom($mail->Username, 'Blog Sitesi');
            $mail->addAddress($email, $user['ad_soyad']);

            // E-posta içeriği
            $mail->isHTML(true);
            $mail->Subject = "Şifre Sıfırlama Talebi";
            
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/blog_websitesi/sifre_yenile.php?token=" . $token;
            
            $mailContent = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .button { 
                        display: inline-block; 
                        padding: 10px 20px; 
                        background-color: #3B82F6; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 5px; 
                        margin: 20px 0; 
                    }
                    .footer { font-size: 12px; color: #666; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Merhaba ' . htmlspecialchars($user['ad_soyad']) . ',</h2>
                    <p>Blog sitesi hesabınız için şifre sıfırlama talebinde bulundunuz.</p>
                    <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
                    <p><a href="' . $reset_link . '" class="button" style="color: white;">Şifremi Sıfırla</a></p>
                    <p>Veya aşağıdaki bağlantıyı tarayıcınıza kopyalayın:</p>
                    <p>' . $reset_link . '</p>
                    <p>Bu bağlantı 1 saat süreyle geçerlidir.</p>
                    <p>Eğer bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.</p>
                    <div class="footer">
                        <p>Saygılarımızla,<br>Blog Sitesi</p>
                    </div>
                </div>
            </body>
            </html>';
            
            $mail->Body = $mailContent;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $mailContent));

            if ($mail->send()) {
                $_SESSION['basari'] = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi. Lütfen e-posta kutunuzu kontrol edin.";
            } else {
                $_SESSION['hata'] = "E-posta gönderilirken bir hata oluştu: " . $mail->ErrorInfo;
            }
        } catch (Exception $e) {
            $_SESSION['hata'] = "E-posta gönderilirken bir hata oluştu: " . $e->getMessage();
        }
    } else {
        $_SESSION['hata'] = "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
    }
} else {
    $_SESSION['hata'] = "Geçersiz istek!";
}

header("Location: sifremi_unuttum.php");
exit;
?>