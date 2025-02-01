<?php
if (!isset($title)) {
    $title = "Admin Paneli";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #4834d4;
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            z-index: 1000;
        }
        
        .sidebar-brand {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            padding: 0 10px;
        }
        
        .nav-link {
            color: white !important;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link i {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }
        }
        
        .toggle-sidebar {
            display: none;
            position: fixed;
            left: 1rem;
            top: 1rem;
            z-index: 1050;
            background: white;
            border: none;
            padding: 8px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .default-avatar {
            background: linear-gradient(45deg, #4361ee, #3f37c9);
            font-weight: 600;
            font-size: 1.2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            <div class="sidebar">
                <div class="sidebar-brand">
                    Blog Admin
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home"></i>Ana Sayfa
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'yazilar.php' ? 'active' : ''; ?>" href="yazilar.php">
                        <i class="fas fa-file-alt"></i>Yazılar
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kategoriler.php' ? 'active' : ''; ?>" href="kategoriler.php">
                        <i class="fas fa-tags"></i>Kategoriler
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'yorumlar.php' ? 'active' : ''; ?>" href="yorumlar.php">
                        <i class="fas fa-comments"></i>Yorumlar
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kullanicilar.php' ? 'active' : ''; ?>" href="kullanicilar.php">
                        <i class="fas fa-users"></i>Kullanıcılar
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'mesajlar.php' ? 'active' : ''; ?>" href="mesajlar.php">
                        <i class="fas fa-envelope"></i>Mesajlar
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ayarlar.php' ? 'active' : ''; ?>" href="ayarlar.php">
                        <i class="fas fa-sliders-h"></i>Ayarlar
                    </a>
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-home"></i>Ana Sayfaya Dön
                    </a>
                    <a class="nav-link" href="../cikis.php">
                        <i class="fas fa-sign-out-alt"></i>Çıkış Yap
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col p-0">
                <div class="main-content">
                    <!-- Üst Bar -->
                    <div class="top-bar d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h4 mb-0"><?php echo htmlspecialchars($title); ?></h2>
                        
                        <div class="user-profile d-flex align-items-center">
                            <div class="user-info text-end me-3">
                                <div class="fw-medium"><?php echo htmlspecialchars($_SESSION['admin']['ad_soyad']); ?></div>
                                <small class="text-muted"><?php echo ucfirst($_SESSION['admin']['rol']); ?></small>
                            </div>
                            <?php if (isset($_SESSION['admin']['profil_resmi']) && !empty($_SESSION['admin']['profil_resmi']) && file_exists('../uploads/' . $_SESSION['admin']['profil_resmi'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($_SESSION['admin']['profil_resmi']); ?>" 
                                     alt="<?php echo htmlspecialchars($_SESSION['admin']['ad_soyad']); ?>" 
                                     class="rounded-circle"
                                     style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                                <div class="default-avatar rounded-circle d-flex align-items-center justify-content-center bg-primary text-white"
                                     style="width: 40px; height: 40px;">
                                    <?php
                                    $ad_soyad = $_SESSION['admin']['ad_soyad'] ?? 'Kullanıcı';
                                    $initials = mb_substr($ad_soyad, 0, 1, 'UTF-8');
                                    echo htmlspecialchars($initials);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                    <script>
                        document.getElementById('toggleSidebar').addEventListener('click', function() {
                            document.querySelector('.sidebar').classList.toggle('active');
                        });

                        window.addEventListener('resize', function() {
                            if (window.innerWidth > 768) {
                                document.querySelector('.sidebar').classList.remove('active');
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 