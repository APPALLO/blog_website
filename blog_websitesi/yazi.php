<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    include 'baglan.php';
    
    if(isset($_GET['id'])) {
        $yazi_id = (int)$_GET['id'];
        $sql = "SELECT b.*, k.kategori_adi, u.kullanici_adi as yazar, u.ad_soyad as yazar_adsoyad 
                FROM blog_yazilar b 
                LEFT JOIN kategoriler k ON b.kategori_id = k.id 
                LEFT JOIN kullanicilar u ON b.yazar_id = u.id 
                WHERE b.id = $yazi_id AND b.durum = 'yayinda'";
        $result = $conn->query($sql);
        
        if($result->num_rows > 0) {
            $yazi = $result->fetch_assoc();
            echo '<title>' . htmlspecialchars($yazi['baslik']) . ' - Blog Sitesi</title>';
            echo '<meta name="description" content="' . htmlspecialchars(substr(strip_tags($yazi['icerik']), 0, 160)) . '">';
            
            // Görüntülenme sayısını artır - yazarın kendi yazısını okuduğunda artmasın
            if (!isset($_SESSION['kullanici_id']) || $_SESSION['kullanici_id'] != $yazi['yazar_id']) {
                $sql = "UPDATE blog_yazilar SET goruntulenme = goruntulenme + 1 WHERE id = $yazi_id";
                $conn->query($sql);
            }
        } else {
            header("Location: index.php");
            exit();
        }
    } else {
        header("Location: index.php");
        exit();
    }
    ?>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: var(--gray-dark);
            line-height: 1.8;
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

        .article-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="2" cy="2" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }

        .article-title {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 2rem;
            color: var(--white);
        }

        .article-meta {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 1.5rem;
        }

        .author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 3px solid var(--white);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .content {
            font-size: 1.125rem;
            line-height: 1.8;
            color: var(--gray-dark);
        }

        .content p {
            margin-bottom: 1.5rem;
        }

        .content img {
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .social-share {
            position: sticky;
            top: 2rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .social-share a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            margin-bottom: 0.75rem;
            color: var(--white);
            text-decoration: none;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }

        .social-share a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .social-share .facebook { background: #1877F2; }
        .social-share .twitter { background: #1DA1F2; }
        .social-share .whatsapp { background: #25D366; }
        .social-share .linkedin { background: #0A66C2; }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .comments {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .comment {
            border-bottom: 1px solid var(--gray-light);
            padding: 1.5rem 0;
            transition: all 0.3s ease;
        }

        .comment:hover {
            background: var(--gray-light);
            transform: translateX(8px);
            padding: 1.5rem;
            border-radius: 0.75rem;
        }

        .comment:last-child {
            border-bottom: none;
        }

        .comment-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .form-control {
            border: 2px solid var(--gray-light);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--gray-light);
            color: var(--gray-dark);
            border-radius: 2rem;
            margin: 0.25rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .tag:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gray-light);
            z-index: 1000;
        }

        .reading-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
            width: 0;
            transition: width 0.1s ease;
        }

        .table-of-contents {
            position: sticky;
            top: 2rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .table-of-contents ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .table-of-contents li {
            margin-bottom: 0.5rem;
        }

        .table-of-contents a {
            color: var(--gray-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .table-of-contents a:hover {
            background: var(--gray-light);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .table-of-contents a.active {
            background: var(--primary-color);
            color: var(--white);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <article>
                    <?php if($yazi['resim_url']): ?>
                        <img src="<?php echo htmlspecialchars($yazi['resim_url']); ?>" class="img-fluid rounded mb-4" alt="<?php echo htmlspecialchars($yazi['baslik']); ?>">
                    <?php endif; ?>
                    
                    <h1 class="display-4 mb-4"><?php echo htmlspecialchars($yazi['baslik']); ?></h1>
                    
                    <div class="d-flex align-items-center mb-4">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($yazi['yazar_adsoyad']); ?>" class="rounded-circle me-2" width="40" height="40" alt="<?php echo htmlspecialchars($yazi['yazar_adsoyad']); ?>">
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($yazi['yazar_adsoyad']); ?></div>
                            <small class="text-muted">
                                <?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?> · 
                                <?php echo $yazi['goruntulenme']; ?> görüntülenme ·
                                <span class="badge bg-primary"><?php echo htmlspecialchars($yazi['kategori_adi']); ?></span>
                            </small>
                        </div>
                    </div>

                    <!-- Sosyal Medya Paylaşım Butonları -->
                    <div class="social-share mb-4">
                        <div class="d-flex gap-2">
                            <?php
                            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                            $share_text = urlencode($yazi['baslik']);
                            ?>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" target="_blank" class="facebook flex-fill text-center">
                                <i class="fab fa-facebook-f"></i> Paylaş
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url); ?>&text=<?php echo $share_text; ?>" target="_blank" class="twitter flex-fill text-center">
                                <i class="fab fa-twitter"></i> Tweet
                            </a>
                            <a href="https://wa.me/?text=<?php echo $share_text . ' ' . urlencode($current_url); ?>" target="_blank" class="whatsapp flex-fill text-center">
                                <i class="fab fa-whatsapp"></i> Paylaş
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($current_url); ?>&title=<?php echo $share_text; ?>" target="_blank" class="linkedin flex-fill text-center">
                                <i class="fab fa-linkedin-in"></i> Paylaş
                            </a>
                        </div>
                    </div>
                    
                    <div class="content mb-5">
                        <?php echo $yazi['icerik']; ?>
                    </div>
                    
                    <?php
                    // Etiketleri getir
                    $sql = "SELECT e.* FROM etiketler e 
                           INNER JOIN yazi_etiketler ye ON e.id = ye.etiket_id 
                           WHERE ye.yazi_id = $yazi_id";
                    $result = $conn->query($sql);
                    
                    if($result->num_rows > 0) {
                        echo '<div class="mb-4">';
                        echo '<h5>Etiketler:</h5>';
                        while($etiket = $result->fetch_assoc()) {
                            echo '<a href="etiket.php?slug=' . $etiket['slug'] . '" class="badge bg-secondary text-decoration-none me-1">';
                            echo '#' . htmlspecialchars($etiket['etiket_adi']);
                            echo '</a>';
                        }
                        echo '</div>';
                    }
                    ?>
                </article>

                <!-- Yorumlar -->
                <section class="comments mt-5">
                    <h3>Yorumlar</h3>
                    
                    <?php
                    $sql = "SELECT y.*, u.kullanici_adi, u.ad_soyad 
                           FROM yorumlar y 
                           LEFT JOIN kullanicilar u ON y.kullanici_id = u.id 
                           WHERE y.yazi_id = $yazi_id AND y.durum = 'onaylanmis' 
                           ORDER BY y.tarih DESC";
                    $result = $conn->query($sql);
                    
                    if($result->num_rows > 0) {
                        while($yorum = $result->fetch_assoc()) {
                            echo '<div class="card mb-3">';
                            echo '<div class="card-body">';
                            echo '<div class="d-flex justify-content-between">';
                            echo '<h6 class="card-subtitle mb-2 text-muted">' . htmlspecialchars($yorum['ad_soyad'] ?: $yorum['kullanici_adi']) . '</h6>';
                            echo '<small class="text-muted">' . date('d.m.Y H:i', strtotime($yorum['tarih'])) . '</small>';
                            echo '</div>';
                            echo '<p class="card-text">' . nl2br(htmlspecialchars($yorum['yorum_metni'])) . '</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-info">Henüz yorum yapılmamış. İlk yorumu siz yapın!</div>';
                    }
                    ?>
                    
                    <!-- Yorum Formu -->
                    <?php if(isset($_SESSION['kullanici_id'])): ?>
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Yorum Yap</h5>
                                <form action="yorum_ekle.php" method="POST" id="yorumForm">
                                    <input type="hidden" name="yazi_id" value="<?php echo $yazi_id; ?>">
                                    <div class="mb-3">
                                        <label for="yorum" class="form-label">Yorumunuz</label>
                                        <textarea class="form-control" id="yorum" name="yorum" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Gönder</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4">
                            Yorum yapabilmek için lütfen <a href="giris.php">giriş yapın</a> veya <a href="kayit.php">kayıt olun</a>.
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            
            <div class="col-md-4">
                <!-- Yazar Hakkında -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Yazar Hakkında</h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($yazi['yazar_adsoyad']); ?></h6>
                        <p class="card-text">
                            <?php
                            $sql = "SELECT COUNT(*) as yazi_sayisi FROM blog_yazilar WHERE yazar_id = " . $yazi['yazar_id'];
                            $result = $conn->query($sql);
                            $yazi_sayisi = $result->fetch_assoc()['yazi_sayisi'];
                            echo "Toplam $yazi_sayisi yazı yazdı.";
                            ?>
                        </p>
                        <a href="yazar.php?id=<?php echo $yazi['yazar_id']; ?>" class="card-link">Tüm Yazıları</a>
                    </div>
                </div>

                <!-- Benzer Yazılar -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Benzer Yazılar</h5>
                        <?php
                        $sql = "SELECT * FROM blog_yazilar 
                               WHERE kategori_id = {$yazi['kategori_id']} 
                               AND id != $yazi_id 
                               AND durum = 'yayinda' 
                               ORDER BY tarih DESC 
                               LIMIT 5";
                        $result = $conn->query($sql);
                        
                        if($result->num_rows > 0) {
                            echo '<ul class="list-unstyled">';
                            while($benzer = $result->fetch_assoc()) {
                                echo '<li class="mb-2">';
                                echo '<a href="yazi.php?id=' . $benzer['id'] . '">';
                                echo htmlspecialchars($benzer['baslik']);
                                echo '</a>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="card-text">Benzer yazı bulunamadı.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const yorumForm = document.getElementById('yorumForm');
        const yorumlarContainer = document.querySelector('.comments');
        
        if (yorumForm) {
            yorumForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                const alertContainer = document.createElement('div');
                alertContainer.className = 'alert mt-3';
                
                // Submit butonunu devre dışı bırak
                submitButton.disabled = true;
                
                fetch('yorum_ekle.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Yorum başarıyla eklendiyse
                        alertContainer.className = 'alert alert-success mt-3';
                        alertContainer.textContent = data.message;
                        
                        // Yeni yorumu ekle
                        const yorumlarDiv = document.querySelector('.comments .alert-info');
                        if (yorumlarDiv) {
                            // İlk yorum ise "henüz yorum yapılmamış" mesajını kaldır
                            yorumlarDiv.remove();
                        }
                        
                        // Yeni yorumu listenin başına ekle
                        const yorumlarList = document.querySelector('.comments');
                        yorumlarList.insertAdjacentHTML('afterbegin', data.yorum_html);
                        
                        // Formu temizle
                        yorumForm.reset();
                    } else {
                        // Hata varsa
                        alertContainer.className = 'alert alert-danger mt-3';
                        alertContainer.textContent = data.message;
                    }
                })
                .catch(error => {
                    alertContainer.className = 'alert alert-danger mt-3';
                    alertContainer.textContent = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                })
                .finally(() => {
                    // Submit butonunu tekrar aktif et
                    submitButton.disabled = false;
                    
                    // Alert mesajını göster
                    yorumForm.appendChild(alertContainer);
                    
                    // 3 saniye sonra alert mesajını kaldır
                    setTimeout(() => {
                        alertContainer.remove();
                    }, 3000);
                });
            });
        }
    });
    </script>
</body>
</html> 