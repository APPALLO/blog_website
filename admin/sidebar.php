<?php
// Aktif sayfayı belirle
$current_page = basename($_SERVER['PHP_SELF']);

// İstatistikleri al
$istatistikler = array();

// Toplam yazı sayısı
$sql = "SELECT COUNT(*) as toplam FROM blog_yazilar";
$result = $conn->query($sql);
$istatistikler['yazi_sayisi'] = $result->fetch_assoc()['toplam'];

// Toplam kullanıcı sayısı
$sql = "SELECT COUNT(*) as toplam FROM kullanicilar";
$result = $conn->query($sql);
$istatistikler['kullanici_sayisi'] = $result->fetch_assoc()['toplam'];

// Toplam yorum sayısı
$sql = "SELECT COUNT(*) as toplam FROM yorumlar WHERE durum = 'onay_bekliyor'";
$result = $conn->query($sql);
$istatistikler['bekleyen_yorum'] = $result->fetch_assoc()['toplam'];

// Okunmamış mesaj sayısı
$sql = "SELECT COUNT(*) as toplam FROM iletisim_mesajlari WHERE durum = 'okunmamis'";
$result = $conn->query($sql);
$istatistikler['okunmamis_mesaj'] = $result->fetch_assoc()['toplam'];
?>

<!-- Mobil Menü Butonu -->
<button class="toggle-sidebar" id="toggleSidebar">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<div class="col-auto p-0">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-newspaper me-2"></i>Blog Admin
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $current_page == 'panel.php' ? 'active' : ''; ?>" href="panel.php">
                <i class="fas fa-home me-2"></i>Ana Sayfa
            </a>
            <a class="nav-link <?php echo $current_page == 'yazilar.php' ? 'active' : ''; ?>" href="yazilar.php">
                <i class="fas fa-file-alt me-2"></i>Yazılar
                <?php if($istatistikler['yazi_sayisi'] > 0): ?>
                <span class="badge bg-primary rounded-pill ms-auto"><?php echo $istatistikler['yazi_sayisi']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo $current_page == 'kategoriler.php' ? 'active' : ''; ?>" href="kategoriler.php">
                <i class="fas fa-tags me-2"></i>Kategoriler
            </a>
            <a class="nav-link <?php echo $current_page == 'yorumlar.php' ? 'active' : ''; ?>" href="yorumlar.php">
                <i class="fas fa-comments me-2"></i>Yorumlar
                <?php if($istatistikler['bekleyen_yorum'] > 0): ?>
                <span class="badge bg-warning rounded-pill ms-auto"><?php echo $istatistikler['bekleyen_yorum']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo $current_page == 'kullanicilar.php' ? 'active' : ''; ?>" href="kullanicilar.php">
                <i class="fas fa-users me-2"></i>Kullanıcılar
                <?php if($istatistikler['kullanici_sayisi'] > 0): ?>
                <span class="badge bg-primary rounded-pill ms-auto"><?php echo $istatistikler['kullanici_sayisi']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo $current_page == 'mesajlar.php' ? 'active' : ''; ?>" href="mesajlar.php">
                <i class="fas fa-envelope me-2"></i>Mesajlar
                <?php if($istatistikler['okunmamis_mesaj'] > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto"><?php echo $istatistikler['okunmamis_mesaj']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo $current_page == 'ayarlar.php' ? 'active' : ''; ?>" href="ayarlar.php">
                <i class="fas fa-cog me-2"></i>Ayarlar
            </a>
            <div class="sidebar-divider"></div>
            <a class="nav-link" href="../" target="_blank">
                <i class="fas fa-external-link-alt me-2"></i>Siteyi Görüntüle
            </a>
            <a class="nav-link text-danger" href="cikis.php">
                <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
            </a>
        </nav>
    </div>
</div>

<style>
/* Sidebar Stilleri */
.sidebar {
    width: 250px;
    height: 100vh;
    background: #2c3e50;
    position: fixed;
    transition: all 0.3s ease;
    z-index: 1000;
}

.sidebar-brand {
    padding: 1rem;
    color: #fff;
    font-size: 1.25rem;
    font-weight: 600;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
}

.sidebar .nav-link.active {
    color: #fff;
    background: #3498db;
}

.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 0.5rem 0;
}

/* Mobil Menü Butonu */
.toggle-sidebar {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: #2c3e50;
    border: none;
    color: #fff;
    padding: 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;
}

/* Responsive Tasarım */
@media (max-width: 768px) {
    .toggle-sidebar {
        display: block;
    }
    
    .sidebar {
        left: -250px;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .main-content {
        margin-left: 0 !important;
    }
}

/* Ana İçerik Alanı */
.main-content {
    margin-left: 250px;
    padding: 1rem;
    transition: all 0.3s ease;
}

/* Top Bar */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.top-bar-buttons {
    display: flex;
    gap: 0.5rem;
}
</style> 