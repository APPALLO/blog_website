<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'baglan.php';

// Yazarları getir
$sql = "SELECT k.*, 
        COUNT(DISTINCT b.id) as yazi_sayisi,
        SUM(b.goruntulenme) as toplam_goruntulenme,
        MAX(b.tarih) as son_yazi_tarihi
        FROM kullanicilar k
        LEFT JOIN blog_yazilar b ON k.id = b.yazar_id AND b.durum = 'yayinda'
        WHERE k.rol IN ('yazar', 'admin') 
        AND k.durum = '1'
        GROUP BY k.id
        HAVING yazi_sayisi > 0
        ORDER BY toplam_goruntulenme DESC, yazi_sayisi DESC, son_yazi_tarihi DESC";
$yazarlar = $conn->query($sql);

// Popüler yazarlar için rozet ekle
function getPopulerlikRozeti($goruntulenme, $yazi_sayisi) {
    if ($goruntulenme > 1000 && $yazi_sayisi >= 5) {
        return '<span class="badge bg-danger ms-2"><i class="fas fa-fire"></i> Popüler Yazar</span>';
    } elseif ($goruntulenme > 500 && $yazi_sayisi >= 3) {
        return '<span class="badge bg-warning ms-2"><i class="fas fa-star"></i> Yükselen Yazar</span>';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazarlar - Blog Sitesi</title>
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

        .page-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: 3rem 0;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.1)" x="0" y="0" width="100" height="100"/></svg>') repeat;
            opacity: 0.1;
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            from { background-position: 0 0; }
            to { background-position: 100px 100px; }
        }

        .author-card {
            background: var(--card-bg);
            border-radius: 1rem;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            position: relative;
        }

        .author-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .author-cover {
            height: 100px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            position: relative;
            overflow: hidden;
        }

        .author-cover::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.1)" x="0" y="0" width="100" height="100"/></svg>') repeat;
            opacity: 0.1;
        }

        .author-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid var(--card-bg);
            position: absolute;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
            top: 100px;
            transition: all 0.3s ease;
            background: white;
        }

        .author-card:hover .author-avatar {
            transform: translateX(-50%) translateY(-50%) scale(1.1);
        }

        .author-info {
            text-align: center;
            padding: 4rem 1.5rem 1.5rem;
        }

        .author-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .author-role {
            display: inline-block;
            padding: 0.25rem 1rem;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .author-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: rgba(79, 70, 229, 0.03);
            border-radius: 1rem;
            transition: all 0.3s ease;
        }

        .author-card:hover .author-stats {
            background: rgba(79, 70, 229, 0.06);
            transform: translateY(-2px);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--light-text);
            font-weight: 500;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-link {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            transform: translateY(-3px);
        }

        .btn-profile {
            color: var(--primary-color);
            background: transparent;
            border: 2px solid var(--primary-color);
            border-radius: 0.5rem;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-profile:hover {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .author-bio {
            color: var(--light-text);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 1rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Popüler yazar rozeti stilleri */
        .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            transition: all 0.3s ease;
        }
        
        .badge i {
            margin-right: 0.25rem;
        }
        
        .badge.bg-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white !important;
        }
        
        .author-card:hover .badge {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">Yazarlarımız</h1>
                    <p class="lead mb-0">Deneyimli yazarlarımızın profilleri ve yazıları</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row g-4">
            <?php while ($yazar = $yazarlar->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="author-card">
                        <div class="author-cover"></div>
                        <img src="<?php 
                            echo !empty($yazar['profil_resmi']) 
                                ? htmlspecialchars($yazar['profil_resmi']) 
                                : 'https://ui-avatars.com/api/?name=' . urlencode($yazar['ad_soyad']) . '&size=100';
                        ?>" alt="<?php echo htmlspecialchars($yazar['ad_soyad']); ?>" class="author-avatar">
                        
                        <div class="author-info">
                            <h3 class="author-name">
                                <?php echo htmlspecialchars($yazar['ad_soyad']); ?>
                                <?php echo getPopulerlikRozeti($yazar['toplam_goruntulenme'], $yazar['yazi_sayisi']); ?>
                            </h3>
                            <span class="author-role"><?php echo $yazar['rol'] == 'admin' ? 'Admin' : 'Yazar'; ?></span>
                            
                            <?php if (!empty($yazar['hakkinda'])): ?>
                                <p class="author-bio"><?php echo htmlspecialchars($yazar['hakkinda']); ?></p>
                            <?php endif; ?>

                            <div class="author-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $yazar['yazi_sayisi']; ?></div>
                                    <div class="stat-label">Yazı</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo number_format($yazar['toplam_goruntulenme']); ?></div>
                                    <div class="stat-label">Görüntülenme</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo date('d.m.Y', strtotime($yazar['son_yazi_tarihi'])); ?></div>
                                    <div class="stat-label">Son Yazı</div>
                                </div>
                            </div>

                            <div class="social-links">
                                <?php if (!empty($yazar['twitter'])): ?>
                                    <a href="<?php echo htmlspecialchars($yazar['twitter']); ?>" class="social-link" target="_blank">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($yazar['linkedin'])): ?>
                                    <a href="<?php echo htmlspecialchars($yazar['linkedin']); ?>" class="social-link" target="_blank">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($yazar['website'])): ?>
                                    <a href="<?php echo htmlspecialchars($yazar['website']); ?>" class="social-link" target="_blank">
                                        <i class="fas fa-globe"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <a href="yazar.php?id=<?php echo $yazar['id']; ?>" class="btn btn-profile">
                                <span>Profili Görüntüle</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 