<?php
// Aktif sayfayı kontrol et
$aktif_sayfa = isset($aktif_sayfa) ? $aktif_sayfa : '';

// Okunmamış mesaj sayısını al
$sql = "SELECT COUNT(*) as okunmamis FROM iletisim_mesajlari WHERE durum = 'okunmamis'";
$result = $conn->query($sql);
$okunmamis_mesaj = $result->fetch_assoc()['okunmamis'];

// Onay bekleyen kullanıcı sayısını al
$sql = "SELECT COUNT(*) as bekleyen FROM kullanicilar WHERE durum = 'beklemede'";
$result = $conn->query($sql);
$bekleyen_kullanici = $result->fetch_assoc()['bekleyen'];
?>

<!-- Sidebar -->
<div class="col-auto p-0">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-blog me-2"></i>Blog Admin
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'panel.php' ? 'active' : ''; ?>" href="panel.php">
                <i class="fas fa-home me-2"></i>Ana Sayfa
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'yazilar.php' ? 'active' : ''; ?>" href="yazilar.php">
                <i class="fas fa-file-alt me-2"></i>Yazılar
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kategoriler.php' ? 'active' : ''; ?>" href="kategoriler.php">
                <i class="fas fa-tag me-2"></i>Kategoriler
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'yorumlar.php' ? 'active' : ''; ?>" href="yorumlar.php">
                <i class="fas fa-comment me-2"></i>Yorumlar
                <?php if(isset($istatistikler['bekleyen_yorum']) && $istatistikler['bekleyen_yorum'] > 0): ?>
                <span class="nav-badge"><?php echo $istatistikler['bekleyen_yorum']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kullanicilar.php' ? 'active' : ''; ?>" href="kullanicilar.php">
                <i class="fas fa-user me-2"></i>Kullanıcılar
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'mesajlar.php' ? 'active' : ''; ?>" href="mesajlar.php">
                <i class="fas fa-comment-dots me-2"></i>Mesajlar
                <?php if(isset($istatistikler['okunmamis_mesaj']) && $istatistikler['okunmamis_mesaj'] > 0): ?>
                <span class="nav-badge"><?php echo $istatistikler['okunmamis_mesaj']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ayarlar.php' ? 'active' : ''; ?>" href="ayarlar.php">
                <i class="fas fa-sliders-h me-2"></i>Ayarlar
            </a>
            <a class="nav-link text-danger" href="cikis.php">
                <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
            </a>
        </nav>
    </div>
</div>

<style>
.sidebar {
    background: linear-gradient(135deg, #4361ee, #3f37c9);
    min-height: 100vh;
    width: 260px;
    transition: all 0.3s ease;
    position: fixed;
    z-index: 1000;
}

.sidebar-brand {
    padding: 1.5rem;
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.nav-link {
    color: rgba(255,255,255,0.8) !important;
    padding: 0.8rem 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    font-weight: 500;
    border-radius: 8px;
    margin: 0.2rem 0.8rem;
}

.nav-link:hover {
    color: white !important;
    background: rgba(255,255,255,0.1);
}

.nav-link.active {
    color: white !important;
    background: rgba(255,255,255,0.2);
}

.nav-badge {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.2);
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.75rem;
}

.nav-item:last-child {
    margin-top: auto;
}

.nav-item:last-child .nav-link {
    margin-bottom: 1rem;
}
</style> 
</style> 