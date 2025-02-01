<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Kullanıcı bilgilerini getir
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM kullanicilar WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$kullanici = $result->fetch_assoc();

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_soyad = $_POST['ad_soyad'];
    $email = $_POST['email'];
    $kullanici_adi = $_POST['kullanici_adi'];
    $mevcut_sifre = $_POST['mevcut_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];
    
    // Hata kontrolü
    $hatalar = [];
    
    // E-posta ve kullanıcı adı benzersizlik kontrolü
    $sql = "SELECT id FROM kullanicilar WHERE (email = ? OR kullanici_adi = ?) AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $email, $kullanici_adi, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['email'] === $email) {
                $hatalar[] = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
            }
            if ($row['kullanici_adi'] === $kullanici_adi) {
                $hatalar[] = "Bu kullanıcı adı başka bir kullanıcı tarafından kullanılıyor.";
            }
        }
    }
    
    // Şifre değişikliği yapılacaksa kontroller
    if (!empty($yeni_sifre)) {
        // Mevcut şifre kontrolü
        if (!password_verify($mevcut_sifre, $kullanici['sifre'])) {
            $hatalar[] = "Mevcut şifreniz hatalı.";
        }
        
        // Yeni şifre kontrolü
        if (strlen($yeni_sifre) < 6) {
            $hatalar[] = "Yeni şifre en az 6 karakter olmalıdır.";
        }
        
        // Şifre tekrar kontrolü
        if ($yeni_sifre !== $yeni_sifre_tekrar) {
            $hatalar[] = "Yeni şifreler eşleşmiyor.";
        }
    }
    
    // Hata yoksa güncelle
    if (empty($hatalar)) {
        if (!empty($yeni_sifre)) {
            // Şifre ile güncelle
            $yeni_sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $sql = "UPDATE kullanicilar SET ad_soyad = ?, email = ?, kullanici_adi = ?, sifre = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $ad_soyad, $email, $kullanici_adi, $yeni_sifre_hash, $admin_id);
        } else {
            // Şifre olmadan güncelle
            $sql = "UPDATE kullanicilar SET ad_soyad = ?, email = ?, kullanici_adi = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $ad_soyad, $email, $kullanici_adi, $admin_id);
        }
        
        if ($stmt->execute()) {
            // Session bilgilerini güncelle
            $_SESSION['admin']['ad_soyad'] = $ad_soyad;
            $_SESSION['admin']['email'] = $email;
            $_SESSION['admin']['kullanici_adi'] = $kullanici_adi;
            
            $success_message = "Profil bilgileriniz başarıyla güncellendi.";
            
            // Kullanıcı bilgilerini yeniden yükle
            $sql = "SELECT * FROM kullanicilar WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $kullanici = $result->fetch_assoc();
        } else {
            $error_message = "Profil bilgileri güncellenirken bir hata oluştu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Ana İçerik -->
            <div class="col p-0">
                <div class="main-content">
                    <!-- Üst Bar -->
                    <div class="top-bar">
                        <h2 class="h4 mb-0">Profil</h2>
                        
                        <div class="d-flex align-items-center">
                            <div class="user-menu dropdown">
                                <div class="d-flex align-items-center" role="button" data-bs-toggle="dropdown">
                                    <div class="user-info text-end me-3">
                                        <div class="fw-medium"><?php echo htmlspecialchars($_SESSION['admin']['ad_soyad']); ?></div>
                                        <small class="text-muted"><?php echo ucfirst($_SESSION['admin']['rol']); ?></small>
                                    </div>
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($_SESSION['admin']['ad_soyad'], 0, 1)); ?>
                                    </div>
                                </div>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                                    <li><a class="dropdown-item" href="ayarlar.php"><i class="fas fa-cog me-2"></i>Ayarlar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="cikis.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ana İçerik -->
                    <div class="p-4">
                        <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($hatalar)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <ul class="mb-0">
                                <?php foreach ($hatalar as $hata): ?>
                                <li><?php echo $hata; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <div class="content-card">
                            <div class="card-body">
                                <form method="post" class="profile-form">
                                    <div class="row">
                                        <!-- Profil Bilgileri -->
                                        <div class="col-md-6 mb-4">
                                            <div class="content-header mb-3">
                                                <i class="fas fa-user"></i>
                                                Profil Bilgileri
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Ad Soyad</label>
                                                <input type="text" name="ad_soyad" class="form-control" value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">E-posta Adresi</label>
                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($kullanici['email']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Kullanıcı Adı</label>
                                                <input type="text" name="kullanici_adi" class="form-control" value="<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <!-- Şifre Değiştirme -->
                                        <div class="col-md-6 mb-4">
                                            <div class="content-header mb-3">
                                                <i class="fas fa-lock"></i>
                                                Şifre Değiştirme
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Mevcut Şifre</label>
                                                <div class="input-group">
                                                    <input type="password" name="mevcut_sifre" class="form-control" id="mevcut_sifre">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mevcut_sifre')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Yeni Şifre</label>
                                                <div class="input-group">
                                                    <input type="password" name="yeni_sifre" class="form-control" id="yeni_sifre">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('yeni_sifre')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">En az 6 karakter olmalıdır</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Yeni Şifre Tekrar</label>
                                                <div class="input-group">
                                                    <input type="password" name="yeni_sifre_tekrar" class="form-control" id="yeni_sifre_tekrar">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('yeni_sifre_tekrar')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Responsive kontrol
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });

        // Şifre göster/gizle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            
            const icon = event.currentTarget.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html> 