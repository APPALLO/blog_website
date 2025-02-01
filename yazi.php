<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'baglan.php';
require_once 'fonksiyonlar.php';

// Mevcut URL'yi al
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Yazı ID kontrolü ve veri çekme
$yazi = null;
$hata = null;

if(isset($_GET['id'])) {
    $yazi_id = (int)$_GET['id'];
    
    // Ana yazı sorgusu - prepared statement kullanarak güvenliği artırıyoruz
    $sql = "SELECT b.*, k.kategori_adi, 
            u.kullanici_adi as yazar, u.ad_soyad as yazar_adsoyad, u.profil_resmi as yazar_resmi,
            (SELECT COUNT(*) FROM yorumlar WHERE yazi_id = b.id AND durum = 'onaylandi') as yorum_sayisi
            FROM blog_yazilar b 
            LEFT JOIN kategoriler k ON b.kategori_id = k.id 
            LEFT JOIN kullanicilar u ON b.yazar_id = u.id 
            WHERE b.id = ? AND b.durum = 'yayinda'";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $yazi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $yazi = $result->fetch_assoc();
        // Kategori URL'sini oluştur
        $yazi['kategori_url'] = seo_url($yazi['kategori_adi']);
        
        // Görüntülenme sayısını artır - yazarın kendi yazısını okuduğunda artmasın
        if (!isset($_SESSION['kullanici_id']) || $_SESSION['kullanici_id'] != $yazi['yazar_id']) {
            $update_sql = "UPDATE blog_yazilar SET goruntulenme = goruntulenme + 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $yazi_id);
            $update_stmt->execute();
        }
        
        // Benzer yazıları getir
        $benzer_yazilar_sql = "SELECT id, baslik, ozet, kapak_resmi, tarih, goruntulenme 
                              FROM blog_yazilar 
                              WHERE kategori_id = ? AND id != ? AND durum = 'yayinda' 
                              ORDER BY tarih DESC LIMIT 3";
        $benzer_stmt = $conn->prepare($benzer_yazilar_sql);
        $benzer_stmt->bind_param("ii", $yazi['kategori_id'], $yazi_id);
        $benzer_stmt->execute();
        $benzer_yazilar = $benzer_stmt->get_result();
        
        // Yazının etiketlerini getir
        $etiketler = [];
        if(!empty($yazi['etiketler'])) {
            $etiketler = explode(',', $yazi['etiketler']);
        }
        
        // Yazar bilgilerini al
        $yazar = [
            'ad_soyad' => $yazi['yazar_adsoyad'] ?? 'Anonim',
            'profil_resmi' => $yazi['yazar_resmi'] ?? 'default-avatar.jpg',
            'hakkinda' => $yazi['yazar_hakkinda'] ?? 'Bu yazının yazarı hakkında bilgi bulunmuyor.'
        ];

        if (!$yazar) {
            $yazar = [
                'ad_soyad' => 'Anonim',
                'profil_resmi' => 'default-avatar.jpg',
                'hakkinda' => 'Bu yazının yazarı sistemde bulunamadı.'
            ];
        }
        
    } else {
        $hata = "Yazı bulunamadı veya yayından kaldırılmış olabilir.";
    }
} else {
    $hata = "Geçersiz yazı ID'si.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if($yazi): ?>
        <title><?php echo htmlspecialchars($yazi['baslik']); ?> - Blog Sitesi</title>
        <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($yazi['ozet']), 0, 160)); ?>">
        <!-- Open Graph Etiketleri -->
        <meta property="og:title" content="<?php echo htmlspecialchars($yazi['baslik']); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($yazi['ozet']), 0, 160)); ?>">
        <meta property="og:image" content="<?php echo !empty($yazi['kapak_resmi']) ? $yazi['kapak_resmi'] : 'assets/img/default-post.jpg'; ?>">
        <meta property="og:url" content="<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <?php else: ?>
        <title>Yazı Bulunamadı - Blog Sitesi</title>
    <?php endif; ?>
    
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css">
    <style>
        :root {
            --primary-color: #4F46E5;
            --secondary-color: #9B2C2C;
            --accent-color: #6366F1;
            --success-color: #059669;
            --gray-dark: #374151;
            --gray-medium: #9CA3AF;
            --gray-light: #F3F4F6;
            --white: #FFFFFF;
        }

        .article-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 4rem 0;
            margin-bottom: 3rem;
            color: var(--white);
            border-radius: 0 0 2rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .article-meta {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .author-card {
            background: var(--white);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .author-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .author-details h4 {
            margin: 0;
            color: #333;
        }

        .author-bio {
            margin: 5px 0 0;
            color: #666;
        }

        .content {
            font-size: 1.125rem;
            line-height: 1.8;
            color: var(--gray-dark);
        }

        .content img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            margin: 2rem 0;
        }

        .content pre {
            background: #2d2d2d;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1.5rem 0;
        }

        .content blockquote {
            border-left: 4px solid var(--primary-color);
            padding-left: 1rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: var(--gray-medium);
        }

        .social-share {
            position: sticky;
            top: 2rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .comment-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            margin-top: 3rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .comment {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .comment-author {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .comment-meta h5 {
            margin: 0;
            color: #333;
        }

        .comment-meta small {
            color: #666;
        }

        .comment-content {
            color: #333;
            line-height: 1.6;
        }

        .similar-posts {
            margin-top: 3rem;
        }

        .similar-post-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }

        .similar-post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .tag-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .tag {
            background: var(--gray-light);
            color: var(--gray-dark);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .tag:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .reading-progress-bar {
            height: 100%;
            background: var(--primary-color);
            width: 0%;
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .article-header {
                padding: 2rem 0;
            }

            .article-title {
                font-size: 2rem;
            }

            .social-share {
                position: static;
                margin-bottom: 2rem;
            }
        }

        .share-bar {
            position: sticky;
            bottom: 20px;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .glassmorphism {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .share-icon-wrapper {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .share-main-icon {
            font-size: 1.5rem;
            color: white;
        }

        .share-buttons-wrapper {
            position: relative;
        }

        .btn-share {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            color: white;
        }

        .btn-share i {
            font-size: 1.1rem;
        }

        .btn-facebook {
            background: linear-gradient(135deg, #1877f2, #0d6efd);
        }

        .btn-twitter {
            background: linear-gradient(135deg, #1da1f2, #0c8bd9);
        }

        .btn-linkedin {
            background: linear-gradient(135deg, #0a66c2, #094c8d);
        }

        .btn-whatsapp {
            background: linear-gradient(135deg, #25d366, #1da750);
        }

        .btn-copy {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        .btn-share:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            color: white;
        }

        @media (max-width: 768px) {
            .share-text {
                display: none;
            }
            
            .btn-share {
                padding: 12px;
                border-radius: 50%;
                width: 45px;
                height: 45px;
                justify-content: center;
            }
            
            .share-icon-wrapper {
                width: 40px;
                height: 40px;
                border-radius: 12px;
            }
            
            .share-main-icon {
                font-size: 1.2rem;
            }
        }

        @keyframes shareButtonPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(99, 102, 241, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }

        .btn-share:active {
            transform: scale(0.95);
        }

        .share-success-animation {
            animation: shareButtonPulse 1s;
        }

        .default-avatar {
            background: linear-gradient(45deg, #4361ee, #3f37c9);
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .yazar-avatar,
        .yorum-avatar {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        .yorum-avatar {
            width: 40px;
            height: 40px;
        }

        .yorum {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .yorum:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="reading-progress">
        <div class="reading-progress-bar" id="readingProgress"></div>
    </div>

    <?php if($hata): ?>
        <div class="container my-5">
            <div class="alert alert-danger">
                <h4 class="alert-heading">Hata!</h4>
                <p><?php echo $hata; ?></p>
                <hr>
                <p class="mb-0">
                    <a href="index.php" class="alert-link">Ana sayfaya dön</a> veya başka bir yazı okumak için 
                    <a href="kategoriler.php" class="alert-link">kategorilere göz at</a>.
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Yazı Başlığı ve Meta Bilgileri -->
        <header class="article-header">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white">Ana Sayfa</a></li>
                        <li class="breadcrumb-item">
                            <a href="kategori.php?url=<?php echo $yazi['kategori_url']; ?>" class="text-white">
                                <?php echo htmlspecialchars($yazi['kategori_adi']); ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-white" aria-current="page">
                            <?php echo htmlspecialchars($yazi['baslik']); ?>
                        </li>
                    </ol>
                </nav>
                
                <h1 class="article-title"><?php echo htmlspecialchars($yazi['baslik']); ?></h1>
                
                <div class="article-meta">
                    <div class="author-info">
                        <img src="<?php 
                            if (!empty($yazi['yazar_resmi'])) {
                                echo htmlspecialchars($yazi['yazar_resmi']);
                            } else {
                                echo 'uploads/default-avatar.png';
                            }
                        ?>" alt="<?php echo htmlspecialchars($yazi['yazar_adsoyad']); ?>" class="author-avatar">
                        <div class="author-details">
                            <h4><?php echo htmlspecialchars($yazi['yazar_adsoyad']); ?></h4>
                            <p class="author-bio">Yazar</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="container my-5">
            <div class="row">
                <!-- Ana İçerik -->
                <div class="col-lg-8">
                    <?php if(!empty($yazi['kapak_resmi'])): ?>
                        <img src="<?php echo htmlspecialchars($yazi['kapak_resmi']); ?>" 
                             alt="<?php echo htmlspecialchars($yazi['baslik']); ?>" 
                             class="img-fluid rounded mb-4">
                    <?php endif; ?>

                    <!-- Yazı İçeriği -->
                    <article class="content">
                        <?php echo $yazi['icerik']; ?>
                    </article>

                    <!-- Etiketler -->
                    <?php if(!empty($etiketler)): ?>
                        <div class="tag-cloud mt-4">
                            <?php foreach($etiketler as $etiket): ?>
                                <a href="etiket.php?tag=<?php echo urlencode(trim($etiket)); ?>" class="tag">
                                    #<?php echo htmlspecialchars(trim($etiket)); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Yazar Kartı -->
                    <div class="author-card mt-5">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php 
                                echo !empty($yazi['yazar_resmi']) 
                                    ? htmlspecialchars($yazi['yazar_resmi']) 
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($yazi['yazar_adsoyad']) . '&size=80';
                            ?>" 
                                alt="<?php echo htmlspecialchars($yazi['yazar_adsoyad']); ?>" 
                                class="author-avatar">
                            <div class="ms-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($yazi['yazar_adsoyad']); ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Yorumları Listele -->
                    <div class="comments-section mt-5">
                        <h3>Yorumlar (<?php echo $yazi['yorum_sayisi']; ?>)</h3>
                        
                        <?php if(isset($_SESSION['kullanici_id'])): ?>
                            <!-- Yorum Formu -->
                            <form action="yorum_ekle.php" method="POST" class="mb-5">
                                <input type="hidden" name="yazi_id" value="<?php echo $yazi_id; ?>">
                                <div class="mb-3">
                                    <textarea name="yorum" class="form-control" rows="4" 
                                              placeholder="Yorumunuzu yazın..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Yorum Yap
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Yorum yapabilmek için lütfen <a href="giris.php">giriş yapın</a> veya 
                                <a href="kayit.php">kayıt olun</a>.
                            </div>
                        <?php endif; ?>

                        <!-- Yorum Listesi -->
                        <?php
                        $yorum_sql = "SELECT y.*, k.kullanici_adi, k.ad_soyad, k.profil_resmi 
                                      FROM yorumlar y
                                      LEFT JOIN kullanicilar k ON y.kullanici_id = k.id 
                                      WHERE y.yazi_id = ? AND y.durum = 'onaylandi'
                                      ORDER BY y.tarih DESC";

                        $yorum_stmt = $conn->prepare($yorum_sql);
                        $yorum_stmt->bind_param("i", $yazi_id);
                        $yorum_stmt->execute();
                        $yorumlar = $yorum_stmt->get_result();
                        
                        if($yorumlar->num_rows > 0):
                            while($yorum = $yorumlar->fetch_assoc()):
                        ?>
                            <div class="comment">
                                <div class="comment-author">
                                    <img src="<?php 
                                        if (!empty($yorum['profil_resmi'])) {
                                            echo htmlspecialchars($yorum['profil_resmi']);
                                        } else {
                                            echo 'uploads/default-avatar.png';
                                        }
                                    ?>" alt="<?php echo htmlspecialchars($yorum['ad_soyad']); ?>" class="comment-avatar">
                                    <div class="comment-meta">
                                        <h5><?php echo htmlspecialchars($yorum['ad_soyad']); ?></h5>
                                        <small><?php echo date('d.m.Y H:i', strtotime($yorum['tarih'])); ?></small>
                                    </div>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($yorum['yorum'])); ?>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <p class="text-muted">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Yan Panel -->
                <div class="col-lg-4">
                    <!-- Paylaşım Barı -->
                    <div class="share-bar">
                        <div class="container">
                            <div class="share-wrapper glassmorphism rounded-4 p-4 mb-4">
                                <div class="share-content">
                                    <div class="share-header mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="share-icon-wrapper me-3">
                                                <i class="fas fa-share-alt share-main-icon"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold">Bu Yazıyı Paylaş</h6>
                                                <p class="mb-0 text-muted small">Arkadaşlarınla paylaşarak destek ol</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="share-buttons-wrapper">
                                        <div class="share-buttons d-flex flex-wrap gap-2">
                                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" 
                                               target="_blank" 
                                               class="btn btn-share btn-facebook"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Facebook'ta Paylaş">
                                                <i class="fab fa-facebook-f"></i>
                                                <span class="share-text">Facebook</span>
                                            </a>
                                            
                                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url); ?>&text=<?php echo urlencode($yazi['baslik']); ?>" 
                                               target="_blank" 
                                               class="btn btn-share btn-twitter"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Twitter'da Paylaş">
                                                <i class="fab fa-twitter"></i>
                                                <span class="share-text">Twitter</span>
                                            </a>
                                            
                                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($current_url); ?>&title=<?php echo urlencode($yazi['baslik']); ?>" 
                                               target="_blank" 
                                               class="btn btn-share btn-linkedin"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="LinkedIn'de Paylaş">
                                                <i class="fab fa-linkedin-in"></i>
                                                <span class="share-text">LinkedIn</span>
                                            </a>
                                            
                                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($yazi['baslik'] . ' ' . $current_url); ?>" 
                                               target="_blank" 
                                               class="btn btn-share btn-whatsapp"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="WhatsApp'ta Paylaş">
                                                <i class="fab fa-whatsapp"></i>
                                                <span class="share-text">WhatsApp</span>
                                            </a>
                                            
                                            <button class="btn btn-share btn-copy"
                                                    onclick="copyToClipboard('<?php echo $current_url; ?>')"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Bağlantıyı Kopyala">
                                                <i class="fas fa-link"></i>
                                                <span class="share-text">Kopyala</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Benzer Yazılar -->
                    <?php if($benzer_yazilar->num_rows > 0): ?>
                        <div class="similar-posts">
                            <h5 class="mb-3">Benzer Yazılar</h5>
                            <div class="row g-3">
                                <?php while($benzer = $benzer_yazilar->fetch_assoc()): ?>
                                    <div class="col-12">
                                        <div class="similar-post-card">
                                            <?php if(!empty($benzer['kapak_resmi'])): ?>
                                                <img src="<?php echo htmlspecialchars($benzer['kapak_resmi']); ?>" 
                                                     alt="<?php echo htmlspecialchars($benzer['baslik']); ?>" 
                                                     class="card-img-top" style="height: 150px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <a href="yazi.php?id=<?php echo $benzer['id']; ?>" class="text-decoration-none text-dark">
                                                        <?php echo htmlspecialchars($benzer['baslik']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="far fa-calendar-alt"></i> 
                                                    <?php echo date('d.m.Y', strtotime($benzer['tarih'])); ?> &bull;
                                                    <i class="far fa-eye"></i> 
                                                    <?php echo number_format($benzer['goruntulenme']); ?> görüntülenme
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
    <script>
        // Okuma İlerlemesi
        window.addEventListener('scroll', function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("readingProgress").style.width = scrolled + "%";
        });

        // Kod bloklarını otomatik highlight et
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('pre code').forEach((block) => {
                Prism.highlightElement(block);
            });
        });

        // Resimleri modal içinde göster
        document.querySelectorAll('.content img').forEach(image => {
            image.onclick = function() {
                const modal = document.createElement('div');
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100%';
                modal.style.height = '100%';
                modal.style.backgroundColor = 'rgba(0,0,0,0.9)';
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '1050';
                modal.style.cursor = 'pointer';
                
                const img = document.createElement('img');
                img.src = this.src;
                img.style.maxHeight = '90%';
                img.style.maxWidth = '90%';
                img.style.objectFit = 'contain';
                
                modal.appendChild(img);
                document.body.appendChild(modal);
                
                modal.onclick = function() {
                    modal.remove();
                };
            };
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const copyBtn = document.querySelector('.btn-copy');
                copyBtn.classList.add('share-success-animation');
                
                const originalTitle = copyBtn.getAttribute('data-bs-original-title');
                copyBtn.setAttribute('data-bs-original-title', 'Kopyalandı! ✓');
                bootstrap.Tooltip.getInstance(copyBtn).show();
                
                setTimeout(() => {
                    copyBtn.classList.remove('share-success-animation');
                    copyBtn.setAttribute('data-bs-original-title', originalTitle);
                }, 2000);
            });
        }

        // Tooltip'leri etkinleştir
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html> 