<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'baglan.php';

// Başarı mesajı kontrolü
if (isset($_GET['mesaj']) && $_GET['mesaj'] === 'hesap_silindi') {
    $_SESSION['basari'] = "Hesabınız başarıyla silindi.";
}

// Öne çıkan yazıları getir (veya son yazılardan 3 tanesini göster)
$sql = "SELECT b.*, k.kategori_adi, u.ad_soyad, u.kullanici_adi,
        (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
        FROM blog_yazilar b
        LEFT JOIN kategoriler k ON b.kategori_id = k.id
        LEFT JOIN kullanicilar u ON b.yazar_id = u.id
        WHERE b.durum = 'yayinda'
        ORDER BY b.tarih DESC
        LIMIT 3";
$one_cikan_yazilar = $conn->query($sql);

// Son yazıları getir
$sql = "SELECT b.*, k.kategori_adi, u.ad_soyad, u.kullanici_adi,
        (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
        FROM blog_yazilar b
        LEFT JOIN kategoriler k ON b.kategori_id = k.id
        LEFT JOIN kullanicilar u ON b.yazar_id = u.id
        WHERE b.durum = 'yayinda'
        ORDER BY b.tarih DESC
        LIMIT 6";
$son_yazilar = $conn->query($sql);

// Popüler yazıları getir
$sql = "SELECT b.*, k.kategori_adi, u.ad_soyad, u.kullanici_adi,
        (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
        FROM blog_yazilar b
        LEFT JOIN kategoriler k ON b.kategori_id = k.id
        LEFT JOIN kullanicilar u ON b.yazar_id = u.id
        WHERE b.durum = 'yayinda'
        ORDER BY b.goruntulenme DESC
        LIMIT 4";
$populer_yazilar = $conn->query($sql);

// Kategorileri getir
$sql = "SELECT k.*, 
        (SELECT COUNT(*) FROM blog_yazilar WHERE kategori_id = k.id AND durum = 'yayinda') as yazi_sayisi
        FROM kategoriler k
        ORDER BY k.kategori_adi ASC";
$kategoriler = $conn->query($sql);

if (!$kategoriler) {
    die("Kategori sorgusu hatası: " . $conn->error);
}

// En aktif yazarları getir
$sql = "SELECT u.*, COUNT(b.id) as yazi_sayisi
        FROM kullanicilar u
        LEFT JOIN blog_yazilar b ON u.id = b.yazar_id AND b.durum = 'yayinda'
        WHERE u.durum = 1
        GROUP BY u.id
        ORDER BY yazi_sayisi DESC
        LIMIT 5";
$aktif_yazarlar = $conn->query($sql);

// Başarı ve hata mesajlarını göster
if (isset($_SESSION['basari'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . $_SESSION['basari'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['basari']);
}

if (isset($_SESSION['hata'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['hata'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['hata']);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="En güncel blog yazıları ve içerikler. Teknoloji, yaşam, seyahat ve daha fazlası.">
    <meta name="keywords" content="blog, teknoloji, yaşam, seyahat, içerik">
    <meta name="author" content="Blog Sitesi">
    <title>Blog Sitesi - Ana Sayfa</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366F1;
            --primary-dark: #4F46E5;
            --primary-light: #818CF8;
            --accent-color: #8B5CF6;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: var(--gray-dark);
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 6rem 0;
            margin-bottom: 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff10" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom center no-repeat;
            background-size: cover;
            opacity: 0.1;
        }

        .hero-title {
            color: var(--white);
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.25rem;
            font-weight: 500;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-container {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
        }

        .search-box {
            background: var(--white);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .search-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .search-input-wrapper {
            position: relative;
            flex: 2;
        }

        .search-filters {
            display: flex;
            gap: 1rem;
            flex: 3;
        }

        .filter-item {
            position: relative;
            flex: 1;
        }

        .search-icon {
            position: absolute;
            left: 1 rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.25rem;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 4rem;
            border: 2px solid var(--gray-200);
            border-radius: 1rem;
            font-size: 1.1rem;
            color: var(--gray-800);
            transition: all 0.3s ease;
            height: 100%;
        }

        .search-input::placeholder {
            color: var(--gray-400);
            opacity: 1;
        }

        .search-input:focus::placeholder {
            opacity: 0.7;
        }

        .filter-select {
            width: 100%;
            padding: 1rem 2.5rem;
            border: 2px solid var(--gray-200);
            border-radius: 1rem;
            font-size: 1rem;
            color: var(--gray-700);
            appearance: none;
            background: var(--white);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .filter-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            z-index: 1;
            pointer-events: none;
        }

        .search-button {
            width: 100%;
            padding: 1.25rem;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .search-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .search-button i {
            transition: transform 0.3s ease;
        }

        .search-button:hover i {
            transform: translateX(4px);
        }

        @media (max-width: 992px) {
            .search-row {
                flex-direction: column;
            }

            .search-filters {
                flex-direction: column;
            }

            .filter-item {
                width: 100%;
            }

            .search-input-wrapper {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 4rem 0;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .search-box {
                padding: 1.5rem;
            }

            .search-button {
                padding: 1rem;
            }
        }

        .featured-posts {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .post-card {
            background: var(--white);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .post-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .post-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-dark);
            text-decoration: none;
        }

        .post-title:hover {
            color: var(--primary-color);
        }

        .post-excerpt {
            color: var(--gray-medium);
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--gray-medium);
        }

        .post-meta i {
            color: var(--primary-color);
        }

        .category-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .category-badge:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .search-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
        }

        .search-input {
            flex-grow: 1;
            border: 2px solid var(--gray-light);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.1);
        }

        .search-button {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .category-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }

        .category-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .popular-posts {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .popular-post-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .popular-post-item:last-child {
            border-bottom: none;
        }

        .popular-post-image {
            width: 80px;
            height: 80px;
            border-radius: 0.5rem;
            object-fit: cover;
        }

        .popular-post-content {
            flex-grow: 1;
        }

        .popular-post-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--gray-dark);
            text-decoration: none;
        }

        .popular-post-title:hover {
            color: var(--primary-color);
        }

        .popular-post-meta {
            font-size: 0.875rem;
            color: var(--gray-medium);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .page-link {
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: var(--gray-dark);
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: var(--gray-light);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            color: var(--white);
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 3rem 0;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .post-card {
                margin-bottom: 1.5rem;
            }
        }

        .search-wrapper {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            border: 2px solid var(--gray-light);
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .search-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .search-filters .form-select {
            border: 2px solid var(--gray-light);
            border-radius: 0.75rem;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            font-size: 0.9rem;
            min-width: 150px;
            background-position: right 1rem center;
            transition: all 0.3s ease;
        }

        .search-filters .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .search-button {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .search-button:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border-radius: 1rem;
            margin-top: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }

        .search-result-item {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: var(--gray-light);
        }

        .search-result-image {
            width: 60px;
            height: 60px;
            border-radius: 0.5rem;
            object-fit: cover;
        }

        .search-result-content {
            flex: 1;
        }

        .search-result-title {
            font-weight: 600;
            color: var(--gray-dark);
            text-decoration: none;
            margin-bottom: 0.25rem;
            display: block;
        }

        .search-result-info {
            font-size: 0.875rem;
            color: var(--gray-medium);
        }

        @media (max-width: 768px) {
            .search-wrapper {
                flex-direction: column;
                padding: 0.75rem;
            }

            .search-filters {
                width: 100%;
            }

            .search-filters .form-select {
                flex: 1;
            }

            .search-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title mb-4">Blog Dünyasına Hoş Geldiniz</h1>
                <p class="hero-subtitle mb-5">En güncel yazıları keşfedin, düşüncelerinizi paylaşın.</p>
                
                <form action="arama.php" method="GET" class="search-form">
                    <div class="search-container">
                        <div class="search-box">
                            <div class="search-row">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" 
                                           name="q" 
                                           class="search-input" 
                                           placeholder="Blog yazılarında ara..." 
                                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" 
                                           autocomplete="off" 
                                           id="searchInput">
                                </div>
                                
                                <div class="search-filters">
                                    <div class="filter-item">
                                        <i class="fas fa-folder-open filter-icon"></i>
                                        <select name="kategori" class="filter-select">
                                            <option value="">Tüm Kategoriler</option>
                                            <?php
                                            $sql = "SELECT * FROM kategoriler ORDER BY kategori_adi ASC";
                                            $kategoriler = $conn->query($sql);
                                            while ($kategori = $kategoriler->fetch_assoc()) {
                                                echo '<option value="' . $kategori['id'] . '">' . 
                                                     htmlspecialchars($kategori['kategori_adi']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="filter-item">
                                        <i class="fas fa-sort-amount-down filter-icon"></i>
                                        <select name="sirala" class="filter-select">
                                            <option value="tarih_yeni">En Yeni</option>
                                            <option value="tarih_eski">En Eski</option>
                                            <option value="populer">En Popüler</option>
                                            <option value="yorumlar">En Çok Yorumlanan</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="search-button">
                                <span>Ara</span>
                                <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                        
                        <div id="searchResults" class="search-results"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Öne Çıkan Yazılar -->
                <section class="featured-posts">
                    <h2 class="section-title">Öne Çıkan Yazılar</h2>
                    <div class="row">
                        <?php
                        $sql = "SELECT b.*, k.kategori_adi, u.ad_soyad as yazar_adi 
                                FROM blog_yazilar b 
                                LEFT JOIN kategoriler k ON b.kategori_id = k.id 
                                LEFT JOIN kullanicilar u ON b.yazar_id = u.id 
                                WHERE b.durum = 'yayinda' 
                                ORDER BY b.goruntulenme DESC 
                                LIMIT 6";
                        $featured_posts = $conn->query($sql);
                        while ($post = $featured_posts->fetch_assoc()):
                        ?>
                        <div class="col-md-6 mb-4">
                            <article class="post-card">
                                <?php if($post['resim_url']): ?>
                                <img src="<?php echo htmlspecialchars($post['resim_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['baslik']); ?>" 
                                     class="post-image">
                                <?php endif; ?>
                                <div class="post-content">
                                    <a href="yazi.php?id=<?php echo $post['id']; ?>" class="post-title">
                                        <?php echo htmlspecialchars($post['baslik']); ?>
                                    </a>
                                    <p class="post-excerpt">
                                        <?php echo substr(strip_tags($post['icerik']), 0, 150) . '...'; ?>
                                    </p>
                                    <div class="post-meta">
                                        <span>
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($post['yazar_adi']); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d.m.Y', strtotime($post['tarih'])); ?>
                                        </span>
                                        <a href="kategori.php?id=<?php echo $post['kategori_id']; ?>" 
                                           class="category-badge">
                                            <?php echo htmlspecialchars($post['kategori_adi']); ?>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </section>

                <!-- Son Yazılar -->
                <section class="latest-posts">
                    <h2 class="section-title">Son Yazılar</h2>
                    <div class="row">
                        <?php
                        $sql = "SELECT b.*, k.kategori_adi, u.ad_soyad as yazar_adi 
                                FROM blog_yazilar b 
                                LEFT JOIN kategoriler k ON b.kategori_id = k.id 
                                LEFT JOIN kullanicilar u ON b.yazar_id = u.id 
                                WHERE b.durum = 'yayinda' 
                                ORDER BY b.tarih DESC 
                                LIMIT 6";
                        $latest_posts = $conn->query($sql);
                        while ($post = $latest_posts->fetch_assoc()):
                        ?>
                        <div class="col-md-6 mb-4">
                            <article class="post-card">
                                <?php if($post['resim_url']): ?>
                                <img src="<?php echo htmlspecialchars($post['resim_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['baslik']); ?>" 
                                     class="post-image">
                                <?php endif; ?>
                                <div class="post-content">
                                    <a href="yazi.php?id=<?php echo $post['id']; ?>" class="post-title">
                                        <?php echo htmlspecialchars($post['baslik']); ?>
                                    </a>
                                    <p class="post-excerpt">
                                        <?php echo substr(strip_tags($post['icerik']), 0, 150) . '...'; ?>
                                    </p>
                                    <div class="post-meta">
                                        <span>
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($post['yazar_adi']); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d.m.Y', strtotime($post['tarih'])); ?>
                                        </span>
                                        <a href="kategori.php?id=<?php echo $post['kategori_id']; ?>" 
                                           class="category-badge">
                                            <?php echo htmlspecialchars($post['kategori_adi']); ?>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </section>
            </div>

            <div class="col-lg-4">
                <!-- Kategoriler -->
                <div class="category-section mb-4">
                    <h3 class="h5 mb-3">Kategoriler</h3>
                    <div class="category-list">
                        <?php
                        $sql = "SELECT k.*, COUNT(b.id) as yazi_sayisi 
                                FROM kategoriler k 
                                LEFT JOIN blog_yazilar b ON k.id = b.kategori_id 
                                GROUP BY k.id 
                                ORDER BY yazi_sayisi DESC";
                        $kategoriler = $conn->query($sql);
                        while ($kategori = $kategoriler->fetch_assoc()):
                        ?>
                        <a href="kategori.php?id=<?php echo $kategori['id']; ?>" class="category-badge">
                            <?php echo htmlspecialchars($kategori['kategori_adi']); ?> 
                            (<?php echo $kategori['yazi_sayisi']; ?>)
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Popüler Yazılar -->
                <div class="popular-posts">
                    <h3 class="h5 mb-3">Popüler Yazılar</h3>
                    <?php
                    $sql = "SELECT b.*, k.kategori_adi, u.ad_soyad as yazar_adi 
                            FROM blog_yazilar b 
                            LEFT JOIN kategoriler k ON b.kategori_id = k.id 
                            LEFT JOIN kullanicilar u ON b.yazar_id = u.id 
                            WHERE b.durum = 'yayinda' 
                            ORDER BY b.goruntulenme DESC 
                            LIMIT 5";
                    $popular_posts = $conn->query($sql);
                    while ($post = $popular_posts->fetch_assoc()):
                    ?>
                    <div class="popular-post-item">
                        <?php if($post['resim_url']): ?>
                        <img src="<?php echo htmlspecialchars($post['resim_url']); ?>" 
                             alt="<?php echo htmlspecialchars($post['baslik']); ?>" 
                             class="popular-post-image">
                        <?php endif; ?>
                        <div class="popular-post-content">
                            <a href="yazi.php?id=<?php echo $post['id']; ?>" class="popular-post-title">
                                <?php echo htmlspecialchars($post['baslik']); ?>
                            </a>
                            <div class="popular-post-meta">
                                <span>
                                    <i class="fas fa-eye me-1"></i>
                                    <?php echo number_format($post['goruntulenme']); ?> görüntülenme
                                </span>
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
</body>
</html> 