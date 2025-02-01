<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'baglan.php';

$yazar_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Yazar bilgilerini al
$sql = "SELECT * FROM kullanicilar WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $yazar_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$yazar = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($yazar['ad_soyad']); ?> - Blog Sitesi</title>
    <meta name="description" content="<?php echo htmlspecialchars($yazar['ad_soyad']); ?>'in blog yazıları ve profili.">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --accent-color: #818cf8;
            --background-color: #f8fafc;
            --text-color: #0f172a;
            --light-text: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --hover-color: #6366f1;
            --gradient-start: #4f46e5;
            --gradient-end: #7c3aed;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        .author-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .author-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.1)" x="0" y="0" width="100" height="100"/></svg>') repeat;
            opacity: 0.1;
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            from { background-position: 0 0; }
            to { background-position: 100px 100px; }
        }

        .author-avatar {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 8px solid rgba(255, 255, 255, 0.2);
            object-fit: cover;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            position: relative;
        }

        .author-avatar::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: pulseRing 2s infinite;
        }

        @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.05); opacity: 0.1; }
            100% { transform: scale(1); opacity: 0.3; }
        }

        .author-avatar:hover {
            transform: scale(1.05) rotate(5deg);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .post-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            background: var(--card-bg);
            position: relative;
        }

        .post-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.02));
            z-index: 1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .post-card:hover::before {
            opacity: 1;
        }

        .post-image {
            height: 250px;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .post-card:hover .post-image {
            transform: scale(1.1);
        }

        .stats-card {
            text-align: center;
            padding: 2rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            background: var(--card-bg);
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.8), transparent);
            transform: rotate(45deg);
            transition: all 0.6s;
            opacity: 0;
        }

        .stats-card:hover::before {
            animation: shine 1.5s;
        }

        @keyframes shine {
            0% { left: -50%; opacity: 1; }
            100% { left: 100%; opacity: 0; }
        }

        .stats-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .stats-card i {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .stats-card:hover i {
            transform: translateY(-5px);
        }

        .stats-card .number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-color);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-color), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-card .label {
            color: var(--light-text);
            font-size: 1.2rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .social-links a {
            font-size: 1.8rem;
            margin-right: 2rem;
            transition: all 0.3s ease;
            opacity: 0.9;
            position: relative;
        }

        .social-links a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: white;
            bottom: -5px;
            left: 0;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .social-links a:hover::after {
            transform: scaleX(1);
        }

        .social-links a:hover {
            transform: translateY(-5px);
            opacity: 1;
        }

        .badge {
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            border-radius: 2rem;
            letter-spacing: 0.03em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end)) !important;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 2rem;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-outline-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            transition: width 0.3s ease;
            z-index: -1;
        }

        .btn-outline-primary:hover::before {
            width: 100%;
        }

        .btn-outline-primary:hover {
            color: white;
            border-color: transparent;
            background: transparent;
        }

        .pagination {
            gap: 0.5rem;
        }

        .pagination .page-link {
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            color: var(--text-color);
            background: var(--card-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .pagination .page-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            background: var(--background-color);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
        }

        .card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            color: var(--text-color);
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 3px;
        }

        .list-unstyled li {
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .list-unstyled li:hover {
            padding-left: 1rem;
            background: var(--background-color);
            border-radius: 0.5rem;
        }

        .list-unstyled li:last-child {
            border-bottom: none;
        }

        .list-unstyled a {
            color: var(--text-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .list-unstyled a:hover {
            color: var(--primary-color);
        }

        .list-unstyled a i {
            transition: transform 0.3s ease;
        }

        .list-unstyled a:hover i {
            transform: translateX(5px);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="author-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?php 
                        echo !empty($yazar['profil_resmi']) 
                            ? htmlspecialchars($yazar['profil_resmi']) 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($yazar['ad_soyad']) . '&size=150';
                    ?>" alt="<?php echo htmlspecialchars($yazar['ad_soyad']); ?>" class="author-avatar mb-3">
                </div>
                <div class="col-md-9">
                    <h1 class="display-4"><?php echo htmlspecialchars($yazar['ad_soyad']); ?></h1>
                    <?php if (!empty($yazar['hakkinda'])): ?>
                        <p class="lead"><?php echo nl2br(htmlspecialchars($yazar['hakkinda'])); ?></p>
                    <?php endif; ?>
                    <div class="social-links mt-4">
                        <?php if (!empty($yazar['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($yazar['twitter']); ?>" class="text-white me-3" target="_blank">
                                <i class="fab fa-twitter fa-lg"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($yazar['linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($yazar['linkedin']); ?>" class="text-white me-3" target="_blank">
                                <i class="fab fa-linkedin fa-lg"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($yazar['website'])): ?>
                            <a href="<?php echo htmlspecialchars($yazar['website']); ?>" class="text-white" target="_blank">
                                <i class="fas fa-globe fa-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Yazıları</h2>
                <?php
                // Sayfalama için değişkenler
                $sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
                $limit = 6;
                $offset = ($sayfa - 1) * $limit;

                // Toplam yazı sayısını al
                $sql = "SELECT COUNT(*) as total FROM blog_yazilar WHERE yazar_id = ? AND durum = 'yayinda'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $yazar_id);
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'];
                $total_pages = ceil($total / $limit);

                // Yazıları getir
                $sql = "SELECT b.*, k.kategori_adi 
                        FROM blog_yazilar b 
                        LEFT JOIN kategoriler k ON b.kategori_id = k.id 
                        WHERE b.yazar_id = ? AND b.durum = 'yayinda' 
                        ORDER BY b.tarih DESC LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $yazar_id, $limit, $offset);
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
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($yazi['kategori_adi']); ?></span>
                                            <small class="text-muted ms-3">
                                                <i class="fas fa-calendar-alt me-1"></i> 
                                                <?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?>
                                            </small>
                                            <small class="text-muted ms-3">
                                                <i class="fas fa-eye me-1"></i> 
                                                <?php echo number_format($yazi['goruntulenme']); ?>
                                            </small>
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
                                    <a class="page-link" href="?id=<?php echo $yazar_id; ?>&sayfa=<?php echo $sayfa-1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $sayfa ? 'active' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $yazar_id; ?>&sayfa=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($sayfa < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?php echo $yazar_id; ?>&sayfa=<?php echo $sayfa+1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Yazar İstatistikleri -->
                <div class="mb-4">
                    <?php
                    // Toplam yazı sayısı
                    $sql = "SELECT COUNT(*) as yazi_sayisi FROM blog_yazilar WHERE yazar_id = ? AND durum = 'yayinda'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $yazar_id);
                    $stmt->execute();
                    $yazi_sayisi = $stmt->get_result()->fetch_assoc()['yazi_sayisi'];

                    // Toplam görüntülenme
                    $sql = "SELECT SUM(goruntulenme) as toplam_goruntulenme FROM blog_yazilar WHERE yazar_id = ? AND durum = 'yayinda'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $yazar_id);
                    $stmt->execute();
                    $toplam_goruntulenme = $stmt->get_result()->fetch_assoc()['toplam_goruntulenme'] ?: 0;

                    // Toplam yorum sayısı
                    $sql = "SELECT COUNT(*) as yorum_sayisi FROM yorumlar y 
                           INNER JOIN blog_yazilar b ON y.yazi_id = b.id 
                           WHERE b.yazar_id = ? AND y.durum = 'onaylanmis'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $yazar_id);
                    $stmt->execute();
                    $yorum_sayisi = $stmt->get_result()->fetch_assoc()['yorum_sayisi'];
                    ?>

                    <div class="stats-card">
                        <i class="fas fa-file-alt"></i>
                        <div class="number"><?php echo $yazi_sayisi; ?></div>
                        <div class="label">Yazı</div>
                    </div>

                    <div class="stats-card">
                        <i class="fas fa-eye"></i>
                        <div class="number"><?php echo number_format($toplam_goruntulenme); ?></div>
                        <div class="label">Görüntülenme</div>
                    </div>

                    <div class="stats-card">
                        <i class="fas fa-comments"></i>
                        <div class="number"><?php echo $yorum_sayisi; ?></div>
                        <div class="label">Yorum</div>
                    </div>
                </div>

                <!-- En Popüler Yazıları -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">En Popüler Yazıları</h5>
                        <?php
                        $sql = "SELECT * FROM blog_yazilar 
                               WHERE yazar_id = ? AND durum = 'yayinda'
                               ORDER BY goruntulenme DESC LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $yazar_id);
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
                                            <small class="text-muted d-block ms-4">
                                                <?php echo number_format($yazi['goruntulenme']); ?> görüntülenme
                                            </small>
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="card-text">Henüz yazı bulunmamaktadır.</p>
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
