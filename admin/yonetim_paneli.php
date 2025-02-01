<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Sayfa başlığı ve aktif menü için değişkenler
$sayfa_basligi = isset($sayfa_basligi) ? $sayfa_basligi : "Yönetim Paneli";
$aktif_sayfa = isset($aktif_sayfa) ? $aktif_sayfa : "panel";

// Son giriş zamanını güncelle
$sql = "UPDATE adminler SET son_giris = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sayfa_basligi; ?> - Blog Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: #fff;
            padding: 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.1);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: #0d6efd;
        }
        
        .main-content {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .page-header {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        
        .content-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        
        .nav-badge {
            background: rgba(255,255,255,.2);
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: auto;
            font-size: 12px;
        }
        
        .user-dropdown {
            margin-left: auto;
        }
        
        .user-dropdown .dropdown-toggle {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="mb-4">Blog Admin</h3>
                <nav class="nav flex-column">
                    <a class="nav-link <?php echo $aktif_sayfa == 'panel' ? 'active' : ''; ?>" href="panel.php">
                        <i class="fas fa-home me-2"></i>Ana Sayfa
                    </a>
                    <a class="nav-link <?php echo $aktif_sayfa == 'yazilar' ? 'active' : ''; ?>" href="yazilar.php">
                        <i class="fas fa-file-alt me-2"></i>Yazılar
                        <?php
                        $sql = "SELECT COUNT(*) as sayi FROM blog_yazilar WHERE durum = 'taslak'";
                        $result = $conn->query($sql);
                        $taslak_sayisi = $result->fetch_assoc()['sayi'];
                        if($taslak_sayisi > 0):
                        ?>
                        <span class="nav-badge"><?php echo $taslak_sayisi; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link <?php echo $aktif_sayfa == 'kategoriler' ? 'active' : ''; ?>" href="kategoriler.php">
                        <i class="fas fa-tags me-2"></i>Kategoriler
                    </a>
                    <a class="nav-link <?php echo $aktif_sayfa == 'yorumlar' ? 'active' : ''; ?>" href="yorumlar.php">
                        <i class="fas fa-comments me-2"></i>Yorumlar
                        <?php
                        $sql = "SELECT COUNT(*) as sayi FROM yorumlar WHERE durum = 'beklemede'";
                        $result = $conn->query($sql);
                        $bekleyen_yorum = $result->fetch_assoc()['sayi'];
                        if($bekleyen_yorum > 0):
                        ?>
                        <span class="nav-badge"><?php echo $bekleyen_yorum; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link <?php echo $aktif_sayfa == 'kullanicilar' ? 'active' : ''; ?>" href="kullanicilar.php">
                        <i class="fas fa-users me-2"></i>Kullanıcılar
                    </a>
                    <a class="nav-link <?php echo $aktif_sayfa == 'ayarlar' ? 'active' : ''; ?>" href="ayarlar.php">
                        <i class="fas fa-cog me-2"></i>Ayarlar
                    </a>
                    <a class="nav-link text-danger" href="cikis.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                    </a>
                </nav>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Üst Bar -->
                <div class="page-header d-flex align-items-center">
                    <h2 class="h4 mb-0"><?php echo $sayfa_basligi; ?></h2>
                    
                    <!-- Kullanıcı Menüsü -->
                    <div class="user-dropdown dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['admin_kullanici_adi'], 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['admin_kullanici_adi']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="ayarlar.php"><i class="fas fa-cog me-2"></i>Ayarlar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="cikis.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Sayfa İçeriği -->
                <div class="content-card">
                    <?php if(isset($icerik)): ?>
                        <?php echo $icerik; ?>
                    <?php else: ?>
                        <p>Sayfa içeriği burada görüntülenecek.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if(isset($sayfa_js)): ?>
        <?php echo $sayfa_js; ?>
    <?php endif; ?>
</body>
</html><?php
// Veritabanı bağlantısını kapat
$conn->close();
?> 