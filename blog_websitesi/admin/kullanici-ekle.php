<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'];
    $email = $_POST['email'];
    $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
    $ad_soyad = $_POST['ad_soyad'];
    $rol = $_POST['rol'];
    $durum = $_POST['durum'];
    
    // Kullanıcı adı ve email kontrolü
    $sql = "SELECT COUNT(*) as sayi FROM kullanicilar WHERE kullanici_adi = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $kullanici_adi, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['sayi'] > 0) {
        $mesaj = array(
            'tip' => 'danger',
            'icerik' => 'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.'
        );
    } else {
        // Kullanıcıyı veritabanına ekle
        $sql = "INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, rol, durum, kayit_tarihi) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $kullanici_adi, $email, $sifre, $ad_soyad, $rol, $durum);
        
        if ($stmt->execute()) {
            $mesaj = array(
                'tip' => 'success',
                'icerik' => 'Kullanıcı başarıyla eklendi.'
            );
            header("Location: kullanicilar.php?mesaj=eklendi");
            exit();
        } else {
            $mesaj = array(
                'tip' => 'danger',
                'icerik' => 'Kullanıcı eklenirken bir hata oluştu.'
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Kullanıcı Ekle - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .user-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 2rem;
        }

        .user-card .card-body {
            padding: 2rem;
        }

        .user-header {
            color: #4361ee;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-header i {
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

        .password-toggle {
            position: relative;
        }

        .password-toggle .toggle-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
        }

        .role-select {
            background-color: #f8f9fa;
        }

        .status-select {
            background-color: #f8f9fa;
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
                        <h2 class="h4 mb-0">Yeni Kullanıcı Ekle</h2>
                        
                        <div class="d-flex align-items-center gap-3">
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
                        <?php if (isset($mesaj)): ?>
                        <div class="alert alert-<?php echo $mesaj['tip']; ?> alert-dismissible fade show" role="alert">
                            <i class="fas <?php echo $mesaj['tip'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                            <?php echo $mesaj['icerik']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <div class="user-card">
                            <div class="card-body">
                                <div class="user-header">
                                    <i class="fas fa-user-plus"></i>
                                    Kullanıcı Bilgileri
                                </div>
                                
                                <form action="" method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Kullanıcı Adı</label>
                                                <input type="text" class="form-control" name="kullanici_adi" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">E-posta Adresi</label>
                                                <input type="email" class="form-control" name="email" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Şifre</label>
                                                <div class="password-toggle">
                                                    <input type="password" class="form-control" name="sifre" required>
                                                    <i class="fas fa-eye toggle-icon" onclick="togglePassword(this)"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Ad Soyad</label>
                                                <input type="text" class="form-control" name="ad_soyad" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Rol</label>
                                                <select class="form-select role-select" name="rol">
                                                    <option value="kullanici">Kullanıcı</option>
                                                    <option value="editor">Editör</option>
                                                    <option value="admin">Admin</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Durum</label>
                                                <select class="form-select status-select" name="durum">
                                                    <option value="aktif">Aktif</option>
                                                    <option value="beklemede">Beklemede</option>
                                                    <option value="engelli">Engelli</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Kullanıcı Ekle
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