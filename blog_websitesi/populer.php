<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popüler Yazılar - Blog Sitesi</title>
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
                    <h1 class="display-4 fw-bold mb-3">Popüler Yazılar</h1>
                    <p class="lead mb-4">En çok okunan ve beğenilen içeriklerimizi keşfedin.</p>
                    <div class="d-flex align-items-center text-white">
                        <div class="me-4">
                            <i class="fas fa-eye fa-2x mb-2"></i>
                            <h4 class="mb-0">
                                <?php
                                    include 'baglan.php';
                                    $total_views = $conn->query("SELECT SUM(goruntulenme) as toplam FROM blog_yazilar WHERE durum = 'yayinda'")->fetch_assoc()['toplam'];
                                    echo number_format($total_views);
                                ?>
                            </h4>
                            <small>Toplam Görüntülenme</small>
                        </div>
                        <div class="me-4">
                            <i class="fas fa-comment fa-2x mb-2"></i>
                            <h4 class="mb-0">
                                <?php
                                    $total_comments = $conn->query("SELECT COUNT(*) as toplam FROM yorumlar WHERE durum = 'onaylanmis'")->fetch_assoc()['toplam'];
                                    echo number_format($total_comments);
                                ?>
                            </h4>
                            <small>Toplam Yorum</small>
                        </div>
                        <div>
                            <i class="fas fa-file-alt fa-2x mb-2"></i>
                            <h4 class="mb-0">
                                <?php
                                    $total_posts = $conn->query("SELECT COUNT(*) as toplam FROM blog_yazilar WHERE durum = 'yayinda'")->fetch_assoc()['toplam'];
                                    $total_categories = $conn->query("SELECT COUNT(*) as toplam FROM kategoriler")->fetch_assoc()['toplam'];
                                    echo number_format($total_posts);
                                ?>
                            </h4>
                            <small>Toplam Yazı</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="img/blog-hero.svg" alt="Blog İllüstrasyon" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Filtre Seçenekleri -->
                <div class="card mb-4 animate-fade-in">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Sıralama</label>
                                <select name="siralama" class="form-select">
                                    <option value="goruntulenme" <?php echo isset($_GET['siralama']) && $_GET['siralama'] == 'goruntulenme' ? 'selected' : ''; ?>>Görüntülenme</option>
                                    <option value="yorum" <?php echo isset($_GET['siralama']) && $_GET['siralama'] == 'yorum' ? 'selected' : ''; ?>>Yorum Sayısı</option>
                                    <option value="tarih" <?php echo isset($_GET['siralama']) && $_GET['siralama'] == 'tarih' ? 'selected' : ''; ?>>Tarih</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="">Tüm Kategoriler</option>
                                    <?php
                                        $kategoriler = $conn->query("SELECT * FROM kategoriler ORDER BY kategori_adi ASC");
                                        while($kategori = $kategoriler->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $kategori['id']; ?>" <?php echo isset($_GET['kategori']) && $_GET['kategori'] == $kategori['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zaman</label>
                                <select name="zaman" class="form-select">
                                    <option value="">Tüm Zamanlar</option>
                                    <option value="bugun" <?php echo isset($_GET['zaman']) && $_GET['zaman'] == 'bugun' ? 'selected' : ''; ?>>Bugün</option>
                                    <option value="hafta" <?php echo isset($_GET['zaman']) && $_GET['zaman'] == 'hafta' ? 'selected' : ''; ?>>Bu Hafta</option>
                                    <option value="ay" <?php echo isset($_GET['zaman']) && $_GET['zaman'] == 'ay' ? 'selected' : ''; ?>>Bu Ay</option>
                                    <option value="yil" <?php echo isset($_GET['zaman']) && $_GET['zaman'] == 'yil' ? 'selected' : ''; ?>>Bu Yıl</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Filtrele
                                </button>
                                <?php if(!empty($_GET)): ?>
                                    <a href="populer.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Filtreleri Temizle
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Yazılar -->
                <?php
                    // Sayfalama için değişkenler
                    $sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
                    $limit = 10;
                    $offset = ($sayfa - 1) * $limit;

                    // Filtreleme koşulları
                    $where = ["b.durum = 'yayinda'"];
                    if(isset($_GET['kategori']) && !empty($_GET['kategori'])) {
                        $where[] = "b.kategori_id = " . (int)$_GET['kategori'];
                    }
                    
                    if(isset($_GET['zaman'])) {
                        switch($_GET['zaman']) {
                            case 'bugun':
                                $where[] = "DATE(b.tarih) = CURDATE()";
                                break;
                            case 'hafta':
                                $where[] = "YEARWEEK(b.tarih) = YEARWEEK(CURDATE())";
                                break;
                            case 'ay':
                                $where[] = "YEAR(b.tarih) = YEAR(CURDATE()) AND MONTH(b.tarih) = MONTH(CURDATE())";
                                break;
                            case 'yil':
                                $where[] = "YEAR(b.tarih) = YEAR(CURDATE())";
                                break;
                        }
                    }

                    $where_clause = implode(" AND ", $where);

                    // Sıralama
                    $order_by = "b.goruntulenme DESC"; // Varsayılan sıralama
                    if(isset($_GET['siralama'])) {
                        switch($_GET['siralama']) {
                            case 'yorum':
                                $order_by = "yorum_sayisi DESC";
                                break;
                            case 'tarih':
                                $order_by = "b.tarih DESC";
                                break;
                        }
                    }

                    // Toplam yazı sayısını al
                    $total_sql = "SELECT COUNT(*) as total FROM blog_yazilar b WHERE $where_clause";
                    $total_result = $conn->query($total_sql);
                    $total_row = $total_result->fetch_assoc();
                    $total_pages = ceil($total_row['total'] / $limit);

                    // Yazıları getir
                    $sql = "SELECT b.*, k.kategori_adi, u.ad_soyad,
                            (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
                            FROM blog_yazilar b
                            LEFT JOIN kategoriler k ON b.kategori_id = k.id
                            LEFT JOIN kullanicilar u ON b.yazar_id = u.id
                            WHERE $where_clause
                            ORDER BY $order_by
                            LIMIT $offset, $limit";
                    
                    $result = $conn->query($sql);

                    if($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                ?>
                    <div class="card mb-4 popular-post-card animate-fade-in">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <img src="uploads/<?php echo !empty($row['kapak_resmi']) ? htmlspecialchars($row['kapak_resmi']) : 'default-post.jpg'; ?>"
                                     class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary me-2">
                                            <?php echo htmlspecialchars($row['kategori_adi']); ?>
                                        </span>
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('d.m.Y', strtotime($row['tarih'])); ?>
                                        </small>
                                    </div>
                                    <h4 class="card-title">
                                        <a href="yazi.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($row['baslik']); ?>
                                        </a>
                                    </h4>
                                    <p class="card-text">
                                        <?php echo mb_substr(strip_tags($row['icerik']), 0, 150) . '...'; ?>
                                    </p>
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
                            </div>
                        </div>
                    </div>
                <?php 
                        endwhile;
                        
                        // Sayfalama
                        if($total_pages > 1):
                ?>
                    <nav aria-label="Sayfalama" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $sayfa == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?sayfa=<?php echo $i; ?><?php 
                                        echo isset($_GET['siralama']) ? '&siralama=' . $_GET['siralama'] : '';
                                        echo isset($_GET['kategori']) ? '&kategori=' . $_GET['kategori'] : '';
                                        echo isset($_GET['zaman']) ? '&zaman=' . $_GET['zaman'] : '';
                                    ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php 
                        endif;
                    else:
                ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Seçilen kriterlere uygun yazı bulunamadı.
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- İstatistikler -->
                <div class="category-card animate-fade-in">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-chart-line me-2 text-primary"></i>İstatistikler
                    </h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-eye fa-2x mb-2 text-primary"></i>
                                <h3 class="mb-1"><?php echo number_format($total_views); ?></h3>
                                <small class="text-muted">Görüntülenme</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-comment fa-2x mb-2 text-primary"></i>
                                <h3 class="mb-1"><?php echo number_format($total_comments); ?></h3>
                                <small class="text-muted">Yorum</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-file-alt fa-2x mb-2 text-primary"></i>
                                <h3 class="mb-1"><?php echo number_format($total_posts); ?></h3>
                                <small class="text-muted">Yazı</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-folder fa-2x mb-2 text-primary"></i>
                                <h3 class="mb-1"><?php echo number_format($total_categories); ?></h3>
                                <small class="text-muted">Kategori</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- En Aktif Yazarlar -->
                <div class="category-card animate-fade-in">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-users me-2 text-primary"></i>En Aktif Yazarlar
                    </h5>
                    <?php
                        $sql = "SELECT u.*, COUNT(b.id) as yazi_sayisi,
                               SUM(b.goruntulenme) as toplam_goruntulenme,
                               (SELECT COUNT(*) FROM yorumlar y 
                                JOIN blog_yazilar bw ON y.yazi_id = bw.id 
                                WHERE bw.yazar_id = u.id AND y.durum = 'onaylanmis') as yorum_sayisi
                               FROM kullanicilar u
                               LEFT JOIN blog_yazilar b ON u.id = b.yazar_id AND b.durum = 'yayinda'
                               GROUP BY u.id
                               ORDER BY yazi_sayisi DESC
                               LIMIT 5";
                        $yazarlar = $conn->query($sql);
                        
                        while($yazar = $yazarlar->fetch_assoc()):
                    ?>
                        <div class="d-flex align-items-center mb-3 p-3 bg-light rounded">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($yazar['ad_soyad']); ?>&size=48" 
                                 class="rounded-circle me-3" width="48" height="48" alt="">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($yazar['ad_soyad']); ?></h6>
                                <div class="d-flex text-muted">
                                    <small class="me-3">
                                        <i class="far fa-file-alt me-1"></i><?php echo $yazar['yazi_sayisi']; ?> Yazı
                                    </small>
                                    <small class="me-3">
                                        <i class="far fa-eye me-1"></i><?php echo number_format($yazar['toplam_goruntulenme']); ?>
                                    </small>
                                    <small>
                                        <i class="far fa-comment me-1"></i><?php echo $yazar['yorum_sayisi']; ?>
                                    </small>
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