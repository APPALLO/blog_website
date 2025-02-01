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
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        :root {
            --primary-color: #7952b3;
            --secondary-color: #61428f;
            --accent-color: #8c68c9;
            --background-color: #f8f9fa;
            --text-color: #212529;
            --border-color: #dee2e6;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-transform: translateY(-5px);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: var(--gray-dark);
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: url('data:image/svg+xml,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: floatingBg 20s linear infinite;
        }

        @keyframes floatingBg {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(100px, 100px) rotate(360deg); }
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

        .featured-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
            background: white;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .featured-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .featured-card .card-img-top {
            height: 200px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .featured-card:hover .card-img-top {
            transform: scale(1.1);
        }

        .featured-card .card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .featured-card .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
            text-decoration: none;
        }

        .featured-card .card-title:hover {
            color: var(--primary-color);
        }

        .featured-card .card-text {
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 1rem;
            flex: 1;
        }

        .featured-card .card-footer {
            padding: 1rem 1.5rem;
            background: none;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 1;
        }

        .featured-card .author-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .featured-card .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .featured-card .author-name {
            font-size: 0.9rem;
            color: var(--text-color);
            opacity: 0.8;
            font-weight: 500;
        }

        .stats-section {
            background: linear-gradient(45deg, var(--secondary-color), var(--accent-color));
            padding: 50px 0;
            color: white;
        }

        .stat-card {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: var(--hover-transform);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
            background: linear-gradient(135deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .author-card {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: var(--card-shadow);
        }

        .author-card:hover {
            transform: var(--hover-transform);
        }

        .author-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 3px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .author-card:hover .author-avatar {
            transform: scale(1.1) rotate(5deg);
        }

        .social-links a {
            color: var(--primary-color);
            margin: 0 10px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            color: var(--accent-color);
            transform: scale(1.2);
        }

        .newsletter-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 50px 0;
            position: relative;
            overflow: hidden;
        }

        .newsletter-form {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }

        .newsletter-form input {
            border-radius: 30px;
            padding: 15px 150px 15px 20px;
            border: none;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .newsletter-form button {
            position: absolute;
            right: 5px;
            top: 5px;
            border-radius: 25px;
            padding: 10px 30px;
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .newsletter-form button:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(400px, -400px) rotate(360deg); }
        }

        .hero-btn {
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-color);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            position: relative;
            z-index: 1;
        }

        .hero-btn:active {
            transform: scale(0.98);
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            background: #fff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            color: var(--accent-color);
        }

        .hero-btn .btn-icon {
            background: var(--primary-color);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .hero-btn:hover .btn-icon {
            background: var(--accent-color);
            transform: rotate(90deg);
        }

        .hero-btn-outline {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.9);
            color: white;
        }

        .hero-btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .hero-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .hero-actions {
                flex-direction: row;
                justify-content: center;
            }
        }

        .hero-message {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hero-message i {
            color: rgba(255, 255, 255, 0.8);
        }

        .hero-btn-admin {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: 2px solid transparent;
        }

        .hero-btn-admin:hover {
            background: rgb(220, 53, 69);
            color: white;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .hero-btn-admin .btn-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .hero-btn-admin:hover .btn-icon {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .hero-actions {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
                margin: 0 auto;
            }
            
            .hero-btn {
                width: 100%;
                justify-content: center;
            }
        }

        .default-avatar {
            background: linear-gradient(45deg, #4361ee, #3f37c9);
            color: white;
            font-weight: 600;
            font-size: 2.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .default-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section text-white text-center" data-aos="fade-up">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Düşüncelerinizi Paylaşın</h1>
            <p class="lead mb-5">Deneyimlerinizi, fikirlerinizi ve hikayelerinizi bizimle paylaşın</p>
            
            <?php if(isset($_SESSION['kullanici_id'])): ?>
                <button onclick="redirectToCreatePost()" class="hero-btn">
                    <span class="btn-icon">
                        <i class="fas fa-pen-fancy"></i>
                    </span>
                    <span>Yazı Oluştur</span>
                </button>
            <?php else: ?>
                <div class="hero-actions">
                    <button onclick="redirectToLogin()" class="hero-btn">
                        <span class="btn-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </span>
                        <span>Giriş Yap</span>
                    </button>
                    <button onclick="redirectToRegister()" class="hero-btn hero-btn-outline">
                        <span class="btn-icon">
                            <i class="fas fa-user-plus"></i>
                        </span>
                        <span>Kayıt Ol</span>
                    </button>
                    <button onclick="redirectToAdminLogin()" class="hero-btn hero-btn-admin">
                        <span class="btn-icon">
                            <i class="fas fa-user-shield"></i>
                        </span>
                        <span>Admin Girişi</span>
                    </button>
                </div>
                <div class="hero-message">
                    <i class="fas fa-info-circle"></i>
                    <span>Yazı oluşturmak için giriş yapmanız gerekiyor</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="floating-shapes">
            <?php for($i = 0; $i < 10; $i++): ?>
                <div class="shape" style="
                    left: <?php echo rand(0, 100); ?>%;
                    top: <?php echo rand(0, 100); ?>%;
                    width: <?php echo rand(20, 80); ?>px;
                    height: <?php echo rand(20, 80); ?>px;
                    animation-delay: <?php echo $i * 0.5; ?>s;">
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Featured Posts -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">Öne Çıkan Yazılar</h2>
            <div class="row g-4">
                <?php
                // Öne çıkan yazıları getir
                $sql = "SELECT y.*, k.kategori_adi, u.ad_soyad, u.profil_resmi, u.kullanici_adi 
                        FROM blog_yazilar y 
                        JOIN kategoriler k ON y.kategori_id = k.id 
                        JOIN kullanicilar u ON y.yazar_id = u.id 
                        WHERE y.durum = 'yayinda' 
                        ORDER BY y.goruntulenme DESC LIMIT 6";
                $yazilar = $conn->query($sql);
                
                while($yazi = $yazilar->fetch_assoc()):
                ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $yazilar->current_field * 100; ?>">
                    <div class="featured-card">
                        <div class="position-relative">
                            <?php if($yazi['kapak_resmi']): ?>
                                <img src="<?php echo htmlspecialchars($yazi['kapak_resmi']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($yazi['baslik']); ?>">
                            <?php else: ?>
                                <div class="default-image card-img-top d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <span class="category-badge">
                                <i class="fas fa-folder-open me-1"></i>
                                <?php echo htmlspecialchars($yazi['kategori_adi']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <a href="yazi.php?id=<?php echo $yazi['id']; ?>" class="card-title">
                                <?php echo htmlspecialchars($yazi['baslik']); ?>
                            </a>
                            <p class="card-text">
                                <?php echo mb_substr(strip_tags($yazi['ozet']), 0, 150) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="post-meta">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?>
                                </div>
                                <div class="post-meta">
                                    <i class="far fa-eye me-1"></i>
                                    <?php echo number_format($yazi['goruntulenme']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="author-info">
                                <?php if($yazi['profil_resmi'] && file_exists($yazi['profil_resmi'])): ?>
                                    <img src="<?php echo htmlspecialchars($yazi['profil_resmi']); ?>" 
                                         class="author-avatar" 
                                         alt="<?php echo htmlspecialchars($yazi['ad_soyad']); ?>">
                                <?php else: ?>
                                    <div class="default-avatar rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 100px; height: 100px; margin: 0 auto;">
                                        <?php
                                        $initials = mb_substr($yazi['ad_soyad'], 0, 1, 'UTF-8');
                                        echo htmlspecialchars($initials);
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <div class="author-name">
                                    <?php echo htmlspecialchars($yazi['ad_soyad']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <?php
                // İstatistikleri getir
                $total_posts = $conn->query("SELECT COUNT(*) as total FROM blog_yazilar WHERE durum = 'yayinda'")->fetch_assoc()['total'];
                $total_users = $conn->query("SELECT COUNT(*) as total FROM kullanicilar")->fetch_assoc()['total'];
                $total_comments = $conn->query("SELECT COUNT(*) as total FROM yorumlar")->fetch_assoc()['total'];
                $total_views = $conn->query("SELECT SUM(goruntulenme) as total FROM blog_yazilar")->fetch_assoc()['total'];
                
                $stats = [
                    ['icon' => 'fa-newspaper', 'number' => $total_posts, 'label' => 'Yazı'],
                    ['icon' => 'fa-users', 'number' => $total_users, 'label' => 'Kullanıcı'],
                    ['icon' => 'fa-comments', 'number' => $total_comments, 'label' => 'Yorum'],
                    ['icon' => 'fa-eye', 'number' => $total_views, 'label' => 'Görüntülenme']
                ];
                
                foreach($stats as $index => $stat):
                ?>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="stat-card">
                        <i class="fas <?php echo $stat['icon']; ?> fa-2x"></i>
                        <div class="stat-number"><?php echo number_format($stat['number']); ?></div>
                        <div class="stat-label"><?php echo $stat['label']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Popular Authors -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">Popüler Yazarlar</h2>
            <div class="row g-4">
                <?php
                // Popüler yazarları getir
                $sql = "SELECT u.*, COUNT(y.id) as yazi_sayisi, SUM(y.goruntulenme) as toplam_goruntulenme
                        FROM kullanicilar u
                        LEFT JOIN blog_yazilar y ON u.id = y.yazar_id
                        WHERE y.durum = 'yayinda'
                        GROUP BY u.id
                        ORDER BY toplam_goruntulenme DESC
                        LIMIT 4";
                $yazarlar = $conn->query($sql);
                
                while($yazar = $yazarlar->fetch_assoc()):
                ?>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="<?php echo $yazarlar->current_field * 100; ?>">
                    <div class="author-card">
                        <?php if($yazar['profil_resmi'] && file_exists($yazar['profil_resmi'])): ?>
                            <img src="<?php echo htmlspecialchars($yazar['profil_resmi']); ?>" 
                                 class="author-avatar" 
                                 alt="<?php echo htmlspecialchars($yazar['ad_soyad']); ?>">
                        <?php else: ?>
                            <div class="default-avatar rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 100px; height: 100px; margin: 0 auto;">
                                <?php
                                $initials = mb_substr($yazar['ad_soyad'], 0, 1, 'UTF-8');
                                echo htmlspecialchars($initials);
                                ?>
                            </div>
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($yazar['ad_soyad']); ?></h5>
                        <p class="text-muted"><?php echo $yazar['yazi_sayisi']; ?> Yazı</p>
                        <div class="social-links">
                            <?php if (!empty($site_ayarlari['twitter'])): ?>
                                <a href="<?php echo htmlspecialchars($site_ayarlari['twitter']); ?>" target="_blank" class="social-link">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($site_ayarlari['instagram'])): ?>
                                <a href="<?php echo htmlspecialchars($site_ayarlari['instagram']); ?>" target="_blank" class="social-link">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($site_ayarlari['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($site_ayarlari['linkedin']); ?>" target="_blank" class="social-link">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section" data-aos="fade-up">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center text-white">
                    <h2 class="mb-4">Yeni Yazılardan Haberdar Olun</h2>
                    <p class="mb-5">En yeni yazıları kaçırmamak için bültenimize abone olun</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="E-posta adresiniz" required>
                        <button type="submit">Abone Ol</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="floating-shapes">
            <?php for($i = 0; $i < 5; $i++): ?>
                <div class="shape" style="
                    left: <?php echo rand(0, 100); ?>%;
                    top: <?php echo rand(0, 100); ?>%;
                    width: <?php echo rand(20, 80); ?>px;
                    height: <?php echo rand(20, 80); ?>px;
                    animation-delay: <?php echo $i * 0.5; ?>s;">
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            offset: 100,
            once: true
        });

        // İstatistik sayaç animasyonu
        function animateValue(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.innerHTML = Math.floor(progress * (end - start) + start).toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // İstatistik kartları görünür olduğunda sayaç animasyonunu başlat
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumber = entry.target.querySelector('.stat-number');
                    const endValue = parseInt(statNumber.textContent.replace(/,/g, ''));
                    animateValue(statNumber, 0, endValue, 2000);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-card').forEach(card => {
            observer.observe(card);
        });

        function redirectToCreatePost() {
            window.location.href = 'yazi_ekle.php';
        }

        function redirectToLogin() {
            window.location.href = 'giris.php';
        }

        function redirectToRegister() {
            window.location.href = 'kayit.php';
        }

        function redirectToAdminLogin() {
            window.location.href = 'admin/giris.php';
        }
    </script>
</body>
</html> 