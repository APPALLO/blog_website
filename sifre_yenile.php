<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'baglan.php';

// Token kontrolü
if (!isset($_GET['token'])) {
    $_SESSION['hata'] = "Geçersiz şifre sıfırlama bağlantısı!";
    header("Location: giris.php");
    exit;
}

$token = $_GET['token'];

// Token'ın geçerliliğini kontrol et
$sql = "SELECT s.*, k.ad_soyad 
        FROM sifre_sifirlama s 
        JOIN kullanicilar k ON s.kullanici_id = k.id 
        WHERE s.token = ? AND s.kullanildi = 0 AND s.son_gecerlilik > NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['hata'] = "Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı!";
    header("Location: giris.php");
    exit;
}

$reset_info = $result->fetch_assoc();

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $yeni_sifre = $_POST['yeni_sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];
    
    if (strlen($yeni_sifre) < 6) {
        $_SESSION['hata'] = "Şifre en az 6 karakter uzunluğunda olmalıdır!";
    } elseif ($yeni_sifre !== $sifre_tekrar) {
        $_SESSION['hata'] = "Şifreler eşleşmiyor!";
    } else {
        // Şifreyi güncelle
        $hashed_password = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        $sql = "UPDATE kullanicilar SET sifre = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $reset_info['kullanici_id']);
        
        if ($stmt->execute()) {
            // Token'ı kullanıldı olarak işaretle
            $sql = "UPDATE sifre_sifirlama SET kullanildi = 1 WHERE token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $_SESSION['basari'] = "Şifreniz başarıyla güncellendi! Şimdi yeni şifrenizle giriş yapabilirsiniz.";
            header("Location: giris.php");
            exit;
        } else {
            $_SESSION['hata'] = "Şifre güncellenirken bir hata oluştu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Yenileme - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3B82F6;
            --secondary-color: #9B2C2C;
            --accent-color: #4F46E5;
            --text-color: #1F2937;
            --light-bg: #F3F4F6;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .reset-password-container {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 2rem rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 2rem;
            text-align: center;
            border: none;
        }

        .card-header h2 {
            color: white;
            font-weight: 600;
            margin: 0;
        }

        .card-header p {
            color: rgba(255,255,255,0.8);
            margin: 0.5rem 0 0;
            font-size: 0.95rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid var(--light-bg);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
        }

        .input-group-text {
            background: var(--light-bg);
            border: 2px solid var(--light-bg);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }

        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: #DC2626;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }

        .password-strength {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .password-strength.weak { color: #DC2626; }
        .password-strength.medium { color: #F59E0B; }
        .password-strength.strong { color: #10B981; }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="reset-password-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card animate-fade-in">
                        <div class="card-header">
                            <h2 class="h3">
                                <i class="fas fa-lock me-2"></i>Şifre Yenileme
                            </h2>
                            <p>Merhaba, <?php echo htmlspecialchars($reset_info['ad_soyad']); ?>! Lütfen yeni şifrenizi belirleyin.</p>
                        </div>
                        <div class="card-body">
                            <?php
                            if(isset($_SESSION['hata'])) {
                                echo '<div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['hata'] . '
                                </div>';
                                unset($_SESSION['hata']);
                            }
                            ?>

                            <form action="?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                                <div class="mb-4">
                                    <label for="yeni_sifre" class="form-label">Yeni Şifre</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="yeni_sifre" name="yeni_sifre" 
                                               required minlength="6" onkeyup="checkPasswordStrength(this.value)">
                                    </div>
                                    <div id="passwordStrength" class="password-strength"></div>
                                </div>
                                <div class="mb-4">
                                    <label for="sifre_tekrar" class="form-label">Şifre Tekrar</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="sifre_tekrar" name="sifre_tekrar" 
                                               required minlength="6">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-check me-2"></i>Şifreyi Güncelle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('passwordStrength');
        const strength = {
            0: "Çok Zayıf",
            1: "Zayıf",
            2: "Orta",
            3: "Güçlü",
            4: "Çok Güçlü"
        };
        
        let score = 0;
        
        // En az 6 karakter
        if (password.length >= 6) score++;
        // Büyük ve küçük harf
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        // Sayılar
        if (/\d/.test(password)) score++;
        // Özel karakterler
        if (/[^a-zA-Z\d]/.test(password)) score++;
        
        strengthDiv.className = 'password-strength ' + 
            (score < 2 ? 'weak' : score < 3 ? 'medium' : 'strong');
        strengthDiv.innerHTML = '<i class="fas ' + 
            (score < 2 ? 'fa-exclamation-circle' : score < 3 ? 'fa-info-circle' : 'fa-check-circle') + 
            ' me-1"></i>Şifre Gücü: ' + strength[score];
    }
    </script>
</body>
</html> 