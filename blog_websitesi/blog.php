<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Yazıları - Blog Sitesi</title>
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
                <div class="col-md-6 animate-fade-in">
                    <h1 class="display-4 fw-bold mb-3">Blog Yazıları</h1>
                    <p class="lead mb-4">En güncel ve ilgi çekici içeriklerimizi keşfedin.</p>
                    <form action="" method="GET" class="d-flex gap-2">
                        <input type="text" name="arama" class="form-control form-control-lg" placeholder="Blog yazılarında ara..." value="<?php echo isset($_GET['arama']) ? htmlspecialchars($_GET['arama']) : ''; ?>">
                        <button type="submit" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6 d-none d-md-block">
                    <img src="img/blog-hero.svg" alt="Blog İllüstrasyon" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <?php
                    include 'baglan.php';
                    
                    // Sayfalama için değişkenler
                    $sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
                    $limit = 9;
                    $offset = ($sayfa - 1) * $limit;

                    // Arama sorgusu
                    $arama = isset($_GET['arama']) ? $conn->real_escape_string($_GET['arama']) : '';
                    $where = "b.durum = 'yayinda'";
                    if ($arama) {
                        $where .= " AND (b.baslik LIKE '%$arama%' OR b.icerik LIKE '%$arama%')";
                    }

                    // Toplam yazı sayısını al
                    $total_sql = "SELECT COUNT(*) as total FROM blog_yazilar b WHERE $where";
                    $total_result = $conn->query($total_sql);
                    $total_row = $total_result->fetch_assoc();
                    $total_pages = ceil($total_row['total'] / $limit);

                    // Blog yazılarını getir
                    $sql = "SELECT b.*, k.kategori_adi, u.ad_soyad, 
                            (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
                            FROM blog_yazilar b
                            LEFT JOIN kategoriler k ON b.kategori_id = k.id
                            LEFT JOIN kullanicilar u ON b.yazar_id = u.id
                            WHERE $where
                            ORDER BY b.tarih DESC LIMIT $offset, $limit";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0):
                ?>
                    <div class="row g-4">
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 animate-fade-in">
                                <article class="blog-card card h-100">
                                    <img src="uploads/<?php echo !empty($row['kapak_resmi']) ? htmlspecialchars($row['kapak_resmi']) : 'default-post.jpg'; ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($row['baslik']); ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($row['kategori_adi']); ?></span>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y', strtotime($row['tarih'])); ?>
                                            </small>
                                        </div>
                                        <h3 class="card-title">
                                            <a href="yazi.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($row['baslik']); ?>
                                            </a>
                                        </h3>
                                        <p class="card-text">
                                            <?php echo mb_substr(strip_tags($row['icerik']), 0, 150) . '...'; ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white border-top-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['ad_soyad']); ?>&size=32" 
                                                     class="rounded-circle me-2" width="32" height="32" alt="">
                                                <small class="text-muted"><?php echo htmlspecialchars($row['ad_soyad']); ?></small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-3">
                                                    <i class="far fa-eye me-1"></i><?php echo number_format($row['goruntulenme']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="far fa-comment me-1"></i><?php echo $row['yorum_sayisi']; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Sayfalama" class="mt-5">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $sayfa == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?sayfa=<?php echo $i; ?><?php echo $arama ? '&arama=' . urlencode($arama) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-info">
                        <?php if ($arama): ?>
                            <i class="fas fa-info-circle me-2"></i>Arama sonucunda yazı bulunamadı.
                            <a href="blog.php" class="alert-link">Tüm yazıları görüntüle</a>
                        <?php else: ?>
                            <i class="fas fa-info-circle me-2"></i>Henüz blog yazısı bulunmuyor.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Kategoriler -->
                <div class="category-card animate-fade-in">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-folder-open me-2 text-primary"></i>Kategoriler
                        </h5>
                        <span class="badge bg-primary rounded-pill">
                            <?php
                                $total_categories = $conn->query("SELECT COUNT(*) as total FROM kategoriler")->fetch_assoc()['total'];
                                echo $total_categories;
                            ?> Kategori
                        </span>
                    </div>
                    <div class="category-grid">
                        <?php
                            $sql = "SELECT k.*, COUNT(b.id) as yazi_sayisi,
                                   COALESCE(SUM(b.goruntulenme), 0) as toplam_goruntulenme
                                   FROM kategoriler k 
                                   LEFT JOIN blog_yazilar b ON k.id = b.kategori_id AND b.durum = 'yayinda'
                                   GROUP BY k.id
                                   ORDER BY yazi_sayisi DESC";
                            $kategoriler = $conn->query($sql);
                            
                            while ($kategori = $kategoriler->fetch_assoc()):
                                $icon = 'folder';
                                switch(strtolower($kategori['kategori_adi'])) {
                                    case 'teknoloji': $icon = 'laptop-code'; break;
                                    case 'seyahat': $icon = 'plane'; break;
                                    case 'spor': $icon = 'futbol'; break;
                                    case 'yaşam': $icon = 'heart'; break;
                                    case 'bilim': $icon = 'flask'; break;
                                    case 'sanat': $icon = 'palette'; break;
                                    case 'müzik': $icon = 'music'; break;
                                    case 'sinema': $icon = 'film'; break;
                                }
                        ?>
                            <a href="kategori.php?id=<?php echo $kategori['id']; ?>" class="category-item">
                                <div class="category-icon">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div class="category-info">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($kategori['kategori_adi']); ?></h6>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-3">
                                            <i class="far fa-file-alt me-1"></i><?php echo $kategori['yazi_sayisi']; ?> Yazı
                                        </small>
                                        <small class="text-muted">
                                            <i class="far fa-eye me-1"></i><?php echo number_format($kategori['toplam_goruntulenme']); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="category-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Popüler Yazılar -->
                <div class="category-card animate-fade-in">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-fire me-2 text-primary"></i>Popüler Yazılar
                        </h5>
                        <a href="populer.php" class="btn btn-sm btn-outline-primary rounded-pill">
                            Tümünü Gör
                        </a>
                    </div>
                    <?php
                        $sql = "SELECT b.*, k.kategori_adi, u.ad_soyad,
                               (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
                               FROM blog_yazilar b
                               LEFT JOIN kategoriler k ON b.kategori_id = k.id
                               LEFT JOIN kullanicilar u ON b.yazar_id = u.id
                               WHERE b.durum = 'yayinda'
                               ORDER BY b.goruntulenme DESC
                               LIMIT 5";
                        $populer_yazilar = $conn->query($sql);
                        
                        while ($yazi = $populer_yazilar->fetch_assoc()):
                    ?>
                        <div class="popular-post-card mb-3">
                            <div class="row g-0">
                                <div class="col-4">
                                    <img src="uploads/<?php echo !empty($yazi['kapak_resmi']) ? htmlspecialchars($yazi['kapak_resmi']) : 'default-post.jpg'; ?>"
                                         class="img-fluid rounded" style="width: 100%; height: 100px; object-fit: cover;" alt="">
                                </div>
                                <div class="col-8">
                                    <div class="card-body p-2 ps-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($yazi['kategori_adi']); ?></span>
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i><?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?>
                                            </small>
                                        </div>
                                        <h6 class="card-title mb-2">
                                            <a href="yazi.php?id=<?php echo $yazi['id']; ?>" class="text-decoration-none text-dark stretched-link">
                                                <?php echo htmlspecialchars($yazi['baslik']); ?>
                                            </a>
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted me-3">
                                                <i class="far fa-eye me-1"></i><?php echo number_format($yazi['goruntulenme']); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="far fa-comment me-1"></i><?php echo $yazi['yorum_sayisi']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html> 