<?php
session_start();
require_once('../baglan.php');

// Karakter seti ayarı
mysqli_set_charset($conn, "utf8mb4");

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Düzenlenecek kullanıcı ID kontrolü
if (!isset($_GET['id'])) {
    header("Location: kullanicilar.php");
    exit();
}

$kullanici_id = intval($_GET['id']);

// Kullanıcı bilgilerini getir
$sql = "SELECT * FROM kullanicilar WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kullanici_id);
$stmt->execute();
$result = $stmt->get_result();
$kullanici = $result->fetch_assoc();

// Kullanıcı bulunamadıysa listeye yönlendir
if (!$kullanici) {
    header("Location: kullanicilar.php");
    exit();
}

// Session'dan başarı mesajını al ve temizle
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_soyad = trim($_POST['ad_soyad']);
    $email = trim($_POST['email']);
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $rol = trim($_POST['rol']);
    $durum = trim($_POST['durum']);
    $onay_durumu = trim($_POST['onay_durumu']);
    $yeni_sifre = trim($_POST['yeni_sifre']);
    
    // Debug için form verilerini logla
    error_log("Form verileri:");
    error_log("Rol: " . $rol);
    error_log("Durum: " . $durum);
    error_log("Mevcut Durum: " . $kullanici['durum']);
    error_log("Onay Durumu: " . $onay_durumu);
    error_log("Yeni Şifre Girildi mi?: " . (!empty($yeni_sifre) ? 'Evet' : 'Hayır'));
    
    // Hata kontrolü
    $hatalar = [];
    
    // Rol kontrolü
    $izin_verilen_roller = ['admin', 'editor', 'yazar', 'uye'];
    if (!in_array($rol, $izin_verilen_roller)) {
        $hatalar[] = "Geçersiz rol seçimi.";
    }
    
    // Durum kontrolü
    $izin_verilen_durumlar = ['aktif', 'pasif'];
    if (!in_array($durum, $izin_verilen_durumlar)) {
        $hatalar[] = "Geçersiz durum seçimi.";
        error_log("Hata: Geçersiz durum seçimi - " . $durum);
    }
    
    // Onay durumu kontrolü
    $izin_verilen_onay_durumlari = ['beklemede', 'onaylandi', 'reddedildi'];
    if (!in_array($onay_durumu, $izin_verilen_onay_durumlari)) {
        $hatalar[] = "Geçersiz onay durumu seçimi.";
    }
    
    // Boş alan kontrolü
    if (empty($ad_soyad)) $hatalar[] = "Ad Soyad alanı boş bırakılamaz.";
    if (empty($email)) $hatalar[] = "E-posta alanı boş bırakılamaz.";
    if (empty($kullanici_adi)) $hatalar[] = "Kullanıcı adı boş bırakılamaz.";
    
    // E-posta formatı kontrolü
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hatalar[] = "Geçerli bir e-posta adresi giriniz.";
    }
    
    // Kullanıcı adı format kontrolü
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $kullanici_adi)) {
        $hatalar[] = "Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.";
    }
    
    // E-posta ve kullanıcı adı benzersizlik kontrolü
    $sql = "SELECT id, email, kullanici_adi FROM kullanicilar WHERE (email = ? OR kullanici_adi = ?) AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $email, $kullanici_adi, $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['email'] === $email) {
            $hatalar[] = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
        }
        if ($row['kullanici_adi'] === $kullanici_adi) {
            $hatalar[] = "Bu kullanıcı adı başka bir kullanıcı tarafından kullanılıyor.";
        }
    }
    
    // Şifre kontrolü
    if (!empty($yeni_sifre)) {
        if (strlen($yeni_sifre) < 6) {
            $hatalar[] = "Şifre en az 6 karakter olmalıdır.";
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $yeni_sifre)) {
            $hatalar[] = "Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.";
        }
    }
    
    // Hata yoksa güncelle
    if (empty($hatalar)) {
        try {
            $conn->begin_transaction();
            
            if (!empty($yeni_sifre)) {
                // Debug için şifre hash'i
                error_log("Yeni şifre girildi. Hash'leniyor...");
                
                // Şifre ile güncelle
                $yeni_sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
                error_log("Yeni şifre hash'i: " . substr($yeni_sifre_hash, 0, 10) . "...");
                
                $sql = "UPDATE kullanicilar SET 
                    ad_soyad = ?, 
                    email = ?, 
                    kullanici_adi = ?, 
                    rol = ?, 
                    durum = ?, 
                    onay_durumu = ?, 
                    sifre = ? 
                WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $ad_soyad, $email, $kullanici_adi, $rol, $durum, $onay_durumu, $yeni_sifre_hash, $kullanici_id);
                
                error_log("Şifre ile güncelleme SQL: " . $sql);
            } else {
                error_log("Yeni şifre girilmedi. Şifre güncellenmeyecek.");
                
                // Şifre olmadan güncelle
                $sql = "UPDATE kullanicilar SET 
                    ad_soyad = ?, 
                    email = ?, 
                    kullanici_adi = ?, 
                    rol = ?, 
                    durum = ?, 
                    onay_durumu = ? 
                WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $ad_soyad, $email, $kullanici_adi, $rol, $durum, $onay_durumu, $kullanici_id);
                
                error_log("Şifresiz güncelleme SQL: " . $sql);
            }
            
            if ($stmt->execute()) {
                error_log("SQL sorgusu başarıyla çalıştı.");
                
                // Güncelleme sonrası kullanıcı bilgilerini kontrol et
                $kontrol_sql = "SELECT rol, durum, onay_durumu FROM kullanicilar WHERE id = ?";
                $kontrol_stmt = $conn->prepare($kontrol_sql);
                $kontrol_stmt->bind_param("i", $kullanici_id);
                $kontrol_stmt->execute();
                $kontrol_result = $kontrol_stmt->get_result();
                $kontrol_row = $kontrol_result->fetch_assoc();
                
                error_log("Güncelleme sonrası kontrol:");
                error_log("Rol: " . $kontrol_row['rol']);
                error_log("Durum: " . $kontrol_row['durum']);
                error_log("Onay durumu: " . $kontrol_row['onay_durumu']);
                
                // Şifre değişikliğini kontrol et
                if (!empty($yeni_sifre)) {
                    $kontrol_sql = "SELECT sifre FROM kullanicilar WHERE id = ?";
                    $kontrol_stmt = $conn->prepare($kontrol_sql);
                    $kontrol_stmt->bind_param("i", $kullanici_id);
                    $kontrol_stmt->execute();
                    $kontrol_result = $kontrol_stmt->get_result();
                    $kontrol_row = $kontrol_result->fetch_assoc();
                    
                    if ($kontrol_row && password_verify($yeni_sifre, $kontrol_row['sifre'])) {
                        error_log("Şifre başarıyla güncellendi ve doğrulandı.");
                    } else {
                        error_log("UYARI: Şifre güncellemesi başarısız olabilir!");
                    }
                }
                
                // Durum değiştiyse bildirim gönder
                if ($kullanici['durum'] !== $durum) {
                    error_log("Durum değişikliği tespit edildi: " . $kullanici['durum'] . " -> " . $durum);
                    
                    $durum_mesaji = $durum === 'aktif' 
                        ? "Hesabınız aktif hale getirildi. Artık sisteme giriş yapabilirsiniz."
                        : "Hesabınız pasif hale getirildi. Sisteme giriş yapamazsınız.";
                    
                    $sql = "INSERT INTO bildirimler (kullanici_id, mesaj, tarih) VALUES (?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $kullanici_id, $durum_mesaji);
                    
                    if ($stmt->execute()) {
                        error_log("Durum değişikliği bildirimi gönderildi.");
                    } else {
                        error_log("Durum değişikliği bildirimi gönderilemedi!");
                    }
                }
                
                // Debug için eklenen kod
                error_log("Güncelleme başarılı. Onay durumu: " . $onay_durumu);
                error_log("SQL: " . $sql);
                error_log("Kullanıcı ID: " . $kullanici_id);
                
                // Onay durumu değiştiyse bildirim gönder
                if ($kullanici['onay_durumu'] !== $onay_durumu) {
                    $bildirim_mesaji = "";
                    switch ($onay_durumu) {
                        case 'onaylandi':
                            $bildirim_mesaji = "Hesabınız onaylandı! Artık sisteme giriş yapabilirsiniz.";
                            break;
                        case 'reddedildi':
                            $bildirim_mesaji = "Üzgünüz, hesabınız reddedildi. Daha fazla bilgi için lütfen iletişime geçin.";
                            break;
                        case 'beklemede':
                            $bildirim_mesaji = "Hesabınız inceleme sürecine alındı.";
                            break;
                    }
                    
                    if (!empty($bildirim_mesaji)) {
                        $sql = "INSERT INTO bildirimler (kullanici_id, mesaj, tarih) VALUES (?, ?, NOW())";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("is", $kullanici_id, $bildirim_mesaji);
                        $stmt->execute();
                    }
                }
                
                $conn->commit();
                $_SESSION['success_message'] = "Kullanıcı bilgileri başarıyla güncellendi.";
                header("Location: kullanici-duzenle.php?id=" . $kullanici_id);
                exit();
            } else {
                throw new Exception("Güncelleme işlemi başarısız oldu.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenle - Admin Paneli</title>
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
                        <h2 class="h4 mb-0">Kullanıcı Düzenle</h2>
                        
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
                    
                    <div class="content-wrapper">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-12">
                                    <?php if (isset($success_message)): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?php echo $success_message; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($hatalar)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <ul class="mb-0">
                                                <?php foreach ($hatalar as $hata): ?>
                                                    <li><?php echo $hata; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($error_message)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php echo $error_message; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card">
                                        <div class="card-body">
                                            <form class="user-form" method="POST" action="kullanici-duzenle.php?id=<?php echo $kullanici_id; ?>">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Ad Soyad</label>
                                                            <input type="text" name="ad_soyad" class="form-control" value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">E-posta</label>
                                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($kullanici['email']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Kullanıcı Adı</label>
                                                            <input type="text" name="kullanici_adi" class="form-control" value="<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?>" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Rol</label>
                                                            <select name="rol" class="form-select" required>
                                                                <option value="">Rol Seçin</option>
                                                                <option value="admin" <?php echo $kullanici['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                <option value="editor" <?php echo $kullanici['rol'] === 'editor' ? 'selected' : ''; ?>>Editör</option>
                                                                <option value="yazar" <?php echo $kullanici['rol'] === 'yazar' ? 'selected' : ''; ?>>Yazar</option>
                                                                <option value="uye" <?php echo $kullanici['rol'] === 'uye' ? 'selected' : ''; ?>>Üye</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Durum</label>
                                                            <select name="durum" class="form-select" required id="durumSelect">
                                                                <option value="">Durum Seçin</option>
                                                                <option value="aktif" <?php echo $kullanici['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                                                <option value="pasif" <?php echo $kullanici['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                                            </select>
                                                            <div class="invalid-feedback">
                                                                Lütfen bir durum seçin.
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Onay Durumu</label>
                                                            <select name="onay_durumu" class="form-select" required>
                                                                <option value="">Onay Durumu Seçin</option>
                                                                <option value="beklemede" <?php echo $kullanici['onay_durumu'] === 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                                                                <option value="onaylandi" <?php echo $kullanici['onay_durumu'] === 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                                                                <option value="reddedildi" <?php echo $kullanici['onay_durumu'] === 'reddedildi' ? 'selected' : ''; ?>>Reddedildi</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Yeni Şifre</label>
                                                            <div class="input-group">
                                                                <input type="password" name="yeni_sifre" class="form-control" id="yeni_sifre" minlength="6">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('yeni_sifre')">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                            <small class="text-muted">Şifreyi değiştirmek istemiyorsanız boş bırakın. Minimum 6 karakter, en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.</small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-end mt-4">
                                                    <a href="kullanicilar.php" class="btn btn-light me-2">
                                                        <i class="fas fa-arrow-left me-2"></i>Geri Dön
                                                    </a>
                                                    <button type="submit" class="btn btn-primary" id="saveButton">
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form gönderim kontrolü
        document.querySelector('.user-form').addEventListener('submit', function(e) {
            // Butonu devre dışı bırak
            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Kaydediliyor...';
            
            // Durum kontrolü
            const durumSelect = document.getElementById('durumSelect');
            if (!durumSelect.value) {
                e.preventDefault();
                durumSelect.classList.add('is-invalid');
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Değişiklikleri Kaydet';
                alert('Lütfen bir durum seçin.');
                return;
            }
            
            // Durum değişikliği onayı
            if (durumSelect.value !== '<?php echo $kullanici['durum']; ?>') {
                if (!confirm('Kullanıcının durumunu "' + durumSelect.options[durumSelect.selectedIndex].text + '" olarak değiştirmek istediğinizden emin misiniz?')) {
                    e.preventDefault();
                    saveButton.disabled = false;
                    saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Değişiklikleri Kaydet';
                    return;
                }
            }
            
            // Diğer form kontrolleri...
            
            // E-posta kontrolü
            const emailField = this.querySelector('input[type="email"]');
            if (emailField && !isValidEmail(emailField.value)) {
                e.preventDefault();
                emailField.classList.add('is-invalid');
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Değişiklikleri Kaydet';
                alert('Lütfen geçerli bir e-posta adresi girin.');
                return;
            }
            
            // Şifre kontrolü
            const passwordField = document.getElementById('yeni_sifre');
            if (passwordField.value) {
                if (passwordField.value.length < 6) {
                    e.preventDefault();
                    passwordField.classList.add('is-invalid');
                    saveButton.disabled = false;
                    saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Değişiklikleri Kaydet';
                    alert('Şifre en az 6 karakter olmalıdır.');
                    return;
                }
                
                // Şifre karmaşıklık kontrolü
                const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/;
                if (!passwordRegex.test(passwordField.value)) {
                    e.preventDefault();
                    passwordField.classList.add('is-invalid');
                    saveButton.disabled = false;
                    saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Değişiklikleri Kaydet';
                    alert('Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.');
                    return;
                }
            }
            
            // Rol kontrolü
            const rolField = this.querySelector('select[name="rol"]');
            if (!['admin', 'editor', 'yazar', 'uye'].includes(rolField.value)) {
                e.preventDefault();
                rolField.classList.add('is-invalid');
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Değişiklikleri Kaydet';
                alert('Lütfen geçerli bir rol seçin.');
                return;
            }
        });

        // E-posta doğrulama fonksiyonu
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Input alanları değiştiğinde invalid sınıfını kaldır
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

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

    <style>
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</body>
</html> 