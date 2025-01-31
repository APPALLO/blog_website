<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Ayarları getir
$sql = "SELECT * FROM site_ayarlari WHERE id = 1";
$result = $conn->query($sql);
$ayarlar = $result->fetch_assoc();

// Eğer ayarlar boşsa varsayılan değerleri kullan
if (!$ayarlar) {
    $ayarlar = [
        'site_baslik' => 'Blog Sitesi',
        'site_aciklama' => 'Blog sitesi açıklaması',
        'site_anahtar_kelimeler' => 'blog, site',
        'site_logo' => '',
        'site_favicon' => '',
        'site_email' => 'info@example.com',
        'site_telefon' => '',
        'site_adres' => '',
        'facebook_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'linkedin_url' => '',
        'youtube_url' => ''
    ];
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_baslik = $_POST['site_baslik'];
    $site_aciklama = $_POST['site_aciklama'];
    $site_anahtar_kelimeler = $_POST['site_anahtar_kelimeler'];
    $site_email = $_POST['site_email'];
    $site_telefon = $_POST['site_telefon'];
    $site_adres = $_POST['site_adres'];
    $facebook_url = $_POST['facebook_url'];
    $twitter_url = $_POST['twitter_url'];
    $instagram_url = $_POST['instagram_url'];
    $linkedin_url = $_POST['linkedin_url'];
    $youtube_url = $_POST['youtube_url'];
    
    // Logo yükleme
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'gif'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $yeni_dosya_adi = 'logo_' . time() . '.' . $dosya_uzantisi;
            $hedef_klasor = '../uploads/';
            $hedef_dosya = $hedef_klasor . $yeni_dosya_adi;
            
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $hedef_dosya)) {
                $site_logo = $yeni_dosya_adi;
            }
        }
    }
    
    // Favicon yükleme
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === 0) {
        $izin_verilen_uzantilar = ['ico', 'png'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $yeni_dosya_adi = 'favicon_' . time() . '.' . $dosya_uzantisi;
            $hedef_klasor = '../uploads/';
            $hedef_dosya = $hedef_klasor . $yeni_dosya_adi;
            
            if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $hedef_dosya)) {
                $site_favicon = $yeni_dosya_adi;
            }
        }
    }
    
    // Ayarları güncelle
    $sql = "UPDATE site_ayarlari SET 
            site_baslik = ?, 
            site_aciklama = ?, 
            site_anahtar_kelimeler = ?,
            site_email = ?,
            site_telefon = ?,
            site_adres = ?,
            facebook_url = ?,
            twitter_url = ?,
            instagram_url = ?,
            linkedin_url = ?,
            youtube_url = ?";
    
    $params = [
        $site_baslik, 
        $site_aciklama, 
        $site_anahtar_kelimeler,
        $site_email,
        $site_telefon,
        $site_adres,
        $facebook_url,
        $twitter_url,
        $instagram_url,
        $linkedin_url,
        $youtube_url
    ];
    $types = "sssssssssss";
    
    if (isset($site_logo)) {
        $sql .= ", site_logo = ?";
        $params[] = $site_logo;
        $types .= "s";
    }
    
    if (isset($site_favicon)) {
        $sql .= ", site_favicon = ?";
        $params[] = $site_favicon;
        $types .= "s";
    }
    
    $sql .= " WHERE id = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $success_message = "Ayarlar başarıyla güncellendi.";
        // Ayarları yeniden yükle
        $result = $conn->query("SELECT * FROM site_ayarlari WHERE id = 1");
        $ayarlar = $result->fetch_assoc();
    } else {
        $error_message = "Ayarlar güncellenirken bir hata oluştu.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Ayarları - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 2rem;
        }

        .settings-card .card-body {
            padding: 2rem;
        }

        .settings-header {
            color: #4361ee;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .settings-header i {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            color: #4361ee;
        }

        .form-label {
            font-weight: 500;
            color: #444;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .image-preview {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-top: 0.5rem;
        }

        .image-preview img {
            max-width: 200px;
            height: auto;
        }

        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #3651d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .social-input {
            position: relative;
        }

        .social-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .social-input input {
            padding-left: 2.75rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .section-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 2rem 0;
        }
    </style>
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
                        <h2 class="h4 mb-0">Site Ayarları</h2>
                        
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

                        <div class="content-card">
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data" class="settings-form">
                                    <div class="row">
                                        <!-- Genel Ayarlar -->
                                        <div class="col-md-6 mb-4">
                                            <div class="content-header mb-3">
                                                <i class="fas fa-cog"></i>
                                                Genel Ayarlar
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Site Başlığı</label>
                                                <input type="text" name="site_baslik" class="form-control" value="<?php echo htmlspecialchars($ayarlar['site_baslik']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Site Açıklaması</label>
                                                <textarea name="site_aciklama" class="form-control" rows="3" required><?php echo htmlspecialchars($ayarlar['site_aciklama']); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Anahtar Kelimeler</label>
                                                <input type="text" name="site_anahtar_kelimeler" class="form-control" value="<?php echo htmlspecialchars($ayarlar['site_anahtar_kelimeler']); ?>" required>
                                                <small class="text-muted">Virgülle ayırarak yazın</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Logo</label>
                                                <input type="file" name="site_logo" class="form-control" accept="image/*">
                                                <?php if (!empty($ayarlar['site_logo'])): ?>
                                                <div class="mt-2">
                                                    <img src="../uploads/<?php echo htmlspecialchars($ayarlar['site_logo']); ?>" alt="Site Logo" class="img-thumbnail" style="height: 50px;">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Favicon</label>
                                                <input type="file" name="site_favicon" class="form-control" accept=".ico,.png">
                                                <?php if (!empty($ayarlar['site_favicon'])): ?>
                                                <div class="mt-2">
                                                    <img src="../uploads/<?php echo htmlspecialchars($ayarlar['site_favicon']); ?>" alt="Favicon" class="img-thumbnail" style="height: 32px;">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- İletişim Bilgileri -->
                                        <div class="col-md-6 mb-4">
                                            <div class="content-header mb-3">
                                                <i class="fas fa-address-card"></i>
                                                İletişim Bilgileri
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">E-posta Adresi</label>
                                                <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($ayarlar['site_email']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Telefon</label>
                                                <input type="tel" name="site_telefon" class="form-control" value="<?php echo htmlspecialchars($ayarlar['site_telefon']); ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Adres</label>
                                                <textarea name="site_adres" class="form-control" rows="3"><?php echo htmlspecialchars($ayarlar['site_adres']); ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <!-- Sosyal Medya -->
                                        <div class="col-12">
                                            <div class="content-header mb-3">
                                                <i class="fas fa-share-alt"></i>
                                                Sosyal Medya
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Facebook</label>
                                                        <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($ayarlar['facebook_url']); ?>">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Twitter</label>
                                                        <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($ayarlar['twitter_url']); ?>">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Instagram</label>
                                                        <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($ayarlar['instagram_url']); ?>">
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">LinkedIn</label>
                                                        <input type="url" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars($ayarlar['linkedin_url']); ?>">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">YouTube</label>
                                                        <input type="url" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars($ayarlar['youtube_url']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Ayarları Kaydet
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
    </script>
</body>
</html>