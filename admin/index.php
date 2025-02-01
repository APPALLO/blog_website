<?php
session_start();
require_once('../baglan.php');

// Hata ayıklama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Giriş denemeleri tablosunu oluştur
$sql = "CREATE TABLE IF NOT EXISTS giris_denemeleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_adresi VARCHAR(45) NOT NULL,
    kullanici_adi VARCHAR(50),
    deneme_sayisi INT DEFAULT 1,
    son_deneme TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    engelleme_suresi TIMESTAMP NULL,
    INDEX (ip_adresi),
    INDEX (kullanici_adi)
)";
$conn->query($sql);

// Eğer zaten giriş yapılmışsa yönetim paneline yönlendir
if (isset($_SESSION['admin_id'])) {
    header("Location: panel.php");
    exit();
}

// Eski giriş denemelerini temizle (24 saatten eski)
$sql = "DELETE FROM giris_denemeleri WHERE son_deneme < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$stmt = $conn->prepare($sql);
$stmt->execute();

// IP adresi ve kullanıcı adına göre giriş denemelerini kontrol et
$ip_adresi = $_SERVER['REMOTE_ADDR'];
$engelleme_suresi = 15; // Dakika cinsinden

$hata = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $sifre = $_POST['sifre'] ?? '';

    if (empty($kullanici_adi) || empty($sifre)) {
        $hata = 'Lütfen tüm alanları doldurun.';
    } else {
        $sql = "SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND rol = 'admin' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $kullanici_adi);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($sifre, $admin['sifre'])) {
            // Session bilgilerini kaydet
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin'] = array(
                'id' => $admin['id'],
                'kullanici_adi' => $admin['kullanici_adi'],
                'ad_soyad' => $admin['ad_soyad'],
                'email' => $admin['email'],
                'rol' => $admin['rol'],
                'son_giris' => $admin['son_giris']
            );

            // Son giriş zamanını güncelle
            $sql = "UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $admin['id']);
            $stmt->execute();

            header("Location: panel.php");
            exit();
        } else {
            $hata = 'Geçersiz kullanıcı adı veya şifre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            background: var(--gri-pastel);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: var(--beyaz);
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header i {
            font-size: 3rem;
            color: var(--mavi-koyu);
            margin-bottom: 1rem;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            color: var(--gri-koyu);
            font-weight: 600;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating .form-control {
            border: 2px solid var(--gri-pastel);
            border-radius: 0.5rem;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--mavi-orta);
            box-shadow: 0 0 0 0.25rem rgba(43, 108, 176, 0.1);
        }
        
        .btn-login {
            background: var(--mavi-koyu);
            border: none;
            border-radius: 0.5rem;
            padding: 1rem;
            font-weight: 500;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            background: var(--mavi-orta);
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-user-shield"></i>
            <h1>Admin Girişi</h1>
        </div>
        
        <?php if ($hata): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
            echo htmlspecialchars($hata);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
        <?php endif; ?>
        
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" required>
                </div>
                <div class="invalid-feedback">Kullanıcı adı gereklidir.</div>
            </div>
            
            <div class="mb-4">
                <label for="sifre" class="form-label">Şifre</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="sifre" name="sifre" required>
                </div>
                <div class="invalid-feedback">Şifre gereklidir.</div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form doğrulama
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html> 