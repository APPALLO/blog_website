<?php
session_start();
require_once('../baglan.php');
require_once('includes/functions.php');  // Fonksiyonlar dosyasını dahil et

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];
    
    $sql = "SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND rol = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kullanici_adi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($sifre, $admin['sifre'])) {
            $_SESSION['admin'] = array(
                'id' => $admin['id'],
                'kullanici_adi' => $admin['kullanici_adi'],
                'email' => $admin['email'],
                'ad_soyad' => $admin['ad_soyad'],
                'rol' => $admin['rol']
            );
            $_SESSION['admin_id'] = $admin['id'];
            
            // Aktivite kaydı
            aktivite_kaydet(
                $admin['id'],
                AKTIVITE_GIRIS,
                $admin['kullanici_adi'] . " kullanıcısı giriş yaptı",
                'kullanicilar'
            );
            
            header("Location: panel.php");
            exit();
        } else {
            $hata = "Kullanıcı adı veya şifre hatalı!";
        }
    } else {
        $hata = "Kullanıcı adı veya şifre hatalı!";
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
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(45deg, #4361ee, #3f37c9);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin: 1rem 0;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            background: #4361ee;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .login-logo i {
            font-size: 2rem;
            color: white;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #3651d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle .toggle-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Admin Girişi</h1>
        </div>
        
        <?php if (isset($hata)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $hata; ?>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" class="form-control" name="kullanici_adi" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <div class="password-toggle">
                    <input type="password" class="form-control" name="sifre" required>
                    <i class="fas fa-eye toggle-icon" onclick="togglePassword(this)"></i>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
            </button>
        </form>
    </div>

    <script>
        function togglePassword(icon) {
            const input = icon.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 