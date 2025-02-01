<?php
require_once 'baglan.php';

// XSS koruma fonksiyonu
function guvenli_cikti($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Arama parametresi
$arama = isset($_GET['arama']) ? trim($_GET['arama']) : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Blog yazıları - En güncel ve ilgi çekici içerikler">
    <meta name="keywords" content="blog, yazılar, içerik, makale">
    <meta name="author" content="Blog Sitesi">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0d6efd">
    <title>Blog Yazıları - Blog Sitesi</title>
    <link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        .blog-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .category-item {
            transition: all 0.3s ease;
            border-radius: 10px;
            background: #fff;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .category-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            position: relative;
            overflow: hidden;
            padding: 4rem 0;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('uploads/blog-hero.svg') no-repeat center right;
            background-size: contain;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .skeleton-loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="hero-section bg-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center hero-content">
                    <h1 class="display-4 fw-bold mb-4" data-aos="fade-up">Blog Yazıları</h1>
                    <p class="lead mb-0" data-aos="fade-up" data-aos-delay="100">
                        En güncel ve ilgi çekici içeriklerimizi keşfedin.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div id="blogPosts" class="row g-4">
                    <!-- Blog yazıları buraya gelecek -->
                </div>
                <div id="loadingSpinner" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
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
                            $kategori_sql = "SELECT k.*, COUNT(DISTINCT b.id) as yazi_sayisi,
                                           COALESCE(SUM(b.goruntulenme), 0) as toplam_goruntulenme
                                           FROM kategoriler k 
                                           LEFT JOIN blog_yazilar b ON k.id = b.kategori_id AND b.durum = 'yayinda'
                                           GROUP BY k.id
                                           ORDER BY yazi_sayisi DESC";
                            $stmt = $conn->prepare($kategori_sql);
                            $stmt->execute();
                            $kategoriler = $stmt->get_result();
                            
                            while ($kategori = $kategoriler->fetch_assoc()):
                                $icon = 'folder';
                                // Kategori adına göre özel ikonlar
                                switch(strtolower($kategori['kategori_adi'])) {
                                    case 'teknoloji':
                                        $icon = 'fa-solid fa-microchip fa-beat';
                                        break;
                                    case 'yazılım':
                                        $icon = 'fa-solid fa-code fa-beat';
                                        break;
                                    case 'tasarım':
                                        $icon = 'fa-solid fa-palette fa-beat';
                                        break;
                                    case 'pazarlama':
                                        $icon = 'fa-solid fa-bullhorn fa-beat';
                                        break;
                                    case 'eğitim':
                                        $icon = 'fa-solid fa-graduation-cap fa-beat';
                                        break;
                                    case 'sağlık':
                                        $icon = 'fa-solid fa-heart-pulse fa-beat';
                                        break;
                                    case 'spor':
                                        $icon = 'fa-solid fa-person-running fa-beat';
                                        break;
                                    case 'müzik':
                                        $icon = 'fa-solid fa-music fa-beat';
                                        break;
                                    case 'seyahat':
                                        $icon = 'fa-solid fa-plane-departure fa-beat';
                                        break;
                                    case 'yemek':
                                        $icon = 'fa-solid fa-utensils fa-beat';
                                        break;
                                    case 'oyun':
                                        $icon = 'fa-solid fa-gamepad fa-beat';
                                        break;
                                    case 'bilim':
                                        $icon = 'fa-solid fa-flask fa-beat';
                                        break;
                                    case 'sanat':
                                        $icon = 'fa-solid fa-brush fa-beat';
                                        break;
                                    case 'kitap':
                                        $icon = 'fa-solid fa-book fa-beat';
                                        break;
                                    case 'sinema':
                                        $icon = 'fa-solid fa-film fa-beat';
                                        break;
                                    case 'fotoğraf':
                                        $icon = 'fa-solid fa-camera fa-beat';
                                        break;
                                    case 'doğa':
                                        $icon = 'fa-solid fa-leaf fa-beat';
                                        break;
                                    case 'iş dünyası':
                                        $icon = 'fa-solid fa-briefcase fa-beat';
                                        break;
                                    case 'ekonomi':
                                        $icon = 'fa-solid fa-chart-line fa-beat';
                                        break;
                                    case 'otomobil':
                                        $icon = 'fa-solid fa-car fa-beat';
                                        break;
                                    default:
                                        $icon = 'fa-solid fa-folder fa-beat';
                                        break;
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
                                    <img src="<?php echo !empty($yazi['kapak_resmi']) ? 'uploads/' . htmlspecialchars($yazi['kapak_resmi']) : 'uploads/default.svg'; ?>"
                                         class="img-fluid rounded" style="width: 100%; height: 100px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($yazi['baslik']); ?>"
                                         onerror="this.src='uploads/default.svg'">
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
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        // Sonsuz scroll için değişkenler
        let page = 1;
        let loading = false;
        let hasMore = true;

        // Sayfa yüklendiğinde ilk içerikleri getir
        document.addEventListener('DOMContentLoaded', () => {
            loadPosts();
        });

        // Scroll eventi
        window.addEventListener('scroll', () => {
            if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 1000) {
                if (!loading && hasMore) {
                    loadPosts();
                }
            }
        });

        // Blog yazılarını yükle
        function loadPosts() {
            if (loading) return;
            loading = true;
            
            document.getElementById('loadingSpinner').classList.remove('d-none');
            
            const searchParams = new URLSearchParams(window.location.search);
            const arama = searchParams.get('arama') || '';
            
            fetch(`get_posts.php?page=${page}&arama=${encodeURIComponent(arama)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.posts.length === 0) {
                        hasMore = false;
                        if (page === 1) {
                            document.getElementById('blogPosts').innerHTML = `
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Henüz blog yazısı bulunmuyor.
                                    </div>
                                </div>
                            `;
                        }
                        document.getElementById('loadingSpinner').classList.add('d-none');
                        return;
                    }
                    
                    const container = document.getElementById('blogPosts');
                    data.posts.forEach(post => {
                        container.insertAdjacentHTML('beforeend', createPostCard(post));
                    });
                    
                    page++;
                    loading = false;
                    document.getElementById('loadingSpinner').classList.add('d-none');
                    
                    // AOS'u yeni eklenen elementler için yeniden başlat
                    AOS.refresh();
                })
                .catch(error => {
                    console.error('Yazılar yüklenirken hata oluştu:', error);
                    document.getElementById('loadingSpinner').classList.add('d-none');
                    loading = false;
                });
        }

        // Blog kartı oluştur
        function createPostCard(post) {
            return `
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <article class="blog-card card h-100">
                        <img src="uploads/${post.kapak_resmi || 'default.svg'}"
                             class="card-img-top" 
                             alt="${post.baslik}"
                             loading="lazy"
                             onerror="this.src='uploads/default.svg'">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary me-2">${post.kategori_adi}</span>
                                <small class="text-muted">${post.tarih}</small>
                            </div>
                            <h3 class="card-title h5">
                                <a href="yazi.php?id=${post.id}" class="text-decoration-none text-dark">
                                    ${post.baslik}
                                </a>
                            </h3>
                            <p class="card-text">${post.ozet}</p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(post.ad_soyad)}&size=32" 
                                         class="rounded-circle me-2" 
                                         width="32" 
                                         height="32" 
                                         alt="${post.ad_soyad}"
                                         loading="lazy">
                                    <small class="text-muted">${post.ad_soyad}</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <small class="text-muted me-3">
                                        <i class="far fa-eye me-1"></i>${post.goruntulenme}
                                    </small>
                                    <small class="text-muted">
                                        <i class="far fa-comment me-1"></i>${post.yorum_sayisi}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            `;
        }
    </script>
</body>
</html> 