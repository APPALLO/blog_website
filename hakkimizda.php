<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'baglan.php';

// Site istatistiklerini getir
$sql = "SELECT 
        (SELECT COUNT(*) FROM blog_yazilar WHERE durum = 'yayinda') as yazi_sayisi,
        (SELECT COUNT(*) FROM kullanicilar WHERE durum = 1) as yazar_sayisi,
        (SELECT COUNT(*) FROM yorumlar WHERE durum = 'onaylanmis') as yorum_sayisi,
        (SELECT COUNT(*) FROM kategoriler) as kategori_sayisi";
$istatistikler = $conn->query($sql)->fetch_assoc();

// En aktif yazarları getir
$sql = "SELECT k.id, k.ad_soyad, k.kullanici_adi, 
        COUNT(b.id) as yazi_sayisi,
        SUM(b.goruntulenme) as toplam_goruntulenme
        FROM kullanicilar k
        LEFT JOIN blog_yazilar b ON k.id = b.yazar_id
        WHERE k.durum = 1 AND (b.durum = 'yayinda' OR b.durum IS NULL)
        GROUP BY k.id
        ORDER BY yazi_sayisi DESC, toplam_goruntulenme DESC
        LIMIT 5";
$aktif_yazarlar = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="hero-section bg-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 animate-fade-in">
                    <h1 class="display-4 fw-bold mb-3">Hakkımızda</h1>
                    <p class="lead mb-4">Blog sitemiz, teknoloji, yazılım, kişisel gelişim ve daha birçok konuda özgün içerikler sunan bir platformdur.</p>
                    <div class="row g-4">
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="display-6 fw-bold mb-2"><?php echo number_format($istatistikler['yazi_sayisi']); ?></div>
                                <small class="text-white-50">Yazı</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="display-6 fw-bold mb-2"><?php echo number_format($istatistikler['yazar_sayisi']); ?></div>
                                <small class="text-white-50">Yazar</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="display-6 fw-bold mb-2"><?php echo number_format($istatistikler['yorum_sayisi']); ?></div>
                                <small class="text-white-50">Yorum</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="display-6 fw-bold mb-2"><?php echo number_format($istatistikler['kategori_sayisi']); ?></div>
                                <small class="text-white-50">Kategori</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="img/blog-hero.svg" alt="Blog İllüstrasyon" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4 animate-fade-in">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                <i class="fas fa-bullseye text-primary"></i>
                            </div>
                            <h2 class="h4 mb-0">Misyonumuz</h2>
                        </div>
                        <p class="text-muted mb-0">
                            Güncel ve kaliteli içeriklerle okuyucularımızı bilgilendirmek, teknoloji ve yazılım 
                            dünyasındaki gelişmeleri takip etmelerini sağlamak, kişisel ve profesyonel gelişimlerine 
                            katkıda bulunmaktır.
                        </p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4 animate-fade-in">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                <i class="fas fa-eye text-primary"></i>
                            </div>
                            <h2 class="h4 mb-0">Vizyonumuz</h2>
                        </div>
                        <p class="text-muted mb-0">
                            Türkiye'nin en çok tercih edilen blog platformlarından biri olmak ve global ölçekte 
                            içerik üreten bir topluluk oluşturmaktır.
                        </p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4 animate-fade-in">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                <i class="fas fa-star text-primary"></i>
                            </div>
                            <h2 class="h4 mb-0">Değerlerimiz</h2>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex p-3 rounded bg-light">
                                    <i class="fas fa-check-circle text-primary mt-1 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Özgün ve Kaliteli İçerik</h6>
                                        <small class="text-muted">Her yazımızda özgünlük ve kaliteyi gözetiyoruz</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex p-3 rounded bg-light">
                                    <i class="fas fa-graduation-cap text-primary mt-1 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Sürekli Öğrenme ve Gelişim</h6>
                                        <small class="text-muted">Kendimizi sürekli geliştiriyoruz</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex p-3 rounded bg-light">
                                    <i class="fas fa-users text-primary mt-1 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Topluluk Odaklı Yaklaşım</h6>
                                        <small class="text-muted">Okuyucularımızla birlikte büyüyoruz</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex p-3 rounded bg-light">
                                    <i class="fas fa-shield-alt text-primary mt-1 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Etik Değerlere Bağlılık</h6>
                                        <small class="text-muted">İlkeli ve etik yayıncılık anlayışı</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm animate-fade-in">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                <i class="fas fa-users text-primary"></i>
                            </div>
                            <h2 class="h4 mb-0">En Aktif Yazarlarımız</h2>
                        </div>
                        <div class="row g-4">
                            <?php while ($yazar = $aktif_yazarlar->fetch_assoc()): ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 rounded bg-light">
                                        <img src="<?php 
                                            echo !empty($yazar['profil_resmi']) 
                                                ? htmlspecialchars($yazar['profil_resmi']) 
                                                : 'https://ui-avatars.com/api/?name=' . urlencode($yazar['ad_soyad']) . '&size=48';
                                        ?>" class="rounded-circle me-3" width="48" height="48" alt="<?php echo htmlspecialchars($yazar['ad_soyad']); ?>">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="yazar.php?id=<?php echo $yazar['id']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($yazar['ad_soyad']); ?>
                                                </a>
                                            </h6>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-3">
                                                    <i class="far fa-file-alt me-1"></i><?php echo $yazar['yazi_sayisi']; ?> yazı
                                                </small>
                                                <small class="text-muted">
                                                    <i class="far fa-eye me-1"></i><?php echo number_format($yazar['toplam_goruntulenme']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4 animate-fade-in">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-chart-line me-2 text-primary"></i>Site İstatistikleri
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-file-alt fa-2x mb-2 text-primary"></i>
                                    <h3 class="mb-1"><?php echo number_format($istatistikler['yazi_sayisi']); ?></h3>
                                    <small class="text-muted">Yazı</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-users fa-2x mb-2 text-primary"></i>
                                    <h3 class="mb-1"><?php echo number_format($istatistikler['yazar_sayisi']); ?></h3>
                                    <small class="text-muted">Yazar</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-comments fa-2x mb-2 text-primary"></i>
                                    <h3 class="mb-1"><?php echo number_format($istatistikler['yorum_sayisi']); ?></h3>
                                    <small class="text-muted">Yorum</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-folder fa-2x mb-2 text-primary"></i>
                                    <h3 class="mb-1"><?php echo number_format($istatistikler['kategori_sayisi']); ?></h3>
                                    <small class="text-muted">Kategori</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm animate-fade-in">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-share-alt me-2 text-primary"></i>Sosyal Medya
                        </h5>
                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
                                <i class="fab fa-facebook me-2"></i>Facebook
                            </a>
                            <a href="#" class="btn btn-outline-info d-flex align-items-center justify-content-center">
                                <i class="fab fa-twitter me-2"></i>Twitter
                            </a>
                            <a href="#" class="btn btn-outline-danger d-flex align-items-center justify-content-center">
                                <i class="fab fa-instagram me-2"></i>Instagram
                            </a>
                            <a href="#" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
                                <i class="fab fa-linkedin me-2"></i>LinkedIn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 