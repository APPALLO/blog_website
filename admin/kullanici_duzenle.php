<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// ID kontrolü
if (!isset($_GET['id'])) {
    header("Location: kullanicilar.php");
    exit();
}

$kullanici_id = intval($_GET['id']);

// Kullanıcı bilgilerini al
$sql = "SELECT * FROM kullanicilar WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kullanici_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: kullanicilar.php");
    exit();
}

$kullanici = $result->fetch_assoc();

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $email = trim($_POST['email']);
    $ad_soyad = trim($_POST['ad_soyad']);
    $rol = $_POST['rol'];
    $durum = isset($_POST['durum']) ? 1 : 0;
    $yeni_sifre = trim($_POST['yeni_sifre']);
    
    $hata = false;
    $hatalar = array();
    
    // Kullanıcı adı kontrolü
    if (empty($kullanici_adi)) {
        $hatalar[] = "Kullanıcı adı boş bırakılamaz.";
        $hata = true;
    } else {
        $stmt = $conn->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? AND id != ?");
        $stmt->bind_param("si", $kullanici_adi, $kullanici_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $hatalar[] = "Bu kullanıcı adı zaten kullanılıyor.";
            $hata = true;
        }
    }
    
    // Email kontrolü
    if (empty($email)) {
        $hatalar[] = "E-posta adresi boş bırakılamaz.";
        $hata = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hatalar[] = "Geçerli bir e-posta adresi giriniz.";
        $hata = true;
    } else {
        $stmt = $conn->prepare("SELECT id FROM kullanicilar WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $kullanici_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $hatalar[] = "Bu e-posta adresi zaten kullanılıyor.";
            $hata = true;
        }
    }
    
    // Yeni şifre kontrolü
    if (!empty($yeni_sifre) && strlen($yeni_sifre) < 6) {
        $hatalar[] = "Şifre en az 6 karakter olmalıdır.";
        $hata = true;
    }
    
    if (!$hata) {
        // Kullanıcıyı güncelle
        if (!empty($yeni_sifre)) {
            $sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $sql = "UPDATE kullanicilar SET kullanici_adi = ?, email = ?, sifre = ?, ad_soyad = ?, rol = ?, durum = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssii", $kullanici_adi, $email, $sifre_hash, $ad_soyad, $rol, $durum, $kullanici_id);
        } else {
            $sql = "UPDATE kullanicilar SET kullanici_adi = ?, email = ?, ad_soyad = ?, rol = ?, durum = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $kullanici_adi, $email, $ad_soyad, $rol, $durum, $kullanici_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['mesaj'] = "Kullanıcı başarıyla güncellendi.";
            $_SESSION['mesaj_tur'] = "success";
            header("Location: kullanicilar.php");
            exit();
        } else {
            $hatalar[] = "Kullanıcı güncellenirken bir hata oluştu.";
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
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <div class="col p-0">
                <div class="main-content">
                    <div class="top-bar">
                        <h2 class="h4 mb-0">Kullanıcı Düzenle</h2>
                        
                        <div class="top-bar-buttons">
                            <a href="kullanicilar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Geri Dön
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($hatalar)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($hatalar as $hata): ?>
                            <li><?php echo $hata; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="kullanici_adi" class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" value="<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?>" required>
                                        <div class="invalid-feedback">Kullanıcı adı gereklidir.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">E-posta <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($kullanici['email']); ?>" required>
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="yeni_sifre" class="form-label">Yeni Şifre</label>
                                        <input type="password" class="form-control" id="yeni_sifre" name="yeni_sifre">
                                        <small class="text-muted">Şifreyi değiştirmek istemiyorsanız boş bırakın.</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="ad_soyad" class="form-label">Ad Soyad</label>
                                        <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                        <select class="form-select" id="rol" name="rol" required <?php echo ($kullanici_id == $_SESSION['admin_id']) ? 'disabled' : ''; ?>>
                                            <option value="kullanici" <?php echo $kullanici['rol'] == 'kullanici' ? 'selected' : ''; ?>>Kullanıcı</option>
                                            <option value="yazar" <?php echo $kullanici['rol'] == 'yazar' ? 'selected' : ''; ?>>Yazar</option>
                                            <option value="admin" <?php echo $kullanici['rol'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <div class="invalid-feedback">Rol seçimi gereklidir.</div>
                                        <?php if ($kullanici_id == $_SESSION['admin_id']): ?>
                                        <input type="hidden" name="rol" value="<?php echo $kullanici['rol']; ?>">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="durum" name="durum" <?php echo $kullanici['durum'] ? 'checked' : ''; ?> <?php echo ($kullanici_id == $_SESSION['admin_id']) ? 'disabled' : ''; ?>>
                                            <label class="form-check-label" for="durum">
                                                Kullanıcı Aktif
                                            </label>
                                            <?php if ($kullanici_id == $_SESSION['admin_id']): ?>
                                            <input type="hidden" name="durum" value="1">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
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