<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'baglan.php';

$kategori_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kategori bilgilerini al
$sql = "SELECT * FROM kategoriler WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kategori_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$kategori = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($kategori['kategori_adi']); ?> - Blog Sitesi</title>
    <meta name="description" content="<?php echo htmlspecialchars($kategori['kategori_adi']); ?> kategorisindeki en güncel blog yazıları.">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .category-header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .post-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .post-card:hover {
            transform: translateY(-5px);
        }
        .post-image {
            height: 200px;
            object-fit: cover;
        }
        .post-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .pagination .page-link {
            color: #495057;
        }
        .pagination .page-item.active .page-link {
            background-color: #495057;
            border-color: #495057;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="category-header">
        <div class="container">
            <h1 class="display-4"><?php echo htmlspecialchars($kategori['kategori_adi']); ?></h1>
            <?php if (!empty($kategori['aciklama'])): ?>
                <p class="lead"><?php echo htmlspecialchars($kategori['aciklama']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <?php
                // Sayfalama için değişkenler
                $sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
                $limit = 9;
                $offset = ($sayfa - 1) * $limit;

                // Toplam yazı sayısını al
                $sql = "SELECT COUNT(*) as total FROM blog_yazilar WHERE kategori_id = ? AND durum = 'yayinda'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $kategori_id);
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'];
                $total_pages = ceil($total / $limit);

                // Yazıları getir
                $sql = "SELECT b.*, u.ad_soyad as yazar_adi 
                        FROM blog_yazilar b 
                        LEFT JOIN kullanicilar u ON b.yazar_id = u.id 
                        WHERE b.kategori_id = ? AND b.durum = 'yayinda' 
                        ORDER BY b.tarih DESC LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $kategori_id, $limit, $offset);
                $stmt->execute();
                $yazilar = $stmt->get_result();
                ?>

                <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                    <?php while ($yazi = $yazilar->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card post-card h-100">
                                <?php if ($yazi['resim_url']): ?>
                                    <img src="<?php echo htmlspecialchars($yazi['resim_url']); ?>" 
                                         class="card-img-top post-image" 
                                         alt="<?php echo htmlspecialchars($yazi['baslik']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($yazi['baslik']); ?></h5>
                                    <p class="card-text"><?php echo substr(strip_tags($yazi['icerik']), 0, 150) . '...'; ?></p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="post-meta">
                                            <i class="fas fa-user-edit"></i> <?php echo htmlspecialchars($yazi['yazar_adi']); ?><br>
                                            <i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?>
                                        </div>
                                        <a href="yazi.php?id=<?php echo $yazi['id']; ?>" class="btn btn-outline-primary btn-sm">Devamını Oku</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Sayfalama">
                        <ul class="pagination justify-content-center">
                            <?php if ($sayfa > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?php echo $kategori_id; ?>&sayfa=<?php echo $sayfa-1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $sayfa ? 'active' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $kategori_id; ?>&sayfa=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($sayfa < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?php echo $kategori_id; ?>&sayfa=<?php echo $sayfa+1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Kategori İstatistikleri -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Kategori İstatistikleri</h5>
                        <?php
                        // Toplam yazı sayısı
                        $sql = "SELECT COUNT(*) as yazi_sayisi FROM blog_yazilar WHERE kategori_id = ? AND durum = 'yayinda'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $kategori_id);
                        $stmt->execute();
                        $yazi_sayisi = $stmt->get_result()->fetch_assoc()['yazi_sayisi'];

                        // Son yazı tarihi
                        $sql = "SELECT MAX(tarih) as son_yazi FROM blog_yazilar WHERE kategori_id = ? AND durum = 'yayinda'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $kategori_id);
                        $stmt->execute();
                        $son_yazi = $stmt->get_result()->fetch_assoc()['son_yazi'];
                        ?>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-file-alt me-2"></i> Toplam Yazı: <?php echo $yazi_sayisi; ?></li>
                            <li><i class="fas fa-clock me-2"></i> Son Yazı: <?php echo date('d.m.Y', strtotime($son_yazi)); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Popüler Yazılar -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Bu Kategorinin En Çok Okunanları</h5>
                        <?php
                        $sql = "SELECT * FROM blog_yazilar 
                               WHERE kategori_id = ? AND durum = 'yayinda'
                               ORDER BY goruntulenme DESC LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $kategori_id);
                        $stmt->execute();
                        $populer_yazilar = $stmt->get_result();

                        if ($populer_yazilar->num_rows > 0):
                        ?>
                            <ul class="list-unstyled">
                                <?php while ($yazi = $populer_yazilar->fetch_assoc()): ?>
                                    <li class="mb-2">
                                        <a href="yazi.php?id=<?php echo $yazi['id']; ?>" class="text-decoration-none">
                                            <i class="fas fa-star text-warning me-2"></i>
                                            <?php echo htmlspecialchars($yazi['baslik']); ?>
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="card-text">Bu kategoride henüz yazı bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html> 