<?php
session_start();
require_once('baglan.php');

try {
    // Kategorileri getir
    $sql = "SELECT k.*, 
            COUNT(DISTINCT b.id) as yazi_sayisi,
            COALESCE(SUM(b.goruntulenme), 0) as toplam_goruntulenme,
            COALESCE(SUM(CASE WHEN b.durum = 'yayinda' THEN 1 ELSE 0 END), 0) as aktif_yazi_sayisi
            FROM kategoriler k 
            LEFT JOIN blog_yazilar b ON k.id = b.kategori_id
            GROUP BY k.id
            ORDER BY k.sira ASC, k.kategori_adi ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $kategoriler = $stmt->get_result();
    
    // Toplam kategori sayısını al
    $total_categories = $kategoriler->num_rows;

    // Toplam yazı ve görüntülenme sayısını hesapla
    $total_posts = 0;
    $total_views = 0;
    $kategori_listesi = array();

    while ($row = $kategoriler->fetch_assoc()) {
        $total_posts += $row['aktif_yazi_sayisi'];
        $total_views += $row['toplam_goruntulenme'];
        $kategori_listesi[] = $row;
    }

    // Sonuç kümesini başa al
    mysqli_data_seek($kategoriler, 0);

} catch (Exception $e) {
    error_log("Kategoriler Hatası: " . $e->getMessage());
    $_SESSION['hata'] = "Kategoriler yüklenirken bir hata oluştu: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriler - Blog Sitesi</title>
    <meta name="description" content="Blog kategorilerimizi keşfedin ve ilgilendiğiniz konulardaki yazıları bulun.">
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
            --gradient-start: #6366f1;
            --gradient-end: #8b5cf6;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .page-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            padding: 5rem 0;
            color: white;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='rgba(255,255,255,.075)' fill-rule='evenodd'/%3E%3C/svg%3E") center center fixed;
            opacity: 0.1;
        }

        .category-stats {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.9);
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            border-radius: 100px;
            backdrop-filter: blur(10px);
            display: inline-flex;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .stat-item i {
            font-size: 1.25rem;
            opacity: 0.9;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .category-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .category-card:hover::before {
            opacity: 1;
        }

        .category-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .category-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .category-stats-small {
            color: var(--light-text);
            font-size: 0.9rem;
            display: flex;
            gap: 1rem;
            margin-top: auto;
        }

        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--light-text);
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .empty-state p {
            color: var(--light-text);
            max-width: 500px;
            margin: 0 auto;
        }

        .category-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }

        .category-tag {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1.5rem;
            border-radius: 100px;
            font-size: 0.9rem;
            font-weight: 500;
            color: white;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-tag i {
            font-size: 1rem;
            opacity: 0.9;
        }

        .category-tag:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }

        .category-tag.more {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            padding: 0.5rem 1rem;
        }

        /* Yeni Animasyonlar ve Efektler */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .category-card {
            /* Mevcut stiller korunacak */
            transform: translateY(0);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .category-icon {
            /* Mevcut stiller korunacak */
            position: relative;
        }

        .category-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: inherit;
            border-radius: inherit;
            z-index: -1;
            animation: pulse 2s ease-in-out infinite;
            opacity: 0.5;
        }

        .category-stats {
            /* Mevcut stiller korunacak */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .stat-item {
            /* Mevcut stiller korunacak */
            position: relative;
        }

        .stat-item::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: white;
            transition: width 0.3s ease;
        }

        .stat-item:hover::after {
            width: 100%;
        }

        /* Yeni Özellik: Kategori Filtreleme */
        .category-filters {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 100px;
            color: var(--text-color);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-color: transparent;
        }

        /* Yeni Özellik: Kategori Kartı İçeriği */
        .category-content {
            position: relative;
            z-index: 1;
        }

        .category-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .category-date {
            font-size: 0.85rem;
            color: var(--light-text);
        }

        .category-badge {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Yeni Özellik: Loading Animasyonu */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Yeni Özellik: Scroll to Top Butonu */
        .scroll-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-5px);
        }

        /* Yeni stil: Kategori Ekle Butonu */
        .add-category-btn {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 100px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .add-category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            color: white;
        }

        /* Modal Stilleri */
        .modal-content {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .modal-header .btn-close {
            color: white;
            opacity: 0.8;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Mobil Uyumluluk Düzenlemeleri */
        @media (max-width: 768px) {
            .page-header {
                padding: 3rem 0;
            }

            .category-stats {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
                width: 90%;
                margin-left: auto;
                margin-right: auto;
            }

            .stat-item {
                justify-content: center;
                width: 100%;
            }

            .category-filters {
                gap: 0.5rem;
                padding: 0 1rem;
            }

            .filter-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
                flex: 1 1 auto;
                text-align: center;
                white-space: nowrap;
            }

            .category-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0 1rem;
            }

            .category-card {
                margin: 0;
            }

            .category-meta {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .category-stats-small {
                flex-direction: column;
                gap: 0.5rem;
            }

            .category-list {
                padding: 0 1rem;
            }

            .category-tag {
                padding: 0.4rem 1rem;
                font-size: 0.85rem;
            }

            .add-category-btn {
                margin: 1rem 0 0 0;
                width: 100%;
                justify-content: center;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .scroll-top {
                bottom: 1rem;
                right: 1rem;
                width: 40px;
                height: 40px;
            }
        }

        /* Tablet Uyumluluk */
        @media (min-width: 769px) and (max-width: 1024px) {
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .category-filters {
                flex-wrap: wrap;
                justify-content: center;
            }

            .filter-btn {
                flex: 0 1 auto;
            }
        }

        /* Küçük Mobil Cihazlar */
        @media (max-width: 375px) {
            .page-header {
                padding: 2rem 0;
            }

            h1.display-4 {
                font-size: 2rem;
            }

            .lead {
                font-size: 1rem !important;
            }

            .category-tag {
                padding: 0.3rem 0.8rem;
                font-size: 0.8rem;
            }

            .filter-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }

        /* Yatay Mobil Görünüm */
        @media (max-height: 600px) and (orientation: landscape) {
            .page-header {
                padding: 2rem 0;
            }

            .category-stats {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .modal-dialog {
                max-height: 90vh;
                overflow-y: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">Kategoriler</h1>
                    <p class="lead mb-4" style="font-size: 1.2rem; opacity: 0.9;">
                        İlgilendiğiniz konulardaki yazıları keşfedin
                        <?php if (isset($_SESSION['kullanici_id'])): ?>
                            <a href="#" class="add-category-btn" data-bs-toggle="modal" data-bs-target="#kategoriEkleModal">
                                <i class="fas fa-plus"></i>
                                Kategori Ekle
                            </a>
                        <?php endif; ?>
                    </p>
                    
                    <div class="category-stats">
                        <div class="stat-item">
                            <i class="fas fa-folder"></i>
                            <span><?php echo $total_categories; ?> Kategori</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-file-alt"></i>
                            <span><?php echo $total_posts; ?> Yazı</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-eye"></i>
                            <span><?php echo number_format($total_views); ?> Görüntülenme</span>
                        </div>
                    </div>

                    <?php if ($total_categories > 0): ?>
                    <div class="category-list mt-4">
                        <?php
                        $displayed_categories = 0;
                        foreach ($kategori_listesi as $kat):
                            if ($displayed_categories < 5): // En popüler 5 kategoriyi göster
                                $icon = 'folder';
                                // Kategori adına göre özel ikonlar
                                switch(strtolower($kat['kategori_adi'])) {
                                    case 'teknoloji':
                                        $icon = 'laptop-code';
                                        break;
                                    case 'yazılım':
                                        $icon = 'code';
                                        break;
                                    case 'tasarım':
                                        $icon = 'paint-brush';
                                        break;
                                    case 'pazarlama':
                                        $icon = 'chart-line';
                                        break;
                                    case 'eğitim':
                                        $icon = 'graduation-cap';
                                        break;
                                    case 'sağlık':
                                        $icon = 'heartbeat';
                                        break;
                                    case 'spor':
                                        $icon = 'running';
                                        break;
                                    case 'müzik':
                                        $icon = 'music';
                                        break;
                                    case 'seyahat':
                                        $icon = 'plane';
                                        break;
                                    case 'yemek':
                                        $icon = 'utensils';
                                        break;
                                    case 'Kod':
                                        $icon = 'fa-solid fa-code fa-beat';
                                        break;
                                }
                                echo '<a href="kategori.php?id=' . $kat['id'] . '" class="category-tag">';
                                echo '<i class="fas fa-' . $icon . '"></i>';
                                echo htmlspecialchars($kat['kategori_adi']);
                                echo '</a>';
                                $displayed_categories++;
                            endif;
                        endforeach;
                        if ($total_categories > 5):
                            echo '<span class="category-tag more"><i class="fas fa-ellipsis-h"></i> ' . ($total_categories - 5) . ' daha</span>';
                        endif;
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Kategori Filtreleme -->
        <div class="category-filters">
            <button class="filter-btn active" data-filter="all">
                <i class="fas fa-th-large d-none d-md-inline me-2"></i>Tümü
            </button>
            <button class="filter-btn" data-filter="popular">
                <i class="fas fa-fire d-none d-md-inline me-2"></i>Popüler
            </button>
            <button class="filter-btn" data-filter="new">
                <i class="fas fa-clock d-none d-md-inline me-2"></i>Yeni
            </button>
            <button class="filter-btn" data-filter="most-viewed">
                <i class="fas fa-eye d-none d-md-inline me-2"></i>En Çok Görüntülenen
            </button>
        </div>

        <?php if (isset($_SESSION['basari'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['basari']; unset($_SESSION['basari']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['hata'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['hata']; unset($_SESSION['hata']); ?>
            </div>
        <?php endif; ?>

        <?php if ($total_categories > 0): ?>
            <div class="category-grid">
                <?php while ($kategori = $kategoriler->fetch_assoc()): 
                    $icon = 'folder';
                    // Kategori adına göre özel ikonlar
                    switch(strtolower($kategori['kategori_adi'])) {
                        case 'teknoloji':
                            $icon = 'laptop-code';
                            break;
                        case 'yazılım':
                            $icon = 'code';
                            break;
                        case 'tasarım':
                            $icon = 'paint-brush';
                            break;
                        case 'pazarlama':
                            $icon = 'chart-line';
                            break;
                        case 'eğitim':
                            $icon = 'graduation-cap';
                            break;
                        case 'sağlık':
                            $icon = 'heartbeat';
                            break;
                        case 'spor':
                            $icon = 'running';
                            break;
                        case 'müzik':
                            $icon = 'music';
                            break;
                        case 'seyahat':
                            $icon = 'plane';
                            break;
                        case 'yemek':
                            $icon = 'utensils';
                            break;
                    }
                ?>
                <a href="kategori.php?id=<?php echo $kategori['id']; ?>" class="category-card">
                    <div class="category-content">
                        <div class="category-icon">
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <h2 class="category-title"><?php echo htmlspecialchars($kategori['kategori_adi']); ?></h2>
                        <?php if (!empty($kategori['aciklama'])): ?>
                            <p class="mb-3"><?php echo htmlspecialchars($kategori['aciklama']); ?></p>
                        <?php endif; ?>
                        <div class="category-stats-small">
                            <span><i class="fas fa-file-alt me-2"></i><?php echo $kategori['yazi_sayisi']; ?> Yazı</span>
                            <span><i class="fas fa-eye me-2"></i><?php echo number_format($kategori['toplam_goruntulenme']); ?> Görüntülenme</span>
                        </div>
                        <div class="category-meta">
                            <span class="category-date">
                                <i class="fas fa-clock me-1"></i>Son güncelleme: <?php echo date('d.m.Y'); ?>
                            </span>
                            <?php if ($kategori['yazi_sayisi'] > 10): ?>
                                <span class="category-badge">Popüler</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>Henüz Kategori Bulunmuyor</h3>
                <p>Şu anda sistemde kayıtlı kategori bulunmuyor. Kategoriler yakında eklenecektir.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scroll to Top Button -->
    <div class="scroll-top">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- Kategori Ekleme Modal -->
    <div class="modal fade" id="kategoriEkleModal" tabindex="-1" aria-labelledby="kategoriEkleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kategoriEkleModalLabel">Yeni Kategori Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <form action="kategori_ekle.php" method="POST">
                        <div class="mb-3">
                            <label for="kategori_adi" class="form-label">Kategori Adı</label>
                            <input type="text" class="form-control" id="kategori_adi" name="kategori_adi" required>
                        </div>
                        <div class="mb-3">
                            <label for="aciklama" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="aciklama" name="aciklama" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="ikon" class="form-label">İkon (Font Awesome)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-icons"></i></span>
                                <input type="text" class="form-control" id="ikon" name="ikon" placeholder="Örn: laptop-code">
                            </div>
                            <div class="form-text">
                                <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer">
                                    Font Awesome'dan ikon seçebilirsiniz
                                </a>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-plus me-2"></i>Kategori Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Loading efekti
            const loading = document.querySelector('.loading-overlay');
            loading.classList.add('active');
            setTimeout(() => {
                loading.classList.remove('active');
            }, 500);

            // Scroll to Top butonu
            const scrollTop = document.querySelector('.scroll-top');
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    scrollTop.classList.add('visible');
                } else {
                    scrollTop.classList.remove('visible');
                }
            });

            scrollTop.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Kategori filtreleme
            const filterButtons = document.querySelectorAll('.filter-btn');
            const categoryCards = document.querySelectorAll('.category-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Aktif buton stilini güncelle
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');

                    // Loading efekti göster
                    loading.classList.add('active');

                    setTimeout(() => {
                        const filter = button.dataset.filter;
                        
                        categoryCards.forEach(card => {
                            // Burada gerçek filtreleme mantığı eklenebilir
                            if (filter === 'all') {
                                card.style.display = 'flex';
                            } else {
                                // Örnek filtreleme mantığı
                                const views = parseInt(card.querySelector('.category-stats-small .fa-eye').nextSibling.textContent);
                                const isPopular = card.querySelector('.category-badge')?.textContent === 'Popüler';
                                
                                if (filter === 'popular' && isPopular) {
                                    card.style.display = 'flex';
                                } else if (filter === 'most-viewed' && views > 1000) {
                                    card.style.display = 'flex';
                                } else if (filter === 'new') {
                                    // Son 7 günde eklenenler
                                    card.style.display = 'flex';
                                } else {
                                    card.style.display = 'none';
                                }
                            }
                        });

                        loading.classList.remove('active');
                    }, 300);
                });
            });
        });
    </script>
</body>
</html> 